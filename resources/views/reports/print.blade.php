<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Laporan Tiket Gangguan (Print)</title>
  <style>
    body { font-family: sans-serif; margin: 1rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { border: 1px solid #444; padding: 0.5rem; text-align: left; }
    th { background: #eee; }
  </style>
</head>
<body>
  <h1>Laporan Verifikasi Tiket Gangguan</h1>
  <p><strong>Periode:</strong>
    {{ $from->format('d M Y') }}
    –
    {{ $to->format('d M Y') }}
  </p>

  <table>
    <thead>
      <tr>
        <th>Ticket #</th><th>Open Date</th><th>Opened By</th><th>Customer</th>
        <th>CID Customer</th><th>Start Time</th><th>End Time</th>
        <th>Issue</th><th>Service Detail</th><th>Alert</th>
      </tr>
    </thead>
    <tbody>
      @foreach($tickets as $t)
      <tr>
        <td>{{ $t->ticket_number }}</td>
        <td>{{ $t->open_date->format('Y-m-d H:i') }}</td>
        <td>{{ optional($t->user)->name }}</td>
        <td>{{ optional($t->customer)->customer }}</td>
        <td>{{ optional($t->customer)->cid_abh }}</td>
        <td>{{ optional($t->start_time)?->format('Y-m-d H:i') ?? '-' }}</td>
        <td>{{ optional($t->end_time)?->format('Y-m-d H:i') ?? '-' }}</td>
        <td>{{ $t->issue_type }}</td>
        <td>{{ $t->service_detail }}</td>
        <td>{{ $t->alert ? 'Yes' : 'No' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <script>
    // Otomatis panggil print dialog
    window.onload = function() { window.print(); }
  </script>
</body>
</html>
