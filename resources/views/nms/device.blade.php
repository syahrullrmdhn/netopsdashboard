@extends('layouts.app')
@section('title', "NMS — " . ($dev['hostname'] ?? ('#'.$dev['device_id'])))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="{openRows:{}}">
  {{-- Header --}}
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">{{ $dev['hostname'] ?? ('#'.$dev['device_id']) }}</h1>
      <p class="text-sm text-gray-500">
        Status:
        @if((int)($dev['status'] ?? 0) === 1)
          <span class="inline-flex items-center gap-1 text-emerald-700">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Up
          </span>
        @else
          <span class="inline-flex items-center gap-1 text-rose-700">
            <span class="h-2 w-2 rounded-full bg-rose-500"></span> Down
          </span>
        @endif
      </p>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('nms.index') }}"
         class="px-3 py-2 text-sm rounded-md bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Back</a>
      <a href="{{ $openUrl }}" target="_blank"
         class="px-3 py-2 text-sm rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Open in Observium</a>
    </div>
  </div>

  {{-- Range --}}
  <form method="get" class="mb-6 flex flex-wrap items-end gap-3">
    <input type="hidden" name="q" value="{{ $q }}">
    <div>
      <label class="block text-xs text-gray-500 mb-1">Preset</label>
      <select name="range"
        class="rounded-md border-gray-300 py-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        <option value="3h"  @selected($range==='3h')>Last 3 hours</option>
        <option value="24h" @selected($range==='24h')>Last 24 hours</option>
        <option value="7d"  @selected($range==='7d')>Last 7 days</option>
        <option value="30d" @selected($range==='30d')>Last 30 days</option>
      </select>
    </div>
    <div>
      <label class="block text-xs text-gray-500 mb-1">From</label>
      <input type="datetime-local" name="from"
             value="{{ \Carbon\Carbon::createFromTimestamp($from)->format('Y-m-d\TH:i') }}"
             class="rounded-md border-gray-300 py-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
    </div>
    <div>
      <label class="block text-xs text-gray-500 mb-1">To</label>
      <input type="datetime-local" name="to"
             value="{{ \Carbon\Carbon::createFromTimestamp($to)->format('Y-m-d\TH:i') }}"
             class="rounded-md border-gray-300 py-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
    </div>
    <button class="px-3 py-2 rounded-md bg-gray-800 text-white text-sm hover:bg-black">Apply</button>

    <div class="ml-auto">
      <label class="block text-xs text-gray-500 mb-1">Find Interface</label>
      <div class="flex gap-2">
        <input type="text" name="q" value="{{ $q }}" placeholder="Gi0/1, vlan, alias..."
               class="rounded-md border-gray-300 py-2 px-3 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        <button class="px-3 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">Search</button>
      </div>
    </div>
  </form>

  {{-- Device summary graphs --}}
  <div class="space-y-6 mb-8">
    @foreach($graphs as $title => $src)
      <div class="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b">
          <p class="text-sm font-semibold text-gray-700">{{ $title }}</p>
        </div>
        <div class="p-4 overflow-x-auto">
          <img src="{{ $src }}" alt="{{ $title }}" class="rounded-md shadow-sm max-w-full" loading="lazy">
        </div>
      </div>
    @endforeach
  </div>

  {{-- Ports list --}}
  <div class="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
    <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
      <p class="text-sm font-semibold text-gray-700">Interfaces ({{ count($ports) }})</p>
      <p class="text-xs text-gray-500">Klik "Traffic" untuk menampilkan grafik pada baris.</p>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-2 text-left font-medium">Status</th>
            <th class="px-4 py-2 text-left font-medium">Name</th>
            <th class="px-4 py-2 text-left font-medium">Alias</th>
            <th class="px-4 py-2 text-left font-medium">Speed</th>
            <th class="px-4 py-2"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($ports as $p)
            <tr class="hover:bg-gray-50" x-data="{open:false}">
              <td class="px-4 py-2">
                @if(($p['status'] ?? 'up') === 'up')
                  <span class="inline-flex items-center gap-1 text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Up
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 text-rose-700">
                    <span class="h-2 w-2 rounded-full bg-rose-500"></span> Down
                  </span>
                @endif
              </td>
              <td class="px-4 py-2 font-medium text-gray-900">{{ $p['name'] }}</td>
              <td class="px-4 py-2 text-gray-700">{{ $p['alias'] ?: '—' }}</td>
              <td class="px-4 py-2 text-gray-700">{{ $p['speed'] ?: '—' }}</td>
              <td class="px-4 py-2 text-right">
                <button @click="open=!open"
                        class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-800 font-medium">
                  Traffic
                  <svg class="w-4 h-4" :class="{'rotate-90':open}" style="transition:transform .15s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
              </td>
            </tr>
            <tr x-show="open" x-transition>
              <td colspan="5" class="px-4 pb-4">
                <img
                  src="{{ route('nms.graph', ['type'=>'port_bits','id'=>$p['id'],'from'=>$from,'to'=>$to,'width'=>900,'height'=>200,'legend'=>'no']) }}"
                  alt="traffic {{ $p['name'] }}" class="rounded-md shadow-sm max-w-full" loading="lazy">
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Tidak ada interface yang cocok.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
