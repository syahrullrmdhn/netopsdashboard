<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RFO Report - {{ $ticket->ticket_number }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Alex+Brush&display=swap" rel="stylesheet">
  <style>
    /* Base Styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      font-size: 12px;
      color: #333333;
      line-height: 1.6;
      background-color: #ffffff;
      padding: 20px;
      -webkit-font-smoothing: antialiased;
    }
    
    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 30px;
      background-color: white;
    }
    
    /* Header Section */
    .company-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid #eaeaea;
    }
    
    .company-logo {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .company-name {
      font-size: 18px;
      font-weight: 600;
      color: #1a2b50;
      letter-spacing: 0.5px;
    }
    
    .company-sub {
      font-size: 12px;
      color: #6b7280;
      font-weight: 400;
      margin-top: 2px;
    }
    
    .report-meta {
      text-align: right;
    }
    
    .report-type {
      font-weight: 600;
      font-size: 12px;
      color: #1a2b50;
    }
    
    .report-date {
      font-size: 10px;
      color: #6b7280;
      font-weight: 400;
      margin-top: 2px;
    }
    
    /* Title Section */
    .report-title {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .report-title h1 {
      font-size: 22px;
      color: #111827;
      margin-bottom: 6px;
      font-weight: 600;
      letter-spacing: 0.3px;
    }
    
    .report-subtitle {
      color: #6b7280;
      font-size: 12px;
      font-weight: 400;
    }
    
    /* Grid Layout */
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 12px;
      margin-bottom: 20px;
    }
    
    .grid-item dt {
      font-weight: 500;
      color: #4b5563;
      margin-bottom: 4px;
      font-size: 11px;
      letter-spacing: 0.2px;
    }
    
    .grid-item dd {
      margin-left: 0;
      margin-bottom: 10px;
      font-size: 11px;
      font-weight: 400;
      color: #111827;
    }
    
    /* Content Sections */
    h2 {
      font-size: 16px;
      color: #1a2b50;
      margin-top: 25px;
      margin-bottom: 12px;
      padding-bottom: 6px;
      border-bottom: 1px solid #eaeaea;
      font-weight: 600;
      letter-spacing: 0.3px;
    }
    
    .content {
      margin-bottom: 20px;
      font-size: 11px;
      line-height: 1.7;
      font-weight: 400;
      color: #374151;
    }
    
    /* Chronology */
    .chronology-item {
      padding-left: 14px;
      border-left: 2px solid #3b82f6;
      margin-bottom: 14px;
      page-break-inside: avoid;
    }
    
    .chronology-meta {
      font-size: 10px;
      color: #6b7280;
      display: flex;
      justify-content: space-between;
      margin-bottom: 4px;
      font-weight: 400;
    }
    
    /* Verification Section */
    .verification {
      margin-top: 50px;
      display: flex;
      justify-content: flex-end;
    }
    
    .signature {
      text-align: center;
      width: 250px;
    }
    
    .signature-line {
      height: 50px;
      margin: 5px 0 10px;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='50' viewBox='0 0 200 50'%3E%3Cpath fill='none' stroke='%23333' stroke-width='1' d='M5,25 Q30,5 55,25 T105,25 T155,25 T200,25'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: center;
      background-size: contain;
    }
    
    .signature-name {
      font-family: 'Alex Brush', cursive;
      font-size: 24px;
      color: #111827;
      margin-top: -5px;
    }
    
    .signature-title {
      font-family: 'Poppins', sans-serif;
      font-size: 10px;
      color: #6b7280;
      font-weight: 400;
      margin-top: -5px;
    }
    
    /* Footer */
    .footer {
      margin-top: 40px;
      padding-top: 15px;
      border-top: 1px solid #eaeaea;
      font-size: 10px;
      color: #9ca3af;
      text-align: center;
      font-weight: 400;
    }
    
    @page {
      margin: 1cm;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Company Header -->
    <div class="company-header">
      <div class="company-logo">
        <img src="{{ public_path('images/android-chrome-512x512.png') }}" alt="Company Logo" style="width: 36px; height: 36px; object-fit: contain;">
        <div>
          <div class="company-name">PT Abhinawa Sumberdaya Asia</div>
          <div class="company-sub">Div. Network & Operations</div>
        </div>
      </div>
      <div class="report-meta">
        <div class="report-type">RFO Report</div>
        <div class="report-date">{{ now()->format('d F Y H:i') }}</div>
      </div>
    </div>

    <!-- Report Title -->
    <div class="report-title">
      <h1>Reason For Outage Report</h1>
      <div class="report-subtitle">Incident Ticket #{{ $ticket->ticket_number }}</div>
    </div>

    <!-- Incident Details -->
    <h2>Incident Information</h2>
    <div class="grid">
      <div class="grid-item">
        <dt>Customer</dt>
        <dd>{{ $ticket->customer->customer }} ({{ $ticket->customer->cid_abh }})</dd>
      </div>
      <div class="grid-item">
        <dt>ABH Ticket</dt>
        <dd>{{ $ticket->ticket_number }}</dd>
      </div>
      <div class="grid-item">
        <dt>Issue Type</dt>
        <dd>{{ $ticket->issue_type }}</dd>
      </div>
      <div class="grid-item">
        <dt>Duration</dt>
        <dd>
          @if($ticket->start_time && $ticket->end_time)
            {{ $ticket->start_time->format('d/m/Y H:i') }} - {{ $ticket->end_time->format('d/m/Y H:i') }}
            ({{ $ticket->end_time->diffInMinutes($ticket->start_time) }} minutes)
          @else
            Ongoing
          @endif
        </dd>
      </div>
    </div>

    <!-- Content Sections -->
    <h2>Root Cause Analysis</h2>
    <div class="content">
      {{ $ticket->problem_detail ?: 'No details provided.' }}
    </div>

    <h2>Corrective Actions Taken</h2>
    <div class="content">
      {{ $ticket->action_taken ?: 'No details provided.' }}
    </div>

    <h2>Preventive Measures</h2>
    <div class="content">
      {{ $ticket->preventive_action ?: 'No details provided.' }}
    </div>

    <h2>Incident Chronology</h2>
    <div class="chronology">
      @foreach($ticket->updates as $u)
        <div class="chronology-item">
          <div class="chronology-meta">
            <span>{{ $u->created_at->format('d/m/Y H:i') }}</span>
            <span>by Abhinawa NOC</span>
          </div>
          <div class="content">
            {{ $u->detail }}
          </div>
        </div>
      @endforeach
    </div>

    <!-- Verification Section -->
    <div class="verification">
      <div class="signature">
        <div style="margin-bottom: 15px;">Verified by,</div>
        <div class="signature-line"></div>
        <div class="signature-name">Supyar Daulay</div>
        <div class="signature-title">Head of Network & Operations</div>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p>This report was automatically generated by Abhinawa Network Ticketing System</p>
      <p>© {{ now()->year }} PT Abhinawa Sumberdaya Asia. All rights reserved.</p>
    </div>
  </div>
</body>
</html>