{{-- resources/views/sla/index.blade.php --}}
@extends('layouts.app')

@section('title','SLA Ticket Customer')

@section('content')
@php
    // SLA yang disepakati
    $slaPercent = 99.5;
    // detik per hari
    $dailySec   = 24 * 3600;
    // helper format detik → "Xd Yh Zm Ws"
    $fmt = fn($sec) => collect([
        intval($sec / 86400) . 'd',
        intval(($sec % 86400) / 3600) . 'h',
        intval(($sec % 3600) / 60) . 'm',
        intval($sec % 60) . 's',
    ])->filter(fn($p) => substr($p, 0, -1) !== '0')
      ->join(' ') ?: '0s';
@endphp

<div class="space-y-6">

  {{-- 1) Filter Customer --}}
  <div class="bg-white p-6 rounded-lg shadow">
    <form action="{{ route('sla.index') }}" method="GET"
          class="md:flex md:space-x-4 space-y-4 md:space-y-0">
      <input type="text" name="search" value="{{ request('search') }}"
             placeholder="Search customers…"
             class="flex-1 border rounded-lg px-4 py-2">
      <select name="group_id" class="flex-1 border rounded-lg px-4 py-2">
        <option value="">All Groups</option>
        @foreach($groupsList as $g)
          <option value="{{ $g->id }}" @selected(request('group_id') == $g->id)>
            {{ $g->group_name }}
          </option>
        @endforeach
      </select>
      <select name="status" class="flex-1 border rounded-lg px-4 py-2">
        <option value="">All Status</option>
        @foreach($statuses as $key => $label)
          <option value="{{ $key }}" @selected(request('status') == $key)>
            {{ $label }}
          </option>
        @endforeach
      </select>
      <button type="submit"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg">
        Filter
      </button>
    </form>
  </div>

  {{-- 2) Allowed Downtime --}}
  <div class="bg-white p-6 rounded-lg shadow">
    <h2 class="text-xl font-semibold mb-4">
      Allowed Downtime for SLA {{ $slaPercent }}%
    </h2>
    <ul class="list-disc pl-6 space-y-1">
      <li><strong>Daily:</strong>    {{ $fmt($dailySec * (100 - $slaPercent) / 100) }}</li>
      <li><strong>Weekly:</strong>   {{ $fmt(7 * $dailySec * (100 - $slaPercent) / 100) }}</li>
      <li><strong>Monthly:</strong>  {{ $fmt(30 * $dailySec * (100 - $slaPercent) / 100) }}</li>
      <li><strong>Quarterly:</strong>{{ $fmt(90 * $dailySec * (100 - $slaPercent) / 100) }}</li>
      <li><strong>Yearly:</strong>   {{ $fmt(365 * $dailySec * (100 - $slaPercent) / 100) }}</li>
    </ul>
  </div>

  {{-- 3) Customers & SLA Table --}}
  <div class="bg-white p-6 rounded-lg shadow overflow-auto">
    <h2 class="text-xl font-semibold mb-4">
      Customers with Tickets & Realtime SLA
    </h2>
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left uppercase">No</th>
          <th class="px-4 py-2 text-left uppercase">Customer</th>
          <th class="px-4 py-2 text-left uppercase"># Tickets</th>
          <th class="px-4 py-2 text-left uppercase">Total Downtime</th>
          <th class="px-4 py-2 text-left uppercase">Realtime SLA (%)</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        @foreach($customers as $i => $cust)
          @php
            $entries  = $ticketsByCust[$cust->id] ?? collect();
            $count    = $entries->count();
            $totalSec = $entries->sum(fn($t) =>
                $t->start_time
                  ? ($t->end_time ?: now())->diffInSeconds($t->start_time)
                  : 0
            );
            $availSec = max(0, $dailySec - $totalSec);
            $slaReal  = round($availSec / $dailySec * 100, 2);
          @endphp
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-2">
              {{ $i + 1 + ($customers->currentPage() - 1) * $customers->perPage() }}
            </td>
            <td class="px-4 py-2">
              {{ $cust->customer }} ({{ $cust->cid_abh }})
            </td>
            <td class="px-4 py-2">{{ $count }}</td>
            <td class="px-4 py-2">{{ $fmt($totalSec) }}</td>
            <td class="px-4 py-2">{{ $slaReal }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <div class="px-4 py-4">
      {{ $customers->links('vendor.pagination.tailwind') }}
    </div>
  </div>

  {{-- 4) PRTG Sensor Summary --}}
  <div class="bg-white p-6 rounded-lg shadow">
    <h2 class="text-xl font-semibold mb-4">PRTG Sensor Summary</h2>
    @if($connected)
      <ul class="list-disc pl-6 space-y-1">
        @foreach($groupedSensors as $device => $sensors)
          @php
            $col  = collect($sensors);
            $up   = $col->where('status', 'Up')->count();
            $down = $col->where('status', 'Down')->count();
            $warn = $col->count() - $up - $down;
          @endphp
          <li>
            <strong>
              <a href="{{ route('sla.device', $device) }}"
                 class="text-blue-600 hover:underline">
                {{ $device }}
              </a>
            </strong>:
            {{ $col->count() }} sensors —
            <span class="text-green-600">Up {{ $up }}</span>,
            <span class="text-red-600">Down {{ $down }}</span>,
            <span class="text-yellow-600">Warn {{ $warn }}</span>
          </li>
        @endforeach
      </ul>
    @else
      <p class="text-red-600">PRTG API connection failed: {{ $errorMsg }}</p>
    @endif
  </div>

</div>
@endsection
