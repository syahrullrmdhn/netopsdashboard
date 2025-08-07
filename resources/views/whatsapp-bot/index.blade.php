{{-- resources/views/whatsapp-bot/index.blade.php --}}
@extends('layouts.app')

@section('title', 'WhatsApp Bot')

@section('content')
<div class="max-w-3xl mx-auto py-8 space-y-8">

  {{-- Connection Card --}}
  <div class="bg-white p-6 rounded-lg shadow space-y-4">
    <h2 class="text-lg font-medium">Connection</h2>
    <div class="flex items-center gap-4">
      <span id="wa-status" class="px-3 py-1 rounded-full bg-gray-200 text-gray-700">Loading…</span>
      <div id="qr-code" class="w-32 h-32 bg-gray-100 flex items-center justify-center"></div>
    </div>
  </div>

  {{-- Send Test Message --}}
  <div class="bg-white p-6 rounded-lg shadow space-y-4">
    <h2 class="text-lg font-medium">Send Test Message</h2>
    <form id="wa-form" class="flex gap-2">
      <input
        id="wa-to"
        type="text"
        placeholder="6281234...@c.us"
        class="flex-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
      >
      <input
        id="wa-msg"
        type="text"
        placeholder="Hello!"
        class="flex-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
      >
      <button
        type="submit"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
      >
        Send
      </button>
    </form>
    <p id="wa-result" class="text-sm text-gray-700"></p>
  </div>
</div>

{{-- QRCode.js for data-URI fallback (not used for generation) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
  async function pollSession() {
    try {
      // Absolute URL to Laravel endpoint
      const res = await fetch("{{ url('whatsapp-bot/session') }}");
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const obj = await res.json();
      console.log('session:', obj);

      // Update status badge
      const st = document.getElementById('wa-status');
      st.textContent = obj.connected ? 'Connected' : 'Disconnected';
      st.className = obj.connected
        ? 'px-3 py-1 rounded-full bg-green-100 text-green-800'
        : 'px-3 py-1 rounded-full bg-red-100 text-red-800';

      // Render QR as <img> if not connected
      const qrC = document.getElementById('qr-code');
      qrC.innerHTML = '';
      if (!obj.connected && obj.qr) {
        qrC.innerHTML = `
          <img
            src="${obj.qr}"
            alt="WhatsApp QR code"
            class="w-full h-full object-contain"
          />
        `;
      }
    } catch (e) {
      console.error('pollSession error:', e);
    }
  }

  // Poll every 3 seconds
  setInterval(pollSession, 3000);
  pollSession();

  // Send test message handler
  document.getElementById('wa-form').addEventListener('submit', async e => {
    e.preventDefault();

    const to  = document.getElementById('wa-to').value.trim();
    const msg = document.getElementById('wa-msg').value.trim();
    if (!to || !msg) return;

    try {
      const res = await fetch("{{ url('whatsapp-bot/send') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ to, message: msg })
      });

      const json = await res.json();
      document.getElementById('wa-result').textContent = json.success
        ? '✅ Sent!'
        : '❌ Error: ' + (json.error || JSON.stringify(json));
    } catch (err) {
      console.error('send error:', err);
      document.getElementById('wa-result').textContent = '❌ Failed to send';
    }
  });
</script>
@endsection
