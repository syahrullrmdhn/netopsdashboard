
// index.js
import dotenv from 'dotenv';
dotenv.config();
import chatRouter, { generateGeminiContent } from './chat.js';
import express from 'express';
import cors from 'cors';
import qrcode from 'qrcode';
import fetch from 'node-fetch';
import pkg from 'whatsapp-web.js';
import { createLogger, format, transports } from 'winston';

global.fetch = fetch;

const { Client, LocalAuth, MessageMedia } = pkg;

// ————— Logger Setup —————
const {
  TIMEZONE      = 'Asia/Jakarta',
  LOG_LEVEL     = 'info',
} = process.env;

const logger = createLogger({
  level: LOG_LEVEL,
  format: format.combine(
    format.timestamp(),
    format.printf(({ timestamp, level, message }) => `[${timestamp}] ${level.toUpperCase()}: ${message}`)
  ),
  transports: [
    new transports.Console(),
    new transports.File({ filename: 'bot.log' })
  ]
});

const {
  CLIENT_ID             = 'laravel-bot',
  LARAVEL_BASE          = 'http://127.0.0.1:8000',
  LARAVEL_API_TOKEN     = '',
  REPORT_EXCEL_ENDPOINT = `${LARAVEL_BASE}/api/report/export-spout`,
  REPORT_PDF_ENDPOINT   = `${LARAVEL_BASE}/api/report/export-pdf`,
  WHATSAPP_GROUP_ID     = '',            // ambil langsung dari env
  PORT                  = 3001,
  NOC_SPV_CONTACT       = 'Assistant Head of NOC: +62 857-4293-8394',
  NOC_MANAGER_CONTACT   = 'Head of NOC: +62 823-8629-4673',
  NOC_SYSADMIN_CONTACT  = 'System Administrator: syahrul@abhinawa.co.id',
} = process.env;

// Buat DEFAULT_GROUP_ID terpisah
const DEFAULT_GROUP_ID = WHATSAPP_GROUP_ID;
console.log('Using WhatsApp Group ID:', DEFAULT_GROUP_ID);

const NOC_CONTACTS = {
  SPV     : NOC_SPV_CONTACT,
  MANAGER : NOC_MANAGER_CONTACT,
  SYSADMIN: NOC_SYSADMIN_CONTACT
};

// ————— Express Setup —————
const app = express();
app.use(cors());
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use('/api/chat', chatRouter);
// ————— WhatsApp‑Web.js Setup —————
const client = new Client({
  authStrategy: new LocalAuth({ clientId: CLIENT_ID }),
  puppeteer: {
    headless: true,
    args: ['--no-sandbox','--disable-setuid-sandbox']
  }
});

let lastQr = null;
let ready  = false;

client.on('qr', qr => {
  lastQr = qr;
  ready = false;
  logger.info('New QR code generated');
});

client.on('ready', () => {
  ready = true;
  logger.info('WhatsApp client is ready & authenticated');
});

client.on('auth_failure', err => {
  logger.error(`Authentication failure: ${err}`);
  ready = false;
  client.initialize();
});

client.on('disconnected', reason => {
  logger.warn(`Client disconnected: ${reason}`);
  ready = false;
  client.initialize();
});
client.initialize();

// ————— Helper Functions —————
const header  = title => `*${title.toUpperCase()}*\n────────────────────\n`;
const section = (t, c) => `*${t}:*\n${c}\n`;
const item    = (l, v) => `  • *${l}:* ${v}\n`;
const footer  = ()    => `\n────────────────────\n*NOC Command Center*\nAbhinawa System`;

