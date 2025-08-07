<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>SLA Customer Report</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', Arial, sans-serif;
      font-size: 12px;
      color: #333;
      line-height: 1.5;
    }
    .title {
      font-size: 24px;
      font-weight: 600;
      color: #b91c1c;
      margin-bottom: 8px;
      letter-spacing: -0.5px;
    }
    .subtitle {
      color: #555;
      margin-bottom: 16px;
      font-weight: 400;
    }
    .section {
      margin-bottom: 36px;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      font-size: 11px;
      margin-bottom: 16px;
    }
    th, td {
      border: 1px solid #e5e7eb;
      padding: 8px 10px;
      text-align: left;
    }
    th {
      background: #e11d48;
      color: #fff;
      font-weight: 500;
      text-transform: uppercase;
      font-size: 10px;
      letter-spacing: 0.5px;
    }
    .badge {
      border-radius: 12px;
      padding: 3px 10px;
      font-size: 10px;
      display: inline-block;
      font-weight: 500;
    }
    .bg-green {
      background: #d1fae5;
      color: #047857;
    }
    .bg-red {
      background: #fee2e2;
      color: #b91c1c;
    }
    .bg-yellow {
      background: #fef3c7;
      color: #b45309;
    }
    .italic {
      font-style: italic;
      color: #888;
      font-weight: 300;
    }
    .page-break {
      page-break-after: always;
    }
    .summary-table td, .summary-table th {
      border: none;
      padding: 4px 0;
      font-size: 11px;
    }
    .summary-table td {
      padding-right: 20px;
    }
    .customer-title {
      font-size: 18px;
      font-weight: 600;
      color: #b91c1c;
      margin-top: 24px;
      margin-bottom: 12px;
    }
    .header-info {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-bottom: 20px;
      border-bottom: 1px solid #eee;
      padding-bottom: 12px;
    }
    .report-meta {
      text-align: right;
      font-size: 10px;
      color: #666;
    }
    .duration-cell {
      font-family: 'Courier New', monospace;
      font-weight: 500;
    }
    tr:nth-child(even) {
      background-color: #f9fafb;
    }
  </style>
</head>
<body>
  <div>
    <div class="header-info">
      <div>
        <div class="title">SLA Customer Report</div>
        <div class="subtitle">
          Period: <b>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</b>
          @if($group)
            <br>Customer Group: <b>{{ $group->group_name }}</b>
          @endif
        </div>
      </div>
      <div class="report-meta">
        Generated on: {{ now()->format('d M Y H:i') }}<br>
        Total Customers: {{ count($data) }}
      </div>
    </div>

    @foreach($data as $row)
      <div class="section">
        <div class="customer-title">
          {{ $row['customer']->customer }} <span style="color: #555; font-weight: 400;">({{ $row['customer']->cid_abh }})</span>
        </div>

        <table class="summary-table" width="100%">
          <tr>
            <td width="15%"><b>SLA Target</b></td>
            <td width="15%">{{ $row['sla_target'] }}%</td>
            <td width="15%"><b>SLA Realtime</b></td>
            <td width="15%">
              <span class="badge {{ $row['sla_real'] >= $row['sla_target'] ? 'bg-green' : ($row['sla_real'] >= 98 ? 'bg-yellow' : 'bg-red') }}">
                {{ number_format($row['sla_real'], 2) }}%
              </span>
            </td>
            <td width="15%"><b>Total Downtime</b></td>
            <td width="15%" class="duration-cell">{{ gmdate('H:i:s', $row['total_downtime']) }}</td>
          </tr>
          <tr>
            <td><b>MTTR</b></td>
            <td class="duration-cell">{{ $row['mttr'] ? gmdate('H:i:s', $row['mttr']) : '-' }}</td>
            <td><b>Incidents</b></td>
            <td>{{ count($row['tickets']) }}</td>
            <td><b>Availability</b></td>
            <td>{{ number_format(100 - ($row['total_downtime'] / ($row['period_seconds'] ?? 86400) * 100), 4) }}%</td>
          </tr>
        </table>

        @if(count($row['tickets']) > 0)
        <table width="100%" style="margin-top: 12px;">
          <thead>
            <tr>
              <th style="width: 30px;">#</th>
              <th>Issue Type</th>
              <th style="width: 120px;">Start Time</th>
              <th style="width: 120px;">End Time</th>
              <th style="width: 80px;">Duration</th>
              <th style="width: 80px;">MTTR</th>
            </tr>
          </thead>
          <tbody>
            @foreach($row['tickets'] as $i => $t)
              @php
                $duration = $t->start_time ? \Carbon\Carbon::parse($t->end_time ?: now())->diffInSeconds(\Carbon\Carbon::parse($t->start_time)) : 0;
              @endphp
              <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $t->issue_type }}</td>
                <td>{{ $t->start_time ? \Carbon\Carbon::parse($t->start_time)->format('d-m-Y H:i') : '-' }}</td>
                <td>{{ $t->end_time ? \Carbon\Carbon::parse($t->end_time)->format('d-m-Y H:i') : '-' }}</td>
                <td class="duration-cell">{{ gmdate('H:i:s', $duration) }}</td>
                <td class="duration-cell">{{ $duration ? gmdate('H:i:s', $duration) : '-' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
        @else
        <div style="background: #f8f9fa; padding: 12px; border-radius: 4px; margin-top: 12px;">
          <span class="italic">No issues/incidents recorded during this period</span>
        </div>
        @endif

        <div style="margin-top: 20px; font-size: 10px; color: #777; text-align: right;">
          SLA Calculation: (Total Period - Downtime) / Total Period × 100%
        </div>
      </div>

      @if(!$loop->last)
      <div class="page-break"></div>
      @endif
    @endforeach
  </div>
</body>
</html>
