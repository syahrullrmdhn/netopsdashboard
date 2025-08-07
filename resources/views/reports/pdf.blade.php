<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Trouble Ticket Report {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}"></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', Arial, sans-serif; background: #fff; color: #1a2b48; font-size: 11px; }
    .customer-title { background: #1e293b; color: #fff; padding: 8px 18px; border-radius: 8px 8px 0 0; font-weight: 600; margin-bottom: 0; }
    table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 22px; }
    th, td { border: 1px solid #e5e7eb; padding: 8px 6px; }
    th { background: #1e293b; color: #fff; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; font-size: 10px; }
    tbody tr:nth-child(even) { background: #f1f5f9; }
    .text-center { text-align: center; }
    .italic { font-style: italic; color: #888; }
    .badge-green { background: #d1fae5; color: #047857; border-radius: 4px; font-size: 10px; padding: 1px 6px; }
    .badge-yellow { background: #fef3c7; color: #b45309; border-radius: 4px; font-size: 10px; padding: 1px 6px; }
    .whitespace-pre-line { white-space: pre-line; }
    .footer { font-size: 10px; color: #6b7280; text-align: center; border-top: 1px solid #e5e7eb; margin-top: 36px; padding-top: 16px; text-transform: uppercase; letter-spacing: 1px; }
    @page { size: A4 landscape; margin: 1.5cm; }
    .break-inside-avoid { break-inside: avoid-page; page-break-inside: avoid; }
    .page-break { page-break-after: always; }
  </style>
</head>
<body>
  <!-- COVER PAGE -->
  <div class="cover-page" style="font-family: 'poppins', Tahoma, Geneva, Verdana, sans-serif; text-align: center; background-color: #fff; color: #111;">
    <div class="header">
      <img src="{{ public_path('images/ABH-LOGO-HORIZONTAL_RED.webp') }}" alt="Company Logo" style="width: 200px;">
      <div class="company-name" style="font-size:16px; font-weight:700; text-transform:uppercase; color:#333;">
        PT. ABHINAWA SUMBERDAYA ASIA
      </div>
      <div class="address" style="font-size:12px; color:#333; max-width:75%; margin:0 auto;">
        Menara Kadin Indonesia, Jl. H. R. Rasuna Said, RT.1/RW.2, Kuningan, Kuningan Tim.,
        Kecamatan Setiabudi, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12950
      </div>
    </div>
    <h1 style="font-size: 32px; color: #b91c1c; text-transform: uppercase; margin-bottom: 10px; margin-top:10px">
      NETWORK INCIDENT VERIFICATION REPORT
    </h1>
    <div style="font-size: 18px; color: #333; margin-bottom: 18px;">
      Detailed Incident Analysis
    </div>
    <div style="font-size: 16px; color: #555;">
      {{ $from->format('F j, Y') }} to {{ $to->format('F j, Y') }}
    </div>

    {{-- ==== FILTER INFO ==== --}}
    <div style="margin:20px auto 32px; color: #1e293b; font-size:14px; max-width:70%; background: #f1f5f9; border-radius: 8px; padding:10px 0 8px;">
      <div style="margin-bottom:2px;">
        <strong>Filters Applied:</strong>
      </div>
      <div>
        <span style="display:inline-block; min-width:120px;"><strong>Customer</strong>:</span>
        @if(isset($customerName) && $customerName)
          {{ $customerName }}
        @else
          @php
            $custList = [];
            foreach($customers as $c) $custList[] = $c->customer;
          @endphp
          {{ count($custList) === 1 ? $custList[0] : 'All' }}
        @endif
      </div>
      <div>
        <span style="display:inline-block; min-width:120px;"><strong>Group</strong>:</span>
        @if(isset($groupName) && $groupName)
          {{ $groupName }}
        @else
          {{ (request('group_id') && isset($groups) && $groups->where('id', request('group_id'))->first())
              ? $groups->where('id', request('group_id'))->first()->group_name : 'All' }}
        @endif
      </div>
      <div>
        <span style="display:inline-block; min-width:120px;"><strong>Issue Type</strong>:</span>
        {{ request('issue_type') ? ucwords(request('issue_type')) : 'All' }}
      </div>
    </div>
    {{-- ==== END FILTER INFO ==== --}}

    <hr style="border: 1px solid #b91c1c; width: 60%; margin: 0 auto 20px;">
    <div style="font-size: 14px; color: #888;">
      Confidential • Prepared for <strong>{{ auth()->user()->company ?? 'Internal Review' }}</strong> • Generated on {{ now()->format('F j, Y') }}
    </div>
  </div>

  <!-- PAGE BREAK: agar table report tidak ikut cover -->
  <div class="page-break"></div>

  <!-- REPORT SECTION -->
  <div class="first-page">
    @foreach($customers as $cust)
      @php
        $custTickets = $ticketsByCust->get($cust->id, collect());
      @endphp

      <div class="customer-section mb-10 break-inside-avoid">
        <div class="customer-title">
          Customer: {{ $cust->customer }} (CID: {{ $cust->cid_abh }})
        </div>

        <table>
          <thead>
            <tr>
              <th class="text-center">NO</th>
              <th class="text-center">TICKET NO.</th>
              <th class="text-center">SUPPLIER REF.</th>
              <th class="text-center">SID</th>
              <th class="text-center">SUPPLIER</th>
              <th class="text-center">ISSUE TYPE</th>
              <th class="text-center">START TIME</th>
              <th class="text-center">END TIME</th>
              <th class="text-center">DURATION</th>
              <th class="text-center">ROOT CAUSE</th>
              <th class="text-center">RESOLUTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse($custTickets as $i => $t)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $t->ticket_number }}</td>
                <td>{!! $t->supplier_ticket_number ?: '<span class="italic">N/A</span>' !!}</td>
                <td>{!! optional($t->customer)->cid_supp ?: '<span class="italic">N/A</span>' !!}</td>
                <td>{!! optional($t->customer->supplier)->nama_supplier ?: '<span class="italic">N/A</span>' !!}</td>
                <td>{!! $t->issue_type ?: '<span class="italic">N/A</span>' !!}</td>
                <td class="text-center">{!! optional($t->start_time)->format('d/m/Y H:i') ?: '<span class="italic">N/A</span>' !!}</td>
                <td class="text-center">
                  @if($t->end_time)
                    {{ $t->end_time->format('d/m/Y H:i') }}
                    <span class="badge-green ml-1">Resolved</span>
                  @else
                    <span class="italic">Ongoing</span>
                    <span class="badge-yellow ml-1">Open</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($t->start_time && $t->end_time)
                    @php
                      $diff = $t->end_time->diff($t->start_time);
                    @endphp
                    {{ $diff->h + ($diff->days * 24) }}h {{ $diff->i }}m
                  @else
                    <span class="italic">-</span>
                  @endif
                </td>
                <td class="whitespace-pre-line">{!! $t->problem_detail ?: '<span class="italic">Analysis pending</span>' !!}</td>
                <td class="whitespace-pre-line">{!! $t->action_taken ?: '<span class="italic">Resolution pending</span>' !!}</td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center italic" style="background:#f1f5f9">
                  No network incidents recorded for this customer during the period
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    @endforeach

    <div class="footer">
      Confidential Document • Generated on {{ now()->format('F j, Y \a\t H:i') }} by Network Operations Center
    </div>
  </div>
</body>
</html>
