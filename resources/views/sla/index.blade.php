@extends('layouts.app')
@section('title', 'SLA Monitoring Dashboard')

@section('content')
@php
    $fmt = fn($sec) => collect([
        intval($sec / 86400) . 'd',
        intval(($sec % 86400) / 3600) . 'h',
        intval(($sec % 3600) / 60) . 'm',
        intval($sec % 60) . 's',
    ])->filter(fn($p) => substr($p, 0, -1) !== '0')->join(' ') ?: '0s';
@endphp

<div class="space-y-6">

  <!-- Export SLA Filter -->
  <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
    <h3 class="text-lg font-medium text-gray-800 mb-3">Export Report</h3>
    <form action="{{ route('sla.export') }}" method="get" target="_blank"
          x-data="{
            customers: {{ Js::from(\App\Models\Customer::on('customerdb')->orderBy('customer')->get(['id','customer','cid_abh','customer_group_id'])) }},
            groups: {{ Js::from(\App\Models\CustomerGroup::on('customerdb')->orderBy('group_name')->get(['id','group_name'])) }},
            group_id: '{{ request('group_id', '') }}',
            customer_id: '{{ request('customer_id', '') }}',
            filterCustomers() {
              if (!this.group_id) return this.customers;
              return this.customers.filter(c => String(c.customer_group_id) === String(this.group_id));
            }
          }"
          class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end"
    >
      <div>
        <label for="group-select" class="block text-sm font-medium text-gray-700 mb-1">Customer Group</label>
        <select id="group-select" name="group_id" x-model="group_id" class="w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
          <option value="">All Groups</option>
          <template x-for="g in groups" :key="g.id">
            <option :value="g.id" x-text="g.group_name"></option>
          </template>
        </select>
      </div>

      <div>
        <label for="customer-select" class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
        <select id="customer-select" name="customer_id" x-model="customer_id" class="w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
          <option value="">All Customers</option>
          <template x-for="c in filterCustomers()" :key="c.id">
            <option :value="c.id" x-text="c.customer + ' (' + c.cid_abh + ')'"></option>
          </template>
        </select>
      </div>

      <div>
        <label for="date-from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
        <input id="date-from" type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
      </div>

      <div>
        <label for="date-to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
        <input id="date-to" type="date" name="date_to" value="{{ $dateTo }}" class="w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
      </div>

      <div>
        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm font-semibold flex items-center justify-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Export PDF
        </button>
      </div>
    </form>
  </div>

  <!-- Filter Card -->
  <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">SLA Performance Dashboard</h2>
        <p class="text-sm text-gray-500">Monitor and analyze service level agreements</p>
      </div>
      <form method="GET" action="{{ route('sla.index') }}" class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 flex flex-col sm:flex-row gap-2">
          <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search customer..."
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>
          <div class="flex items-center gap-2">
            <input type="date" name="date_from" value="{{ $dateFrom }}"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <span class="text-gray-400">to</span>
            <input type="date" name="date_to" value="{{ $dateTo }}"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>
        </div>
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          Apply Filters
        </button>
      </form>
    </div>

    <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-blue-800">Calculation Method</h3>
          <div class="mt-1 text-sm text-gray-700">
            <p>SLA is calculated based on <b>total link downtime</b> during the period:
            <b>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</b> to <b>{{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</b> ({{ floor($periodSec/86400) }} days).</p>
            <p class="mt-1"><span class="font-medium">Formula:</span> <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded">SLA Realtime = SLA Target - (Total Downtime / Period) × 100%</code></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Data Card -->
  <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Customer SLA Performance</h2>
        <p class="text-sm text-gray-500">Detailed view of SLA compliance by customer</p>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SID ABH</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SLA Target</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Down Incidents</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Downtime</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Allowed</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SLA Status</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @forelse($customerStats as $i => $c)
          @php
            $slaTarget = $c['sla_percent'];
            $slaReal   = min(max($c['sla_realtime'], 0), 100);

            if ($slaReal >= $slaTarget) {
                $color = 'bg-green-500';
                $statusClass = 'text-green-600';
                $statusText = 'Meeting SLA';
            } elseif ($slaReal >= 98) {
                $color = 'bg-yellow-400';
                $statusClass = 'text-yellow-600';
                $statusText = 'At Risk';
            } else {
                $color = 'bg-red-500';
                $statusClass = 'text-red-600';
                $statusText = 'Below SLA';
            }
            $pct = $slaReal;
          @endphp
          <tr class="hover:bg-gray-50 transition-colors duration-150">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $customerStats->firstItem() + $i }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-medium">{{ $c['customer']->customer ?? '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $c['customer']->cid_abh ?? '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $slaTarget }}%</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $c['linkdown_count'] > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                {{ $c['linkdown_count'] }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $fmt($c['total_downtime']) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $fmt($c['sla_allowed']) }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="mr-3">
                  <span class="text-sm font-medium {{ $statusClass }}">{{ number_format($slaReal, 2) }}%</span>
                  <span class="block text-xs text-gray-500">{{ $statusText }}</span>
                </div>
                <div class="relative w-16">
                  <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                    <div style="width: {{ $pct }}%"
                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $color }}">
                    </div>
                  </div>
                  <div class="absolute right-0 -mt-2 -mr-4 text-xs text-gray-400">
                    {{ number_format($slaTarget, 2) }}%
                  </div>
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
              <div class="flex flex-col items-center justify-center py-8">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="mt-2 block text-sm font-medium text-gray-600">No customer data found</span>
              </div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- PAGINATION --}}
    @if($customerStats->hasPages())
    <div class="mt-4 px-6 py-3 bg-gray-50 border-t border-gray-200 text-xs text-gray-500 flex flex-col sm:flex-row justify-between items-center gap-2">
      <div>
        Showing <span class="font-medium">{{ $customerStats->firstItem() }}</span> to <span class="font-medium">{{ $customerStats->lastItem() }}</span> of <span class="font-medium">{{ $customerStats->total() }}</span> results
      </div>
      <div>
        {{ $customerStats->onEachSide(1)->links('vendor.pagination.tailwind') }}
      </div>
    </div>
    @endif
  </div>
</div>
@endsection
