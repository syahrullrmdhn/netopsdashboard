<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Trouble Ticket Report {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600&family=Playfair+Display:wght@500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    /* Base Styles */
    @page {
      size: A4 landscape;
      margin: 1.5cm;
      @bottom-center {
        content: "Page " counter(page) " of " counter(pages);
        font-family: 'Montserrat', sans-serif;
        font-size: 8pt;
        color: #6b7280;
      }
    }
    
    body {
      font-family: 'Roboto', sans-serif;
      font-size: 10pt;
      line-height: 1.6;
      color: #374151;
      background-color: #fff;
      -webkit-font-smoothing: antialiased;
    }
    
    /* Cover Page */
    .cover-page {
      page: cover;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      background: linear-gradient(135deg, #f8fafc 0%, #e5e7eb 100%);
      border: 1px solid #e5e7eb;
    }
    
    .cover-logo {
      width: 120px;
      height: 120px;
      margin-bottom: 2rem;
    }
    
    .cover-title {
      font-family: 'Playfair Display', serif;
      font-size: 2.2rem;
      font-weight: 700;
      color: #1a2b48;
      margin-bottom: 1rem;
      line-height: 1.2;
    }
    
    .cover-subtitle {
      font-family: 'Montserrat', sans-serif;
      font-size: 1rem;
      color: #6b7280;
      margin-bottom: 3rem;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    
    .cover-period {
      font-family: 'Montserrat', sans-serif;
      font-size: 1.1rem;
      color: #1a2b48;
      margin-bottom: 3rem;
      padding: 1rem 2rem;
      background-color: rgba(255,255,255,0.8);
      border-radius: 4px;
      display: inline-block;
    }
    
    .cover-footer {
      position: absolute;
      bottom: 3cm;
      width: 100%;
      font-family: 'Montserrat', sans-serif;
      font-size: 0.8rem;
      color: #6b7280;
    }
    
    @page cover {
      background: linear-gradient(135deg, #f8fafc 0%, #e5e7eb 100%);
      margin: 0;
    }
    
    /* Header Styles */
    .header {
      text-align: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #e5e7eb;
    }
    
    .logo {
      font-family: 'Playfair Display', serif;
      font-size: 1.8rem;
      font-weight: 600;
      color: #1a2b48;
      margin-bottom: 0.3rem;
      letter-spacing: -0.5px;
    }
    
    .company-name {
      font-family: 'Montserrat', sans-serif;
      font-size: 0.9rem;
      font-weight: 500;
      margin-bottom: 0.3rem;
      color: #1f2937;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }
    
    .address {
      font-family: 'Montserrat', sans-serif;
      font-size: 0.75rem;
      color: #6b7280;
      max-width: 75%;
      margin: 0 auto;
      line-height: 1.4;
    }
    
    /* Report Title */
    .report-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.6rem;
      font-weight: 600;
      text-align: center;
      margin: 1rem 0 0.5rem;
      color: #1a2b48;
    }
    
    .report-period {
      font-family: 'Montserrat', sans-serif;
      font-size: 0.9rem;
      color: #6b7280;
      text-align: center;
      margin-bottom: 2rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* Customer Section */
    .customer-section {
      margin-bottom: 2.5rem;
      page-break-inside: avoid;
    }
    
    .customer-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 1rem;
      font-weight: 600;
      color: #1a2b48;
      margin-bottom: 1rem;
      background-color: #f8fafc;
      padding: 0.8rem 1.2rem;
      border-radius: 4px;
      border-left: 4px solid #1a2b48;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* Table Styles */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1.5rem;
      page-break-inside: avoid;
      table-layout: fixed;
    }
    
    th {
      background-color: #1a2b48;
      color: #fff;
      font-family: 'Montserrat', sans-serif;
      font-weight: 500;
      text-align: left;
      padding: 0.8rem 0.6rem;
      border: 1px solid #e5e7eb;
      font-size: 0.8rem;
      vertical-align: middle;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    td {
      padding: 0.8rem 0.6rem;
      border: 1px solid #e5e7eb;
      font-size: 0.9rem;
      vertical-align: top;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    
    .text-center {
      text-align: center;
    }
    
    /* Column Styles */
    .col-no {
      width: 3%;
    }
    
    .col-ticket {
      width: 8%;
    }
    
    .col-supplier {
      width: 6%;
    }
    
    .col-name {
      width: 10%;
    }
    
    .col-issue {
      width: 7%;
    }
    
    .col-time {
      width: 7%;
    }
    
    .col-duration {
      width: 5%;
    }
    
    .col-cause {
      width: 22%;
    }
    
    .col-action {
      width: 22%;
    }
    
    /* No Data Row */
    .no-data {
      text-align: center;
      color: #9ca3af;
      font-style: italic;
      padding: 1.5rem;
      background-color: #f9fafb;
      font-family: 'Montserrat', sans-serif;
    }
    
    /* Zebra Striping */
    tbody tr:nth-child(even) {
      background-color: #f9fafb;
    }
    
    /* Hover Effect */
    tbody tr:hover {
      background-color: #f3f4f6;
    }
    
    /* Footer */
    .footer {
      font-family: 'Montserrat', sans-serif;
      font-size: 0.8rem;
      color: #6b7280;
      text-align: center;
      margin-top: 2rem;
      padding-top: 1rem;
      border-top: 1px solid #e5e7eb;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* Multi-line cell content */
    .multiline {
      white-space: pre-wrap;
      line-height: 1.5;
      padding-top: 0.8rem;
      padding-bottom: 0.8rem;
    }
    
    /* Compact view for empty cells */
    .empty-cell {
      color: #9ca3af;
      font-style: italic;
      font-family: 'Montserrat', sans-serif;
    }
    
    /* Status indicators */
    .status-indicator {
      display: inline-block;
      padding: 0.2rem 0.6rem;
      border-radius: 12px;
      font-size: 0.7rem;
      font-weight: 500;
      font-family: 'Montserrat', sans-serif;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      margin-top: 0.3rem;
    }
    
    .status-open {
      background-color: #fef3c7;
      color: #92400e;
    }
    
    .status-resolved {
      background-color: #d1fae5;
      color: #065f46;
    }
    
    /* Date formatting */
    .date-cell {
      font-family: 'Montserrat', sans-serif;
      font-size: 0.8rem;
    }
    
    /* First page after cover */
    .first-page {
      page: first;
    }
  </style>
</head>
<body>
  <!-- Cover Page -->
  <div class="cover-page">
    <img src="{{ public_path('images/company-logo.png') }}" alt="Company Logo" class="cover-logo">
    <h1 class="cover-title">Network Incident Verification Report</h1>
    <div class="cover-subtitle">Detailed Incident Analysis</div>
    <div class="cover-period">
      {{ $from->format('F j, Y') }} to {{ $to->format('F j, Y') }}
    </div>
    <div class="cover-footer">
      Confidential • Prepared for {{ auth()->user()->company ?? 'Internal Review' }} • Generated on {{ now()->format('F j, Y') }}
    </div>
  </div>

  <!-- Report Content -->
  <div class="first-page">
    <div class="header">
      <div class="logo">abhinawa</div>
      <div class="company-name">PT. Abhinawa Sumberdaya Asia</div>
      <div class="address">
        Head Office: Menara Kadin Indonesia, Jl. H. R. Rasuna Said, RT.1/RW.2, Kuningan, Kuningan Tim.,<br>
        Kecamatan Setiabudi, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12950
      </div>
    </div>

    <h1 class="report-title">Network Incident Verification</h1>
    <div class="report-period">
      Detailed Report for Period: {{ $from->format('F j, Y') }} to {{ $to->format('F j, Y') }}
    </div>

    @foreach($customers as $cust)
      @php
        $custTickets = $ticketsByCust->get($cust->id, collect());
      @endphp

      <div class="customer-section">
        <div class="customer-title">
          Customer: {{ $cust->customer }} (CID: {{ $cust->cid_abh }})
        </div>
        
        <table>
          <thead>
            <tr>
              <th class="col-no text-center">#</th>
              <th class="col-ticket">Ticket No.</th>
              <th class="col-ticket">Supplier Ref.</th>
              <th class="col-supplier">SID</th>
              <th class="col-name">Supplier</th>
              <th class="col-issue">Issue Type</th>
              <th class="col-time">Start Time</th>
              <th class="col-time">End Time</th>
              <th class="col-duration text-center">Duration</th>
              <th class="col-cause">Root Cause</th>
              <th class="col-action">Resolution</th>
            </tr>
          </thead>
          <tbody>
            @forelse($custTickets as $i => $t)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $t->ticket_number }}</td>
                <td>{!! $t->supplier_ticket_number ?: '<span class="empty-cell">N/A</span>' !!}</td>
                <td>{!! optional($t->customer)->cid_supp ?: '<span class="empty-cell">N/A</span>' !!}</td>
                <td>{!! optional($t->customer->supplier)->nama_supplier ?: '<span class="empty-cell">N/A</span>' !!}</td>
                <td>{!! $t->issue_type ?: '<span class="empty-cell">N/A</span>' !!}</td>
                <td class="date-cell">{!! optional($t->start_time)->format('d/m/Y H:i') ?: '<span class="empty-cell">N/A</span>' !!}</td>
                <td class="date-cell">
                  @if($t->end_time)
                    {{ $t->end_time->format('d/m/Y H:i') }}
                    <div class="status-indicator status-resolved">Resolved</div>
                  @else
                    <span class="empty-cell">Ongoing</span>
                    <div class="status-indicator status-open">Open</div>
                  @endif
                </td>
                <td class="text-center date-cell">
                  @if($t->start_time && $t->end_time)
                    {{ $t->end_time->diffInMinutes($t->start_time) }}m
                  @else
                    -
                  @endif
                </td>
                <td class="multiline">
                  {!! $t->problem_detail ?: '<span class="empty-cell">Analysis pending</span>' !!}
                </td>
                <td class="multiline">
                  {!! $t->action_taken ?: '<span class="empty-cell">Resolution pending</span>' !!}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="no-data">
                  No network incidents recorded for this customer during the period
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      
      @if(!$loop->last && $loop->iteration % 3 === 0)
        <div style="page-break-after: always;"></div>
      @endif
    @endforeach

    <div class="footer">
      Confidential Document • Generated on {{ now()->format('F j, Y \a\t H:i') }} by Network Operations Center
    </div>
  </div>
</body>
</html>
