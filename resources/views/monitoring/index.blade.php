@extends('layouts.app')

@section('title', 'Network Monitoring (Observium)')

@section('content')
<div
  x-data="monitoringPage()"
  x-init="init()"
  class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
>
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Network Monitoring (Observium)</h1>
      <p class="text-sm text-gray-500">Login via server, grafik diproksi — ringan & aman.</p>
    </div>
    <a href="{{ rtrim(config('services.observium.base'),'/') }}/"
       target="_blank"
       class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
      Open Observium
    </a>
  </div>

  {{-- search --}}
  <div class="mt-6 flex gap-2">
    <div class="relative flex-1">
      <input x-model="q" @keydown.enter="reload()"
        type="text" placeholder="Cari interface / hostname / lokasi…"
        class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"/>
      {{-- suggest dropdown (interfaces) --}}
      <div x-show="suggest.length && focusSearch"
           @mousedown.prevent
           class="absolute z-10 mt-1 w-full bg-white rounded-md shadow border max-h-64 overflow-auto">
        <template x-for="(s,i) in suggest" :key="i">
          <button @click="openGraph(s)"
            class="w-full text-left px-3 py-2 hover:bg-gray-50">
            <div class="text-sm font-medium" x-text="s.ifname"></div>
            <div class="text-xs text-gray-500" x-text="s.hostname"></div>
          </button>
        </template>
      </div>
    </div>
    <button @click="reload()"
      class="px-4 py-2 rounded-md bg-gray-800 text-white hover:bg-gray-900">Filter</button>
  </div>

  {{-- cards --}}
  <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="rounded-lg border p-4">
      <div class="text-sm text-gray-500">Total Devices</div>
      <div class="text-2xl font-semibold" x-text="cards.total"></div>
    </div>
    <div class="rounded-lg border p-4">
      <div class="text-sm text-gray-500">Up</div>
      <div class="text-2xl font-semibold text-emerald-600" x-text="cards.up"></div>
    </div>
    <div class="rounded-lg border p-4">
      <div class="text-sm text-gray-500">Down</div>
      <div class="text-2xl font-semibold text-rose-600" x-text="cards.down"></div>
    </div>
  </div>

  {{-- tabs --}}
  <div class="mt-6 border-b flex gap-6">
    <button :class="tab==='devices' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-600'"
            class="pb-2" @click="tab='devices'">Devices</button>
    <button :class="tab==='ifaces' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-600'"
            class="pb-2" @click="tab='ifaces'; loadSuggest(true)">Interfaces</button>
  </div>

  {{-- DEVICES TABLE --}}
  <div x-show="tab==='devices'" class="mt-4 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-xs uppercase text-gray-500">
          <th class="px-3 py-2 text-left">Status</th>
          <th class="px-3 py-2 text-left">Hostname</th>
          <th class="px-3 py-2 text-left">Operating System</th>
          <th class="px-3 py-2 text-left">Sysname</th>
          <th class="px-3 py-2 text-left">Hardware</th>
          <th class="px-3 py-2 text-left">Location</th>
          <th class="px-3 py-2 text-left">Interfaces</th>
        </tr>
      </thead>
      <tbody>
        <template x-if="!devices.length">
          <tr><td colspan="7" class="px-3 py-6 text-center text-gray-400">Tidak ada data.</td></tr>
        </template>

        <template x-for="d in devices" :key="d.device_id">
          <tr class="border-t">
            <td class="px-3 py-2">
              <span class="inline-flex items-center gap-1">
                <span :class="d.status ? 'bg-emerald-500' : 'bg-rose-500'" class="inline-block w-2 h-2 rounded-full"></span>
                <span x-text="d.status ? 'Up' : 'Down'"></span>
              </span>
            </td>
            <td class="px-3 py-2 font-medium" x-text="d.hostname"></td>
            <td class="px-3 py-2" x-text="d.os || '-'"></td>
            <td class="px-3 py-2" x-text="d.sysname || '-'"></td>
            <td class="px-3 py-2" x-text="d.hardware || '-'"></td>
            <td class="px-3 py-2" x-text="d.location || '-'"></td>
            <td class="px-3 py-2">
              <a @click.prevent="openInterfaces(d.device_id, d.hostname)" href="#" class="text-indigo-600 hover:underline"
                 x-text="d.if_count ?? 'lihat'"></a>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>

  {{-- INTERFACES LIST (simple) --}}
  <div x-show="tab==='ifaces'" class="mt-4">
    <div class="text-sm text-gray-500 mb-2" x-text="ifaceTitle"></div>
    <div class="rounded-lg border divide-y">
      <template x-if="!ifaces.length">
        <div class="p-4 text-center text-gray-400">Tidak ada data.</div>
      </template>

      <template x-for="it in ifaces" :key="it.device_id + '-' + it.port_id">
        <div class="p-3 flex items-center justify-between">
          <div>
            <div class="font-medium" x-text="it.ifname"></div>
            <div class="text-xs text-gray-500" x-text="it.hostname"></div>
          </div>
          <div class="flex items-center gap-3">
            <span :class="it.status==='up' ? 'text-emerald-600' : 'text-rose-600'" class="text-sm font-semibold"
                  x-text="it.status.toUpperCase()"></span>
            <button @click="openGraph(it)"
                    class="px-3 py-1.5 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm">
              Graph
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>

  {{-- GRAPH MODAL --}}
  <div x-show="graphOpen" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl w-full max-w-4xl overflow-hidden shadow-xl">
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <div class="font-semibold">Traffic Graph — <span x-text="picked.hostname + ' :: ' + picked.ifname"></span></div>
        <button @click="graphOpen=false" class="text-gray-500 hover:text-gray-700">&times;</button>
      </div>
      <div class="px-4 py-3 flex items-center gap-2">
        <button @click="range='-1h'; refreshGraph()" :class="range==='-1h' ? activeBtn : normalBtn">1h</button>
        <button @click="range='-6h'; refreshGraph()" :class="range==='-6h' ? activeBtn : normalBtn">6h</button>
        <button @click="range='-24h'; refreshGraph()" :class="range==='-24h' ? activeBtn : normalBtn">24h</button>
        <button @click="range='-7d'; refreshGraph()" :class="range==='-7d' ? activeBtn : normalBtn">7d</button>
        <button @click="range='-30d'; refreshGraph()" :class="range==='-30d' ? activeBtn : normalBtn">30d</button>
      </div>
      <div class="px-4 pb-4">
        <img :src="graphUrl" alt="graph" class="w-full rounded-md border"/>
      </div>
    </div>
  </div>
