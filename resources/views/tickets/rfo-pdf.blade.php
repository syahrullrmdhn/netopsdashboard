<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Post Incident Report – {{ $ticket->ticket_number }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    @page { margin: 22mm 16mm 22mm 16mm; }
    body {
      font-family: 'Poppins', Arial, sans-serif;
      font-size: 14px;
      color: #222b45;
      background: #fff;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 820px;
      margin: 0 auto;
      background: #fff;
      padding: 0;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #e5e7eb;
      padding-bottom: 14px;
      margin-bottom: 24px;
    }
    .header-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .header-logo {
      height: 48px;
      width: 48px;
      object-fit: contain;
    }
    .header-title {
      font-size: 22px;
      font-weight: 700;
      color: #222b45;
      margin-bottom: 3px;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .header-subtitle {
      font-size: 13px;
      color: #6b7280;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .header-date {
      text-align: right;
    }
    .header-date-label {
      font-size: 14px;
      font-weight: 600;
      color: #4b5563;
    }
    .header-date-value {
      font-size: 12px;
      color: #6b7280;
    }
    .report-title-block {
      text-align: center;
      margin-bottom: 18px;
    }
    .report-title {
      font-size: 26px;
      font-weight: 700;
      color: #222b45;
      margin: 0;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .report-ticket {
      margin-top: 7px;
      color: #6b7280;
      font-size: 14px;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .info-section {
      border-bottom: 2px solid #e5e7eb;
      padding-bottom: 14px;
      margin-bottom: 18px;
    }
    .info-section-title {
      font-size: 18px;
      font-weight: 700;
      color: #374151;
      margin-bottom: 10px;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .info-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 6px;
    }
    .info-table td {
      font-size: 13.4px;
      vertical-align: top;
      padding: 3px 0;
    }
    .info-label {
      color: #6b7280;
      font-weight: 600;
      font-size: 13px;
      width: 115px;
      padding-right: 8px;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .info-value {
      color: #222b45;
      font-weight: 700;
      font-size: 14px;
      padding-right: 24px;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .section {
      border-bottom: 1.5px solid #e5e7eb;
      padding-bottom: 10px;
      margin-bottom: 18px;
    }
    .section-title {
      font-size: 16px;
      font-weight: 700;
      color: #374151;
      margin-bottom: 5px;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .section-content {
      color: #222b45;
      font-size: 14px;
      white-space: pre-line;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .chronology-section {
      margin-bottom: 28px;
    }
    .chronology-title {
      font-size: 16px;
      font-weight: 700;
      color: #374151;
      margin-bottom: 8px;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .chronology-list {
      list-style: none;
      padding-left: 0;
    }
    .chronology-item {
      border-left: 3px solid #3b82f6;
      margin-bottom: 15px;
      padding-left: 14px;
    }
    .chronology-meta {
      display: flex;
      justify-content: space-between;
      font-size: 12.5px;
      color: #6b7280;
      margin-bottom: 2px;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .chronology-detail {
      font-size: 14px;
      color: #222b45;
      font-family: 'Poppins', Arial, sans-serif;
    }
    .footer {
      border-top: 1px solid #e5e7eb;
      font-size: 12.5px;
      color: #6b7280;
      text-align: center;
      padding-top: 13px;
      margin-top: 38px;
      font-family: 'Poppins', Arial, sans-serif;
    }
  </style>
</head>
<body>
  <div class="container">

    <!-- HEADER -->
<div class="header" style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #e5e7eb;padding-bottom:10px;margin-bottom:24px;">
  <div style="display:flex;align-items:center;gap:13px;">
    <img src="{{ public_path('images/android-chrome-192x192.png') }}" alt="Logo" style="height:42px;width:42px;object-fit:contain;margin:0;padding:0;">
    <div>
      <div style="font-size:22px;font-weight:700;color:#222b45;font-family:'Poppins',Arial,sans-serif;margin:0;line-height:1.1;">PT. Abhinawa Sumberdaya Asia</div>
      <div style="font-size:14px;color:#6b7280;font-family:'Poppins',Arial,sans-serif;margin-top:2px;">Div. Network Operations Center</div>
    </div>
  </div>
  <div style="text-align:right;">
    <div style="font-size:14px;font-weight:600;color:#4b5563;">IR Created</div>
    <div style="font-size:12px;color:#6b7280;">{{ now()->format('d F Y H:i') }}</div>
  </div>
</div>


    <!-- REPORT TITLE -->
    <div class="report-title-block">
      <div class="report-title">Post Incident Report</div>
      <div class="report-ticket">
        Incident Ticket {{ $ticket->ticket_number }}
      </div>
    </div>

    <!-- INCIDENT INFO -->
    <div class="info-section">
      <div class="info-section-title">Incident Information</div>
      <table class="info-table">
        <tr>
          <td class="info-label">Open Date</td>
          <td class="info-value">{{ $ticket->open_date }}</td>
          <td class="info-label">Customer</td>
          <td class="info-value">{{ $ticket->customer->customer ?? '-' }} ({{ $ticket->customer->cid_abh ?? '-' }})</td>
        </tr>
        <tr>
          <td class="info-label">ABH Ticket</td>
          <td class="info-value">{{ $ticket->ticket_number }}</td>
          <td class="info-label">Type of Issue</td>
          <td class="info-value">{{ $ticket->issue_type }}</td>
        </tr>
        <tr>
          <td class="info-label">Start Time</td>
          <td class="info-value">{{ $ticket->start_time ?? '-' }}</td>
          <td class="info-label">End Time</td>
          <td class="info-value">{{ $ticket->end_time ?? '-' }}</td>
        </tr>
        <tr>
          <td class="info-label">MTTR</td>
          <td class="info-value">
            @if($ticket->end_time && $ticket->open_date)
              {{ $ticket->end_time->format('d/m/Y H:i') }} - {{ $ticket->open_date->format('d/m/Y H:i') }}
              ({{ $ticket->end_time->diffInMinutes($ticket->open_date) }} minutes)
            @else
              Ongoing
            @endif
          </td>
          <td class="info-label">Duration</td>
          <td class="info-value">
            @if($ticket->start_time && $ticket->end_time)
              {{ $ticket->start_time->format('d/m/Y H:i') }} - {{ $ticket->end_time->format('d/m/Y H:i') }}
              ({{ $ticket->end_time->diffInMinutes($ticket->start_time) }} minutes)
            @else
              Ongoing
            @endif
          </td>
        </tr>
      </table>
    </div>

    <!-- ROOT CAUSE -->
    <div class="section">
      <div class="section-title">Root Cause Analysis</div>
      <div class="section-content">{{ $ticket->problem_detail }}</div>
    </div>

    <!-- ACTION TAKEN -->
    <div class="section">
      <div class="section-title">Corrective Actions</div>
      <div class="section-content">{{ $ticket->action_taken }}</div>
    </div>

    <!-- PREVENTIVE -->
    <div class="section">
      <div class="section-title">Preventive & Improvement</div>
      <div class="section-content">{{ $ticket->preventive_action }}</div>
    </div>

    <!-- CHRONOLOGY -->
    <div class="chronology-section">
      <div class="chronology-title">Incident Chronology</div>
      <ul class="chronology-list">
        @foreach($ticket->updates as $u)
        <li class="chronology-item">
          <div class="chronology-meta">
            <span>{{ $u->created_at->format('d/m/Y H:i') }}</span>
            <span>by Abhinawa NOC</span>
          </div>
          <div class="chronology-detail">{{ $u->detail ?? 'No Detail Provided' }}</div>
        </li>
        @endforeach
      </ul>
    </div>

    <!-- FOOTER -->
    <div class="footer">
      This report was automatically generated by Abhinawa Network Operations Center<br>
      © {{ now()->year }} PT Abhinawa Sumberdaya Asia. All rights reserved.
    </div>
  </div>
</body>
</html>
