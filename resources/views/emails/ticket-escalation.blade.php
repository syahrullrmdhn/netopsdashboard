<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body>
  <p>Dear {{ $level->name }},</p>

  <p>Mohon bantuannya untuk issue berikut:</p>
  <ul>
    <li><strong>Ticket #</strong> {{ $ticket->ticket_number }}</li>
    <li><strong>Open Date</strong> {{ $ticket->open_date->format('d M Y H:i') }}</li>
    <li><strong>Issue Type</strong> {{ $ticket->issue_type }}</li>
  </ul>

  <p><strong>Chronology:</strong></p>
  <ul>
    @foreach($ticket->updates as $u)
      <li>{{ $u->created_at->format('d M Y H:i') }} – {{ $u->detail }}</li>
    @endforeach
  </ul>

  <p>Terima kasih,<br>{{ $user->name }}</p>
</body>
</html>
