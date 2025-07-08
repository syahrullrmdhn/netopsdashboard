<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>RFO – {{ $ticket->ticket_number }}</title>
  <style>
    body { font-family: sans-serif; font-size:12px; color:#333; margin:20px; line-height:1.5 }
    h1 { text-align:center; margin-bottom:8px; font-size:18px }
    h2 { margin-top:24px; margin-bottom:4px; font-size:14px; border-bottom:1px solid #ccc; padding-bottom:2px }
    .meta dl { display:grid; grid-template-columns: max-content 1fr; gap:4px 12px; }
    .meta dt { font-weight:600; color:#555 }
    .meta dd { margin:0 }
    .badge { display:inline-block; padding:2px 6px; font-size:10px; border-radius:4px; color:#fff }
    .badge--open     { background:#f0ad4e }
    .badge--resolved { background:#5cb85c }
    .content { background:#fafafa; border-left:4px solid #0074d9; padding:8px; white-space:pre-wrap }
    .chronology { margin-top:12px; }
    .chronology-item { margin-bottom:12px; }
    .chronology-meta { font-size:10px; color:#666; margin-bottom:4px; display:flex; justify-content:space-between }
    .footer { text-align:center; font-size:10px; color:#999; margin-top:30px }
  </style>
</head>
<body>

  <h1>Reason For Outage Report</h1>

  <div class="meta">
    <dl>
      <dt>Ticket #</dt>
        <dd>{{ $ticket->ticket_number }}</dd>
      <dt>Customer</dt>
        <dd>
          {{ optional($ticket->customer)->customer }}
          ({{ optional($ticket->customer)->cid_abh }})
        </dd>
      <dt>Issue Type</dt>
        <dd>{{ $ticket->issue_type }}</dd>
      <dt>Opened</dt>
        <dd>{{ optional($ticket->start_time)->format('d M Y H:i') }}</dd>
      <dt>Closed</dt>
        <dd>
          @if($ticket->end_time)
            {{ $ticket->end_time->format('d M Y H:i') }}
          @else
            <span class="badge badge--open">Ongoing</span>
          @endif
        </dd>
      <dt>Duration</dt>
        <dd>
          @if($ticket->start_time && $ticket->end_time)
            {{ $ticket->end_time->diffInMinutes($ticket->start_time) }} minutes
            <span class="badge badge--resolved">Resolved</span>
          @else
            &mdash;
          @endif
        </dd>
    </dl>
  </div>

  <h2>Root Cause Analysis</h2>
  <div class="content">
    @if($ticket->problem_detail)
      {!! nl2br(e($ticket->problem_detail)) !!}
    @else
      <em>No details provided.</em>
    @endif
  </div>

  <h2>Corrective Actions Taken</h2>
  <div class="content">
    @if($ticket->action_taken)
      {!! nl2br(e($ticket->action_taken)) !!}
    @else
      <em>No details provided.</em>
    @endif
  </div>

  <h2>Preventive Measures</h2>
  <div class="content">
    @if($ticket->preventive_action)
      {!! nl2br(e($ticket->preventive_action)) !!}
    @else
      <em>No details provided.</em>
    @endif
  </div>

  <h2>Incident Chronology</h2>
  @if($ticket->updates->isEmpty())
    <p><em>No chronology entries.</em></p>
  @else
    <div class="chronology">
      @foreach($ticket->updates as $u)
        <div class="chronology-item">
          <div class="chronology-meta">
            <span>{{ $u->created_at->format('d M Y H:i') }}</span>
            <span>by {{ optional($u->user)->name ?? 'System' }}</span>
          </div>
          <div class="content">{!! nl2br(e($u->detail)) !!}</div>
        </div>
      @endforeach
    </div>
  @endif

  <div class="footer">
    Generated on {{ now()->format('d M Y H:i') }} by {{ auth()->user()->name }}
  </div>
</body>
</html>