const fetchWithAuth = async (url, options = {}) => {
  try {
    const response = await fetch(url, {
      ...options,
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${LARAVEL_API_TOKEN}`,
        ...(options.headers || {})
      }
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response;
  } catch (err) {
    logger.error(`API request failed: ${err.message}`);
    throw err;
  }
};

function parseArgs(parts) {
  const params = {};
  parts.forEach(p => {
    const idx = p.indexOf('=');
    if (idx > 0) {
      const key = p.slice(0, idx).trim();
      const val = p.slice(idx+1).trim();
      if (key && val) params[key] = val;
    }
  });
  return params;
}

async function fetchReport(format, params) {
  const qs       = new URLSearchParams(params).toString();
  const endpoint = format === 'excel'
    ? REPORT_EXCEL_ENDPOINT
    : REPORT_PDF_ENDPOINT;
  const url      = `${endpoint}?${qs}`;

  logger.info(`Fetching report from: ${url}`);
  const res = await fetchWithAuth(url);
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const buffer = await res.arrayBuffer();
  const base64 = Buffer.from(buffer).toString('base64');
  const mime = format === 'excel'
    ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    : 'application/pdf';

  // Use the constructor instead of fromDataUri
  const filename = `report_${params.start_date}_${params.end_date}.${format === 'excel' ? 'xlsx' : 'pdf'}`;
  return new MessageMedia(mime, base64, filename);
}

async function escalateTicket(phone, ticket) {
  const jid = phone.endsWith('@c.us') ? phone : `${phone}@c.us`;
  const now = new Date().toLocaleString('en-US', { timeZone: TIMEZONE });
  const msg = [
    `*ESCALATION – TICKET #${ticket.ticket_number}*`,
    `• Status   : ${ticket.status}`,
    `• Customer : ${ticket.customer}`,
    `• Issue    : ${ticket.problem_detail}`,
    `• Time     : ${now}`,
  ].join('\n');

  await client.sendMessage(jid, msg);
  logger.info(`Escalation sent to ${jid}`);
}
async function suggestGroup(q) {
  try {
    // Panggil endpoint grup (pastikan URL benar)
    const url = `${LARAVEL_BASE}/api/groups?q=${encodeURIComponent(q)}`;
    const res = await fetchWithAuth(url);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const groups = await res.json();

    if (!groups.length) return null; // tidak ada match

    // Compose pesan saran
    let reply = `🔎 *Group(s) found for "${q}":*\n`;
    groups.forEach(g => {
      reply += `• *${g.group_name}* (${g.customer_count} customers)\n`;
      reply += `  _Export:_ /report pdf group="${g.group_name}" start_date=YYYY-MM-DD end_date=YYYY-MM-DD\n`;
    });
    reply += `\nSalin/ubah command di atas untuk export report sesuai group.`;
    return reply;
  } catch (err) {
    logger.error('Group suggest error: ' + err);
    return null;
  }
}

function formatTicket(ticket) {
  const icons = { Open:'🟡', Closed:'🟢', Pending:'🟠', Escalated:'🔴' };
  const icon  = icons[ticket.status] || '⚪';
  let resp = header(`TICKET #${ticket.ticket_number}`);
  resp += item('Status',      `${icon} ${ticket.status}`);
  resp += item('Priority',    ticket.priority || 'Normal');
  resp += item('Customer',    ticket.customer);
  resp += item('Created',     ticket.created_at);
  resp += item('Last Update', ticket.updated_at);
  resp += section('Description', ticket.problem_detail);
  resp += footer();
  return resp;
}
async function fetchRfoPdf(ticketId) {
  const url = `${LARAVEL_BASE}/api/rfo/${ticketId}/pdf`;
  logger.info(`Fetching RFO PDF: ${url}`);
  const res = await fetchWithAuth(url);
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const buffer = await res.arrayBuffer();
  const base64 = Buffer.from(buffer).toString('base64');
  return new MessageMedia('application/pdf', base64, `RFO_${ticketId}.pdf`);
}

// ————— HTTP Endpoints —————
app.get('/session', async (_, res) => {
  try {
    let qrImage = null;
    if (!ready && lastQr) qrImage = await qrcode.toDataURL(lastQr);
    res.json({
      connected: ready,
      qr:        qrImage,
      status:    ready ? 'authenticated' : 'awaiting_authentication',
      timestamp: new Date().toISOString()
    });
  } catch (err) {
    logger.error(`Session endpoint error: ${err}`);
    res.status(500).json({ error: 'Internal server error' });
  }
});

app.post('/send', async (req, res) => {
  try {
    const { to, message } = req.body;
    if (!to || !message) {
      return res.status(400).json({ error:'Missing to or message' });
    }
    await client.sendMessage(to, message);
    logger.info(`Message sent to ${to}`);
    res.json({ success:true, timestamp:new Date().toISOString() });
  } catch (err) {
    logger.error(`Message send failed: ${err}`);
    res.status(500).json({ error:'Message delivery failed', details: err.toString() });
  }
});
app.post('/api/notify-handover', async (req, res) => {
  // Ambil message—bisa dari req.body.message (JSON) atau raw text
  let message = req.body.message;
  if (!message && typeof req.body === 'string') {
    message = req.body;
  }
  message = (message || '').trim();

  logger.info('Handover payload message:', message);

  if (!message) {
    logger.error('Missing message');
    return res.status(400).json({ error: 'Missing message' });
  }
  if (!ready) {
    logger.error('WhatsApp client not ready!');
    return res.status(503).json({ error: 'Client not ready' });
  }

  try {
    await client.sendMessage(DEFAULT_GROUP_ID, message);
    logger.info(`Handover sent to group ${DEFAULT_GROUP_ID}`);
    return res.json({ success: true });
  } catch (err) {
    logger.error(`Failed to send handover: ${err}`);
    return res.status(500).json({ error: 'Failed to send handover', details: err.toString() });
  }
});

// === Notify Ticket Open ===
app.post('/api/notify-ticket-open', async (req, res) => {
  logger.info('[OPEN] Payload:', req.body);

  const { group_id, ticket_number, customer, issue } = req.body;
  const gid = group_id || DEFAULT_GROUP_ID;

  if (!ticket_number || !customer || !issue) {
    return res.status(400).json({ error: 'Missing required fields' });
  }
  if (!ready) {
    return res.status(503).json({ error: 'WhatsApp client not ready' });
  }

  const now = new Date().toLocaleString('en-US', { timeZone: TIMEZONE });
  const message = `*[TICKET OPEN]*\n` +
                  `Ticket: ${ticket_number}\n` +
                  `Customer: ${customer}\n` +
                  `Issue: ${issue}\n` +
                  `Time: ${now}`;

  try {
    await client.sendMessage(gid, message);
    logger.info(`[OPEN] sent to ${gid}`);
    res.json({ success: true });
  } catch (err) {
    logger.error(`[OPEN] send failed: ${err}`);
    res.status(500).json({ error: 'Failed to send notify open' });
  }
});


// === Notify Ticket Close ===
app.post('/api/notify-ticket-close', async (req, res) => {
  logger.info('[CLOSE] Payload:', req.body);

  const { group_id, ticket_number, customer, issue } = req.body;
  const gid = group_id || DEFAULT_GROUP_ID;

  if (!ticket_number || !customer || !issue) {
    return res.status(400).json({ error: 'Missing required fields' });
  }
  if (!ready) {
    return res.status(503).json({ error: 'WhatsApp client not ready' });
  }

  const now = new Date().toLocaleString('en-US', { timeZone: TIMEZONE });
  const message = `*[TICKET CLOSED]*\n` +
                  `Ticket: ${ticket_number}\n` +
                  `Customer: ${customer}\n` +
                  `Issue: ${issue}\n` +
                  `Time: ${now}`;

  try {
    await client.sendMessage(gid, message);
    logger.info(`[CLOSE] sent to ${gid}`);
    res.json({ success: true });
  } catch (err) {
    logger.error(`[CLOSE] send failed: ${err}`);
    res.status(500).json({ error: 'Failed to send notify close' });
  }
});

// === Send Escalation by Level ===
app.post('/sendescalation', async (req, res) => {
  const { level, ticket } = req.body;
  if (typeof level !== 'number' || !ticket) {
    return res
      .status(400)
      .json({ error: 'Missing or invalid payload: need numeric level and ticket object' });
  }
  if (!ready) {
    return res.status(503).json({ error: 'WhatsApp client not ready' });
  }

  try {
    // 1) fetch escalation contact from your Laravel API
    const apiRes = await fetchWithAuth(
      `${LARAVEL_BASE}/api/escalation‐levels/${encodeURIComponent(level)}`
    );
    const lvl = await apiRes.json();

    // 2) actually send it
    await escalateTicket(lvl.phone, ticket);

    return res.json({ success: true });
  } catch (err) {
    logger.error(`/sendescalation error: ${err}`);
    return res.status(500).json({ error: err.toString() });
  }
});

const chatContexts = new Map();
const MAX_CONTEXT = 10;

client.on('message', async msg => {
  try {
    if (msg.from === 'status@broadcast' || msg.hasMedia) return;

    const chatId = msg.from;
    const body   = msg.body.trim();
    const lower  = body.toLowerCase();
    const parts  = body.split(/\s+/);
    const command = parts[0].toLowerCase();

    logger.info(`Received from ${chatId}: ${body}`);

    // Simpan history obrolan untuk context
    if (!chatContexts.has(chatId)) chatContexts.set(chatId, []);
    const contextArr = chatContexts.get(chatId);
    contextArr.push({ user: msg.author || msg.from, text: body });
    if (contextArr.length > MAX_CONTEXT) contextArr.shift();

    // === Command /story
    if (command === '/story' && parts.length > 1) {
      const userPrompt = parts.slice(1).join(' ');
      await msg.reply('Sebentar, saya coba jawab dulu...');
      try {
        const story = await generateGeminiContent(userPrompt);
        await msg.reply(story || 'Maaf, saya belum bisa jawab sekarang.');
      } catch (err) {
        logger.error('Gemini command failed:', err);
        await msg.reply('Sepertinya ada kendala, nanti coba lagi ya.');
      }
      return;
    }

    // === Trigger "nawa" di mana saja di pesan (contextual)
    if (/\bnawa\b/i.test(body) || lower.includes('nawa')) {
    // Tidak ada reply awal/menunggu

    // Gabungkan context beberapa chat terakhir
    const chatHistory = contextArr
        .map(c => `${c.user}: ${c.text}`)
        .join('\n');

    const prompt = `Ini beberapa percakapan sebelumnya:\n${chatHistory}\n\nBalas pesan terakhir sebagai Nawa:`;

    try {
        const reply = await generateGeminiContent(prompt);
        await msg.reply(reply || 'Maaf, saya belum bisa jawab sekarang.');
    } catch (err) {
        logger.error('Gemini (contextual) failed:', err);
        await msg.reply('Sepertinya ada kendala, nanti coba lagi ya.');
    }
    return;
    }

    // /help
if (command === '/help') {
  await msg.reply([
    header('NOC COMMAND CENTER - ABHINAWA SYSTEM'),
    'Selamat datang di NOC Command Bot. Berikut panduan lengkap untuk mengoptimalkan penggunaan layanan kami:',
    '',
    '📌 *General Commands*',
    '  ┣ /help               - Menampilkan menu bantuan ini',
    '  ┣ /onduty             - Informasi petugas shift aktif & jadwal berikutnya',
    '  ┗ /time               - Waktu server terkini',
    '',
    '🎟️ *Ticket Management*',
    '  ┣ /open               - Daftar tiket aktif (open tickets)',
    '  ┣ /status <ID>        - Cek status tiket spesifik',
    '  ┣ /chronology <ID>    - Riwayat kronologi tiket',
    '  ┣ /rfo <ticket_id>    - Unduh dokumen RFO (PDF)',
    '  ┗ /openticket <cust/ID> <issue> <detail>',
    '        - Buka tiket baru via WhatsApp dengan auto-suggest customer',
    '',
    '🔄 *Shift Operations*',
    '  ┗ /handoverhistory [YYYY-MM-DD]',
    '        - Riwayat serah terima shift (default: hari ini)',
    '',
    '📈 *Reporting Tools*',
    '  ┣ /report excel start_date=YYYY-MM-DD end_date=YYYY-MM-DD',
    '  ┣ /report pdf start_date=YYYY-MM-DD end_date=YYYY-MM-DD',
    '  ┗ /quickreport        - Laporan harian otomatis (format PDF)',
    '',
    '🤖 *AI Assistant*',
    '  ┗ (Sebut "nawa" dalam pesan untuk respon kontekstual)',
    '',
    section('SUPPORT ESCALATION',
      `🛡️ *Supervisor*: ${NOC_CONTACTS.SPV}\n👔 *Manager*: ${NOC_CONTACTS.MANAGER}\n💻 *SysAdmin*: ${NOC_CONTACTS.SYSADMIN}`),
    '',
    footer('© 2025 NOC Abhinawa System | v2.1.0')
  ].join('\n'));
  return;
}
    // /time
    if (command === '/time') {
      const now = new Date().toLocaleString('en-US', { timeZone: TIMEZONE });
      await msg.reply([
        header('SERVER TIME'),
        `*Timezone:* ${TIMEZONE}`,
        `*Current Time:* ${now}`,
        footer()
      ].join('\n'));
      return;
    }

    // /open
    if (command === '/open' || command === '/ticketopen') {
    try {
        const r = await fetchWithAuth(`${LARAVEL_BASE}/api/tickets/open`);
        const tickets = await r.json();
        if (!tickets.length) {
        await msg.reply(header('OPEN TICKETS') + 'No open tickets.' + footer());
        return;
        }
        let resp = header('OPEN TICKETS') + `Total: *${tickets.length}*\n\n`;
        tickets.forEach(t => {
        resp += `📌 *#${t.ticket_number}*\n`;
        resp += item('Issue',       t.issue);
        resp += item('Customer',    t.customer);
        resp += item('Last Update', t.last_update);
        resp += item('Update Detail', t.last_update_detail) + '\n';  // << Tambah ini!
        });
        resp += footer();
        await msg.reply(resp);
    } catch (err) {
        logger.error(`Open tickets error: ${err}`);
        await msg.reply(header('ERROR') + 'Unable to fetch open tickets.' + footer());
    }
    return;
    }


    // /status <ticket#>
    if (command === '/status' && parts[1]) {
      const no = parts[1];
      try {
        const r = await fetchWithAuth(`${LARAVEL_BASE}/api/tickets/number/${encodeURIComponent(no)}`);
        const ticket = await r.json();
        await msg.reply(formatTicket(ticket));
      } catch (err) {
        logger.error(`Status error: ${err}`);
        await msg.reply([
          header('TICKET NOT FOUND'),
          `Could not locate ticket #${no}.`,
          section('Support', NOC_CONTACTS.SPV),
          footer()
        ].join('\n'));
      }
      return;
    }

    // /chronology <ticket#>
    if (command === '/chronology' && parts[1]) {
      const no = parts[1];
      try {
        const r = await fetchWithAuth(`${LARAVEL_BASE}/api/tickets/number/${encodeURIComponent(no)}`);
        const ticket = await r.json();
        let resp = header(`HISTORY #${ticket.ticket_number}`) + item('Updates', ticket.chronology.length) + '\n';
        ticket.chronology.forEach((u,i) => {
          resp += `🔹 Update ${i+1} - ${u.timestamp}\n`;
          resp += item('User', u.user);
          resp += item('Details', u.detail) + '\n';
        });
        resp += footer();
        await msg.reply(resp);
      } catch (err) {
        logger.error(`Chronology error: ${err}`);
        await msg.reply(header('ERROR') + `Cannot retrieve history for #${no}.` + footer());
      }
      return;
    }

    // /onduty
    if (command === '/onduty') {
      try {
        const r = await fetchWithAuth(`${LARAVEL_BASE}/api/noc/onduty`);
        const d = await r.json();
        await msg.reply([
          header('CURRENT SHIFT'),
          item('Current Shift', `${d.current.shift} - ${d.current.user}`),
          item('Next Shift',    `${d.next.shift} - ${d.next.user}`),
          footer()
        ].join('\n'));
      } catch (err) {
        logger.error(`Onduty error: ${err}`);
        await msg.reply(header('ERROR') + 'Unable to fetch shift info.' + footer());
      }
      return;
    }

   // /handoverhistory [date]
if (command === '/handoverhistory') {
  const date = parts[1] || '';
  let url = `${LARAVEL_BASE}/api/noc/history`;
  if (date) url += `/${encodeURIComponent(date)}`;
  try {
    const r = await fetchWithAuth(url);
    const logs = await r.json();
    if (!logs.length) {
      await msg.reply(
        header('HANDOVER SHIFT') +
        `Tidak ada catatan serah terima shift untuk ${date || 'hari ini'}.\n` +
        footer()
      );
      return;
    }

    logs.forEach((l) => {
      // --- Format Issue List ---
      let issues = (l.issues || '-')
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<[^>]+>/g, '')
        .split('\n')
        .map(s => s.trim())
        .filter(Boolean);

      let issuesStr = '';
      if (issues.length && !(issues.length === 1 && issues[0] === '-')) {
        issuesStr = issues
          .map((s, i) => {
            // Pisahkan dengan delimiter jika ada "|"
            if (s.includes('|')) {
              const parts = s.split('|').map(part => part.trim());
              // Multi baris agar mudah dibaca
              return `${i + 1}. ${parts[0]}\n   ${parts[1] || ''}\n   ${parts.slice(2).join(' | ')}`.trim();
            }
            return `${i + 1}. ${s}`;
          })
          .join('\n');
      } else {
        issuesStr = 'Tidak ada issue tercatat.';
      }

      // --- Compose WhatsApp Message ---
      let reply =
        `🌅 [HANDOVER SHIFT ${l.shift ? l.shift.toUpperCase() : ''}]\n` +
        `────────────────────────\n` +
        `• Shift        : ${l.shift || '-'}\n` +
        `• Dari         : ${l.from || '-'}\n` +
        `• Ke           : ${l.to || '-'}\n\n` +
        `📋 *Daftar Issues:*\n${issuesStr}\n\n` +
        `────────────────────────\n` +
        `NOC Command Center | Abhinawa System`;

      // Send for each log (or you can merge if only want latest)
      msg.reply(reply);
    });
  } catch (err) {
    logger.error(`Handover history error: ${err}`);
    await msg.reply(
      header('ERROR') +
      'Terjadi kendala saat mengambil data log serah terima shift.' +
      footer()
    );
  }
  return;
}

if (command === '/report' && parts.length > 2) {
  const fmt = parts[1].toLowerCase();
  if (!['excel','pdf'].includes(fmt)) {
    await msg.reply(header('INVALID FORMAT') + 'Use "excel" or "pdf".' + footer());
    return;
  }
  const params = parseArgs(parts.slice(2));
  if (!params.start_date || !params.end_date) {
    await msg.reply(header('MISSING PARAMETERS') +
      'Specify start_date and end_date.' + footer());
    return;
  }

  // SUGGEST GROUP LOGIC
  if (params.group) {
    let groupKey = params.group.trim().replace(/^"|"$/g, '');

    // Query ke API grup
    const url = `${LARAVEL_BASE}/api/groups?q=${encodeURIComponent(groupKey)}`;
    try {
      const res = await fetchWithAuth(url);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const groups = await res.json();

      // --- TIDAK ADA GROUP ---
      if (!groups.length) {
        await msg.reply(
          header('NO GROUP MATCH') +
          `Tidak ditemukan group mengandung kata "${groupKey}".\nGunakan /groups untuk melihat semua group.` +
          footer()
        );
        return;
      }

      // --- KALAU HANYA 1 GROUP, AUTO EXPORT ---
      if (groups.length === 1) {
        // ganti ke group_name dari DB
        params.group = groups[0].group_name;
        await msg.reply(`⏳ Generating ${fmt.toUpperCase()} report for group *${groups[0].group_name}*...`);
        try {
          const media = await fetchReport(fmt, params);
          await client.sendMessage(msg.from, media, {
            caption: `📊 Report ${groups[0].group_name} (${params.start_date} to ${params.end_date})`
          });
          logger.info(`Report sent to ${msg.from}`);
        } catch (err) {
          logger.error(`Report error: ${err}`);
          await msg.reply(header('REPORT FAILED') + err.message + footer());
        }
        return; // SELESAI!
      }

      // --- LEBIH DARI SATU GROUP, SUGGEST + TOMBOL EXPORT ---
      let reply = `🔎 *Group(s) found for "${groupKey}":*\n`;
      for (const g of groups) {
        reply += `• *${g.group_name}* (${g.customer_count} customers)\n`;
        reply += `  _Export:_ /report ${fmt} group="${g.group_name}" start_date=${params.start_date} end_date=${params.end_date}\n`;
      }
      reply += `\nKlik/salin command di atas untuk export report sesuai group.`;
      await msg.reply(reply);
      return;
    } catch (err) {
      logger.error('Group suggest error: ' + err);
      await msg.reply(header('ERROR') + 'Group query failed.' + footer());
      return;
    }
  }

  // JIKA TIDAK ADA PARAMS GROUP ATAU TIDAK MASUK LOGIC ATAS, LANJUT EXPORT BIASA
  await msg.reply(`⏳ Generating ${fmt.toUpperCase()} report…`);
  try {
    const media = await fetchReport(fmt, params);
    await client.sendMessage(msg.from, media, {
      caption: `📊 Report (${params.start_date} to ${params.end_date})`
    });
    logger.info(`Report sent to ${msg.from}`);
  } catch (err) {
    logger.error(`Report error: ${err}`);
    await msg.reply(header('REPORT FAILED') + err.message + footer());
  }
  return;
}

if (command === '/groups') {
  try {
    const res = await fetchWithAuth(`${LARAVEL_BASE}/api/groups`);
    const groups = await res.json();
    if (!groups.length) {
      await msg.reply(header('GROUPS') + 'No customer groups found.' + footer());
      return;
    }
    let reply = '*Customer Groups:*\n';
    groups.forEach(g => {
      reply += `• *${g.group_name}* (${g.customer_count} customers)\n`;
    });
    await msg.reply(reply);
  } catch (err) {
    logger.error('Groups command error: ' + err);
    await msg.reply(header('ERROR') + 'Unable to fetch group list.' + footer());
  }
  return;
}
        // /rfo <ticket_id>
    if (command === '/rfo' && parts[1]) {
      const ticketId = parts[1];
      await msg.reply(`⏳ Fetching RFO PDF for Ticket #${ticketId}…`);
      try {
        const media = await fetchRfoPdf(ticketId);
        await client.sendMessage(msg.from, media, { caption: `📄 RFO Ticket #${ticketId}` });
        logger.info(`RFO PDF sent to ${msg.from}`);
      } catch (err) {
        logger.error(`RFO PDF error: ${err}`);
        await msg.reply(header('FAILED TO GET RFO PDF') + err.message + footer());
      }
      return;
    }

    // /quickreport
    if (command === '/quickreport') {
      const today = new Date().toISOString().split('T')[0];
      await msg.reply(`⏳ Generating daily report for ${today}…`);
      try {
        const media = await fetchReport('pdf', { start_date: today, end_date: today });
        await client.sendMessage(msg.from, media, { caption: `📊 Daily Report - ${today}` });
        logger.info(`Daily report sent to ${msg.from}`);
      } catch (err) {
        logger.error(`Quickreport error: ${err}`);
        await msg.reply(header('REPORT FAILED') + err.message + footer());
      }
      return;
    }

if (command === '/openticket') {
  // Jika hanya /openticket tanpa parameter
  if (parts.length === 1) {
    await msg.reply(
      `📝 *Cara membuka tiket baru:*\n\n` +
      `/openticket <customer_id/nama> <issue_type> <deskripsi optional>\n\n` +
      `Contoh:\n` +
      `/openticket 123 Fiber Putus Link drop dari jam 12 malam\n` +
      `/openticket Jaringan Fiber gangguan sejak pagi\n\n` +
      `Ketik nama customer bisa sebagian saja atau nama lengkap. Jika tidak ketemu, bot akan menyarankan yang mirip.`
    );
    return;
  }
  // Jika parameter kurang dari 2 (kurang lengkap)
  if (parts.length < 3) {
    await msg.reply(
      `❗ *Format salah!*\n\n` +
      `Ketik:\n/openticket <customer_id/nama> <issue_type> <deskripsi optional>\n` +
      `Contoh:\n/openticket 123 Fiber Putus\n/openticket Jaringan Putus total sejak pagi`
    );
    return;
  }

  // Handler jika semua parameter tersedia (minimal customer dan issue_type)
  const customer_input = parts[1];
  const issue_type = parts[2];
  const problem_detail = parts.slice(3).join(' ') || '';

  await msg.reply('⏳ Membuka tiket, harap tunggu...');

  try {
    const resp = await fetchWithAuth(`${LARAVEL_BASE}/api/tickets/open-wabot`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        customer_id: customer_input,
        issue_type,
        problem_detail,
        user_name: msg._data?.notifyName || msg._data?.pushname || '',
      })
    });

    if (resp.status === 404) {
      const data = await resp.json();
      if (data.suggestions && Object.keys(data.suggestions).length) {
        let reply = '❗ *Customer tidak ditemukan.*\n';
        reply += data.message + '\n\n';
        let i = 1;
        Object.entries(data.suggestions).forEach(([id, name]) => {
          reply += `${i++}. ${name} (ID: ${id})\n`;
        });
        reply += `\n*Balas ulang dengan salah satu ID customer di atas:*\n/openticket <ID> <issue_type> <detail>`;
        await msg.reply(reply);
      } else {
        await msg.reply('❗ Customer tidak ditemukan dan tidak ada yang mirip.');
      }
      return;
    }

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    const data = await resp.json();

    await msg.reply(
      `✅ Ticket berhasil dibuat: #${data.ticket_number}\n` +
      `Customer: ${customer_input}\nIssue: ${issue_type}`
    );
  } catch (err) {
    logger.error(`[WA] Failed open ticket: ${err}`);
    await msg.reply('❌ Gagal membuka tiket. Silakan cek format atau coba lagi.');
  }
  return;
}
    // Unknown command
    if (command.startsWith('/')) {
      await msg.reply(header('UNKNOWN COMMAND') + 'Type /help for commands.' + footer());
    }

  } catch (err) {
    logger.error(`Message handler error: ${err}`);
  }
});

// ————— Start Server —————
app.listen(PORT, () => {
  logger.info(`🚀 WhatsApp‑Bot running on port ${PORT}`);
  logger.info(`Configuration: CLIENT_ID=${CLIENT_ID}, BASE=${LARAVEL_BASE}, TIMEZONE=${TIMEZONE}, LOG_LEVEL=${LOG_LEVEL}`);
});

process.on('unhandledRejection', err => logger.error(`Unhandled Rejection: ${err}`));
process.on('uncaughtException',   err => { logger.error(`Uncaught Exception: ${err}`); process.exit(1); });
