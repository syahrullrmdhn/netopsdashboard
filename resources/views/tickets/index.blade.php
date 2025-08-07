@extends('layouts.app')

@section('title', 'Ticket Management')

@section('content')
<div class="space-y-6" x-data="{ importOpen: false }">
  {{-- Header --}}
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Ticket Management</h1>
      <p class="text-sm text-gray-500 mt-1">Efficiently track and resolve customer support tickets</p>
    </div>
    <div class="flex flex-col sm:flex-row gap-3">
      {{-- FILTER FORM --}}
      <form method="GET" action="{{ route('tickets.index') }}"
            x-data="{
                period: '{{ old('period', request('period', 'this_month')) }}',
                dateFrom: '{{ old('date_from', request('date_from', $dateFrom ?? now()->startOfMonth()->toDateString())) }}',
                dateTo:   '{{ old('date_to', request('date_to', $dateTo ?? now()->endOfMonth()->toDateString())) }}',
                periods: [
                  { value: 'this_month', label: 'This Month' },
                  @for($i=0; $i<12; $i++)
                    @php
                      $month = now()->subMonths($i)->format('Y-m');
                      $monthText = now()->subMonths($i)->format('F Y');
                    @endphp
                    { value: '{{ $month }}', label: '{{ $monthText }}' },
                  @endfor
                  { value: 'custom', label: 'Custom Range' }
                ]
            }"
            class="flex flex-col sm:flex-row gap-2 items-end"
      >
        {{-- Search --}}
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
          </div>
          <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search tickets..."
            class="pl-10 pr-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
          >
        </div>
        {{-- Status --}}
        <select
          name="status"
          class="block w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
        >
          <option value="" {{ request('status')=='' ? 'selected' : '' }}>All Statuses</option>
          <option value="open" {{ request('status')=='open' ? 'selected' : '' }}>Open</option>
          <option value="closed" {{ request('status')=='closed' ? 'selected' : '' }}>Closed</option>
        </select>
        {{-- Period Dropdown --}}
        <select name="period" x-model="period"
                class="block w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
          <template x-for="opt in periods" :key="opt.value">
            <option :value="opt.value" x-text="opt.label"></option>
          </template>
        </select>
        {{-- Custom Date Range --}}
        <template x-if="period === 'custom'">
          <div class="flex gap-2 items-center">
            <input type="date" name="date_from" x-model="dateFrom"
                   class="pl-3 pr-2 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            <span class="self-center text-gray-500">to</span>
            <input type="date" name="date_to" x-model="dateTo"
                   class="pl-3 pr-2 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
          </div>
        </template>
        <button
          type="submit"
          class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Apply Filters
        </button>
      </form>
      {{-- END FILTER FORM --}}
    </div>
  </div>

  {{-- Action Buttons --}}
  <div class="flex flex-wrap items-center gap-3">
    <a href="{{ route('tickets.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
      <x-heroicon-o-plus class="h-5 w-5 mr-2" /> New Ticket
    </a>
  </div>

  {{-- Flash Messages --}}
  @if(session('success'))
    <div class="rounded-md bg-green-50 p-4">
      <div class="flex">
        <x-heroicon-o-check-circle class="h-5 w-5 text-green-400 flex-shrink-0" />
        <p class="ml-3 text-sm font-medium text-green-800">{{ session('success') }}</p>
      </div>
    </div>
  @endif

  {{-- Tickets Table --}}
  <div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timeline</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SLA Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @forelse($tickets as $t)
            @php
              $tgt = $t->customer->sla_target ?? 99.5;
              $real = $t->customer->sla_realtime ?? 100.0;
              if ($real >= $tgt) {
                  $color = '#16a34a'; // Green
              } elseif ($real >= 98) {
                  $color = '#eab308'; // Yellow
              } else {
                  $color = '#dc2626'; // Red
              }
              $pct = $real;
            @endphp
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ $loop->iteration + ($tickets->currentPage()-1)*$tickets->perPage() }}
              </td>
              {{-- Customer --}}
              <td class="px-6 py-4 whitespace-nowrap flex items-center">
                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                  <span class="text-indigo-600 font-medium">
                    {{ strtoupper(substr($t->customer->customer, 0, 1)) }}
                  </span>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ $t->customer->customer }}</div>
                  <div class="text-sm text-gray-500">{{ $t->customer->cid_abh }}</div>
                </div>
              </td>
              {{-- Supplier --}}
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ $t->customer->supplier->nama_supplier ?? '—' }}</div>
                <div class="text-sm text-gray-500">{{ $t->customer->cid_supp ?? '—' }}</div>
              </td>
              {{-- Issue --}}
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ $t->issue_type }}</div>
                <div class="text-sm text-gray-500">
                  {{ \Illuminate\Support\Str::limit($t->problem_detail ?? '', 30, '...') ?: '—' }}
                </div>
              </td>
              {{-- Ticket IDs --}}
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-mono font-medium text-gray-900">{{ $t->ticket_number }}</div>
                <div class="text-sm font-mono text-gray-500">{{ $t->supplier_ticket_number ?: '—' }}</div>
              </td>
              {{-- Timeline --}}
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  @if($t->start_time)
                    {{ $t->start_time->format('M j, H:i') }}
                    @if($t->end_time)
                      <span class="text-gray-400 mx-1">→</span>
                      {{ $t->end_time->format('M j, H:i') }}
                    @endif
                  @else
                    —
                  @endif
                </div>
                <div class="text-sm text-gray-500">
                  @if($t->start_time && $t->end_time)
                    {{ $t->end_time->diffForHumans($t->start_time, true) }}
                  @elseif($t->start_time)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                      Ongoing ({{ now()->diffForHumans($t->start_time, true) }})
                    </span>
                  @else
                    Not started
                  @endif
                </div>
              </td>
              {{-- SLA Status --}}
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="w-full bg-gray-200 rounded-full h-4">
                  <div class="h-4 rounded-full text-xs text-white text-center leading-4"
                       style="width: {{ $pct }}%; background-color: {{ $color }};">
                    {{ number_format($real, 2) }}%
                  </div>
                </div>
                <div class="text-xs text-gray-400 mt-1">
                  {{ number_format($real, 2) }}% of {{ $tgt }}%
                </div>
              </td>
              {{-- Status --}}
              <td class="px-6 py-4 whitespace-nowrap">
                @if($t->end_time)
                  <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Closed</span>
                @else
                  <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Open</span>
                @endif
              </td>
              {{-- Actions --}}
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <a href="{{ route('tickets.show', $t) }}" class="text-indigo-600 hover:text-indigo-900" title="View">
                    <x-heroicon-o-eye class="h-5 w-5" />
                  </a>
                  <a href="{{ route('tickets.edit', $t) }}" class="text-gray-600 hover:text-gray-900" title="Edit">
                    <x-heroicon-o-pencil class="h-5 w-5" />
                  </a>
                  @unless($t->end_time)
                    <form action="{{ route('tickets.close', $t) }}" method="POST" class="inline">
                      @csrf @method('PATCH')
                      <button type="submit" onclick="return confirm('Close this ticket?')" class="text-green-600 hover:text-green-900" title="Close">
                        <x-heroicon-o-check-circle class="h-5 w-5" />
                      </button>
                    </form>
                  @endunless
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                No tickets found. <a href="{{ route('tickets.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one</a>.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if($tickets->hasPages())
      <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        {{ $tickets->links('vendor.pagination.tailwind') }}
      </div>
    @endif
  </div>
</div>
@endsection
