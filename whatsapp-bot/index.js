
// index.js
import dotenv from 'dotenv';
dotenv.config();

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

// ————— Main Message Handler —————
client.on('message', async msg => {
  try {
    if (msg.from === 'status@broadcast' || msg.hasMedia) return;

    const body    = msg.body.trim();
    const lower   = body.toLowerCase();
    const parts   = body.split(/\s+/);
    const command = parts[0].toLowerCase();

    logger.info(`Received from ${msg.from}: ${body}`);

    // /help
    if (command === '/help') {
      await msg.reply([
        header('NOC COMMAND CENTER'),
        '📋 Basic: /help, /onduty, /time',
        '🎫 Tickets: /open, /status <#>, /chronology <#>',
        '📅 Shifts: /handoverhistory [date]',
        '📊 Reports: /report excel|pdf start_date=… end_date=…, /quickreport',
        section('Support Contacts',
          `${NOC_CONTACTS.SPV}\n${NOC_CONTACTS.MANAGER}\n${NOC_CONTACTS.SYSADMIN}`),
        footer()
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
          resp += item('Last Update', t.last_update) + '\n';
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
          await msg.reply(header('HANDOVER HISTORY') + `No logs for ${date||'today'}.` + footer());
          return;
        }
        let resp = header(`HISTORY ${date||'(today)'}`) + `Total: ${logs.length}\n\n`;
        logs.forEach(l => {
          const issues = l.issues.replace(/<br\s*\/?>/g,'\n      ').replace(/<[^>]+>/g,'');
          const notes  = (l.notes||'').replace(/<[^>]+>/g,'') || '—';
          resp += `🔄 *${l.shift} Shift Handover*\n`;
          resp += item('Time',      l.timestamp);
          resp += item('From',      l.from);
          resp += item('To',        l.to);
          resp += section('Issues', issues);
          resp += section('Additional Notes', notes) + '\n';
        });
        resp += footer();
        await msg.reply(resp);
      } catch (err) {
        logger.error(`Handover history error: ${err}`);
        await msg.reply(header('ERROR') + 'Unable to fetch handover logs.' + footer());
      }
      return;
    }

    // /report excel|pdf key=val ...
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
