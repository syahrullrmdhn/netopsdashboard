<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Trouble Ticket Report {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</title>
  <style>
    /* Base Styles */
    @page {
      size: A4;
      margin: 1.5cm;
      @bottom-center {
        content: "Page " counter(page) " of " counter(pages);
        font-size: 9pt;
        color: #666;
      }
    }
    
    body {
      font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      font-size: 9.5pt;
      line-height: 1.4;
      color: #333;
      background-color: #fff;
      -webkit-font-smoothing: antialiased;
    }
    
    /* Header Styles */
    .header {
      text-align: center;
      margin-bottom: 1.2rem;
      padding-bottom: 0.8rem;
      border-bottom: 1px solid #e0e0e0;
    }
    
    .logo {
      font-size: 1.8rem;
      font-weight: 600;
      color: #d32f2f;
      margin-bottom: 0.3rem;
      letter-spacing: -0.5px;
    }
    
    .company-name {
      font-size: 0.95rem;
      font-weight: 500;
      margin-bottom: 0.3rem;
      color: #222;
    }
    
    .address {
      font-size: 0.75rem;
      color: #666;
      max-width: 75%;
      margin: 0 auto;
      line-height: 1.3;
    }
    
    /* Report Title */
    .report-title {
      font-size: 1.2rem;
      font-weight: 600;
      text-align: center;
      margin: 0.8rem 0 0.3rem;
      color: #222;
    }
    
    .report-period {
      font-size: 0.85rem;
      color: #666;
      text-align: center;
      margin-bottom: 1.5rem;
    }
    
    /* Customer Section */
    .customer-section {
      margin-bottom: 1.8rem;
      page-break-inside: avoid;
    }
    
    .customer-title {
      font-size: 0.9rem;
      font-weight: 500;
      color: #222;
      margin-bottom: 0.6rem;
      background-color: #f7f7f7;
      padding: 0.5rem 0.8rem;
      border-radius: 3px;
      border-left: 4px solid #d32f2f;
    }
    
    /* Table Styles */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1.2rem;
      page-break-inside: avoid;
      table-layout: fixed;
    }
    
    th {
      background-color: #f5f5f5;
      color: #444;
      font-weight: 600;
      text-align: left;
      padding: 0.5rem 0.4rem;
      border: 1px solid #ddd;
      font-size: 0.8rem;
      vertical-align: middle;
    }
    
    td {
      padding: 0.5rem 0.4rem;
      border: 1px solid #e0e0e0;
      font-size: 0.8rem;
      vertical-align: top;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    
    .text-center {
      text-align: center;
    }
    
    /* Column Styles */
    .col-no {
      width: 4%;
    }
    
    .col-ticket {
      width: 9%;
    }
    
    .col-supplier {
      width: 7%;
    }
    
    .col-name {
      width: 11%;
    }
    
    .col-issue {
      width: 7%;
    }
    
    .col-time {
      width: 8%;
    }
    
    .col-duration {
      width: 5%;
    }
    
    .col-cause {
      width: 20%;
    }
    
    .col-action {
      width: 20%;
    }
    
    /* No Data Row */
    .no-data {
      text-align: center;
      color: #777;
      font-style: italic;
      padding: 1rem;
      background-color: #fafafa;
    }
    
    /* Zebra Striping */
    tbody tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    
    /* Hover Effect */
    tbody tr:hover {
      background-color: #f1f1f1;
    }
    
    /* Footer */
    .footer {
      font-size: 0.75rem;
      color: #666;
      text-align: center;
      margin-top: 1.5rem;
      padding-top: 0.8rem;
      border-top: 1px solid #e0e0e0;
    }
    
    /* Multi-line cell content */
    .multiline {
      white-space: pre-wrap;
      line-height: 1.3;
      padding-top: 0.6rem;
      padding-bottom: 0.6rem;
    }
    
    /* Compact view for empty cells */
    .empty-cell {
      color: #999;
      font-style: italic;
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">abhinawa</div>
    <div class="company-name">PT. Abhinawa Sumberdaya Asia</div>
    <div class="address">
      Head Office: Menara Kadin Indonesia, Jl. H. R. Rasuna Said, RT.1/RW.2, Kuningan, Kuningan Tim.,<br>
      Kecamatan Setiabudi, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12950
    </div>
  </div>

  <h1 class="report-title">Trouble Ticket Verification Report</h1>
  <div class="report-period">
    Reporting Period: {{ $from->format('F j, Y') }} to {{ $to->format('F j, Y') }}
  </div>

  @foreach($customers as $cust)
    @php
      $custTickets = $ticketsByCust->get($cust->id, collect());
    @endphp

    <div class="customer-section">
      <div class="customer-title">
        Customer: {{ $cust->customer }} ({{ $cust->cid_abh }})
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
            <th class="col-duration text-center">Dur. (min)</th>
            <th class="col-cause">Root Cause</th>
            <th class="col-action">Resolution</th>
          </tr>
        </thead>
        <tbody>
          @forelse($custTickets as $i => $t)
            <tr>
              <td class="text-center">{{ $i + 1 }}</td>
              <td>{{ $t->ticket_number }}</td>
              <td>{{ $t->supplier_ticket_number ?: '-' }}</td>
              <td>{{ optional($t->customer)->cid_supp ?: '-' }}</td>
              <td>{{ optional($t->customer->supplier)->nama_supplier ?: '-' }}</td>
              <td>{{ $t->issue_type ?: '-' }}</td>
              <td>{{ optional($t->start_time)->format('d/m/Y H:i') ?: '-' }}</td>
              <td>{{ optional($t->end_time)->format('d/m/Y H:i') ?: '-' }}</td>
              <td class="text-center">
                @if($t->start_time && $t->end_time)
                  {{ $t->end_time->diffInMinutes($t->start_time) }}
                @else
                  -
                @endif
              </td>
              <td class="multiline">{{ $t->problem_detail ?: '<span class="empty-cell">Not specified</span>' }}</td>
              <td class="multiline">{{ $t->action_taken ?: '<span class="empty-cell">Not specified</span>' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="no-data">No trouble tickets found for this period</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    
    @if($loop->iteration % 5 === 0 && !$loop->last)
      <div style="page-break-after: always;"></div>
    @endif
  @endforeach

  <div class="footer">
    Document generated on {{ now()->format('F j, Y \a\t H:i') }} by {{ auth()->user()->name ?? 'System' }}
  </div>
</body>
</html>