</div>

<script>
function monitoringPage(){
  return {
    q: '',
    tab: 'devices',
    cards: { total:0, up:0, down:0 },
    devices: [],
    ifaces: [],
    ifaceTitle: '',
    // auto-suggest
    suggest: [],
    focusSearch: false,
    // graph modal state
    graphOpen: false,
    picked: null,
    graphUrl: '',
    range: '-24h',
    activeBtn: 'px-2 py-1 rounded-md bg-indigo-600 text-white',
    normalBtn: 'px-2 py-1 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200',

    init(){
      this.reload();
      // auto-suggest ketika fokus di search
      const input = document.querySelector('input[x-model="q"]') || document.querySelector('input');
      input.addEventListener('focus', () => { this.focusSearch = true; this.loadSuggest(); });
      input.addEventListener('blur', () => setTimeout(()=> this.focusSearch=false, 150));
      input.addEventListener('input', () => this.loadSuggest());
    },

    async reload(){
      const url = new URL('{{ url('/monitoring/devices.json') }}', window.location.origin);
      if (this.q) url.searchParams.set('q', this.q);
      url.searchParams.set('max', 30);
      const r = await fetch(url);
      if(!r.ok){ this.devices=[]; this.cards={total:0,up:0,down:0}; return; }
      const data = await r.json();
      this.devices = data.devices || [];
      this.cards.total = data.total || 0;
      this.cards.up    = data.up || 0;
      this.cards.down  = data.down || 0;
      // refresh interfaces tab kalau sedang aktif
      if (this.tab==='ifaces') this.loadSuggest(true);
    },

    async loadSuggest(force=false){
      if (!this.focusSearch && !force) return;
      const url = new URL('{{ url('/monitoring/interfaces.json') }}', window.location.origin);
      if (this.q) url.searchParams.set('q', this.q);
      url.searchParams.set('limit', 10);
      const r = await fetch(url);
      const d = await r.json();
      this.suggest = d.items || [];
      if (this.tab==='ifaces'){ this.ifaces = this.suggest; this.ifaceTitle = `Interfaces (sample ${this.ifaces.length})`; }
    },

    async openInterfaces(deviceId, host){
      const url = new URL('{{ url('/monitoring/interfaces.json') }}', window.location.origin);
      url.searchParams.set('device_id', deviceId);
      url.searchParams.set('limit', 200);
      const r = await fetch(url);
      const d = await r.json();
      this.tab = 'ifaces';
      this.ifaces = d.items || [];
      this.ifaceTitle = `${host} — ${this.ifaces.length} interface`;
    },

    openGraph(s){
      this.picked = s;
      this.graphOpen = true;
      this.refreshGraph();
    },

    refreshGraph(){
      if (!this.picked) return;
      const url = new URL('{{ url('/monitoring/graph.png') }}', window.location.origin);
      url.searchParams.set('type', 'port_bits');
      url.searchParams.set('port_id', this.picked.port_id);
      url.searchParams.set('from', this.range);
      url.searchParams.set('to', 'now');
      url.searchParams.set('width', 900);
      url.searchParams.set('height', 260);
      url.searchParams.set('legend', 'no');
      this.graphUrl = url.toString();
    },
  }
}
</script>
@endsection
