@extends('layouts.app')

@section('title', 'Network Monitoring (Observium)')

@section('content')
<div
  x-data="nmsPage({
    devices: @js($devices),
    stats: @js($stats),
    urls: {
      deviceIf:  '/nms/devices',
      allIf:     '{{ route('nms.interfaces.all') }}',
      searchIf:  '{{ route('nms.interfaces.search') }}',
      graph:     '{{ route('nms.graph') }}',
      observium: '{{ rtrim(config('services.observium.base'), '/') }}/'
    }
  })"
  class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
>
  <div class="flex items-center justify-between mb-6">
    <div>
        @if(isset($err) && $err)
  <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
    <div class="flex items-start gap-2">
      <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-rose-500"/>
      <div>
        <p class="font-semibold">Observium error</p>
        <p class="text-sm">{{ $err }}</p>
      </div>
    </div>
  </div>
@endif

      <h1 class="text-2xl font-bold text-gray-900">Network Monitoring (Observium)</h1>
      <p class="text-sm text-gray-500">Login via server, grafik diproksi — ringan & aman.</p>
    </div>
    <a :href="urls.observium" target="_blank"
       class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
      Open Observium
    </a>
  </div>

  {{-- Global Search w/ suggest --}}
  <div class="mb-6">
    <div class="relative">
      <input type="text" x-model="search.q" @input.debounce.250ms="searchSuggest"
             placeholder="Cari interface / hostname / lokasi…" class="w-full rounded-lg border-gray-300 pl-12 pr-3 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
      <svg class="absolute left-4 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>

      <div x-show="search.open && search.items.length" x-cloak
           class="absolute z-20 mt-2 w-full rounded-lg border bg-white shadow-lg overflow-hidden">
        <template x-for="item in search.items" :key="item.port_id + '-' + item.device_id">
          <button @click="openGraph({ port_id:item.port_id, title: `${item.hostname} — ${item.ifName}` })"
                  class="w-full text-left px-4 py-2 hover:bg-gray-50">
            <div class="flex items-center justify-between">
              <div>
                <p class="font-medium text-gray-900" x-text="item.ifName"></p>
                <p class="text-xs text-gray-500">
                  <span x-text="item.hostname"></span>
                  • <span x-text="item.alias || '-'"></span>
                  • <span x-text="item.location || '-'"></span>
                </p>
              </div>
              <span class="text-[10px] px-1.5 py-0.5 rounded-full"
                :class="item.ifStatus==='down' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'"
                x-text="item.ifStatus==='down' ? 'Down' : 'Up'"></span>
            </div>
            <p class="mt-1 text-[11px] text-gray-500">
              OS: <span x-text="item.os || '-'"></span> • SysName: <span x-text="item.sysname || '-'"></span> • HW: <span x-text="item.hardware || '-'"></span>
            </p>
          </button>
        </template>
      </div>
    </div>
  </div>

  {{-- Stat cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="rounded-lg border bg-white px-4 py-5">
      <p class="text-sm text-gray-500">Total Devices</p>
      <p class="mt-1 text-3xl font-semibold text-gray-900" x-text="stats.total"></p>
    </div>
    <div class="rounded-lg border bg-white px-4 py-5">
      <p class="text-sm text-gray-500">Up</p>
      <p class="mt-1 text-3xl font-semibold text-emerald-600" x-text="stats.up"></p>
    </div>
    <div class="rounded-lg border bg-white px-4 py-5">
      <p class="text-sm text-gray-500">Down</p>
      <p class="mt-1 text-3xl font-semibold text-rose-600" x-text="stats.down"></p>
    </div>
  </div>

  {{-- Tabs --}}
  <div class="mb-4 border-b">
    <nav class="-mb-px flex gap-6">
      <button :class="tab==='devices' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
              class="whitespace-nowrap border-b-2 px-1 pb-2 text-sm font-medium" @click="tab='devices'">Devices</button>
      <button :class="tab==='interfaces' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
              class="whitespace-nowrap border-b-2 px-1 pb-2 text-sm font-medium" @click="openInterfacesTab()">Interfaces</button>
    </nav>
  </div>

  {{-- DEVICES TAB --}}
  <div x-show="tab==='devices'" x-cloak class="space-y-4">
    <div class="overflow-hidden rounded-lg border bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Hostname</th>
            <th class="px-4 py-3">Operating System</th>
            <th class="px-4 py-3">SysName</th>
            <th class="px-4 py-3">Hardware</th>
            <th class="px-4 py-3">Location</th>
            <th class="px-4 py-3">Interfaces</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-sm">
          <template x-for="d in devices" :key="d.device_id">
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                      :class="d.status ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                  <span class="mr-1 h-2 w-2 rounded-full" :class="d.status ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                  <span x-text="d.status ? 'Up' : 'Down'"></span>
                </span>
              </td>
              <td class="px-4 py-3">
                <p class="font-medium text-gray-900" x-text="d.hostname"></p>
                <p class="text-xs text-gray-500">#<span x-text="d.device_id"></span></p>
              </td>
              <td class="px-4 py-3" x-text="d.os || '-'"></td>
              <td class="px-4 py-3" x-text="d.sysname || '-'"></td>
              <td class="px-4 py-3" x-text="d.hardware || '-'"></td>
              <td class="px-4 py-3" x-text="d.location || '-'"></td>
              <td class="px-4 py-3">
                <button @click="togglePorts(d)" class="text-indigo-600 hover:text-indigo-800 font-medium">
                  <span x-text="openDeviceId===d.device_id ? 'Hide' : 'Show'"></span> Interfaces
                </button>
              </td>
            </tr>
            <tr x-show="openDeviceId===d.device_id" x-cloak>
              <td colspan="7" class="bg-gray-50 px-4 py-4">
                <template x-if="!d._portsLoading && (!d._ports || d._ports.length===0)">
                  <p class="text-sm text-gray-500">Tidak ada data interface.</p>
                </template>
                <template x-if="d._portsLoading">
                  <div class="flex items-center gap-2 text-sm text-gray-500">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <circle cx="12" cy="12" r="10" stroke-width="4" class="opacity-25"></circle>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M4 12a8 8 0 018-8"></path>
                    </svg> Loading interfaces…
                  </div>
                </template>
                <div x-show="!d._portsLoading && d._ports && d._ports.length" x-cloak class="overflow-x-auto rounded-md border bg-white">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                      <tr>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Interface</th>
                        <th class="px-3 py-2 text-left">Alias</th>
                        <th class="px-3 py-2 text-left">Speed</th>
                        <th class="px-3 py-2 text-left">Graph</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                      <template x-for="p in d._ports" :key="p.id">
                        <tr class="hover:bg-gray-50">
                          <td class="px-3 py-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                  :class="p.status==='down' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'">
                              <span class="mr-1 h-2 w-2 rounded-full" :class="p.status==='down' ? 'bg-rose-500' : 'bg-emerald-500'"></span>
                              <span x-text="p.status==='down' ? 'Down' : 'Up'"></span>
                            </span>
                          </td>
                          <td class="px-3 py-2 font-medium" x-text="p.name"></td>
                          <td class="px-3 py-2 text-gray-600" x-text="p.alias || '-'"></td>
                          <td class="px-3 py-2 text-gray-600" x-text="p.speed || '-'"></td>
                          <td class="px-3 py-2">
                            <button @click="openGraph({ port_id:p.id, title: d.hostname+' — '+p.name })" class="text-indigo-600 hover:text-indigo-800">View</button>
                          </td>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  {{-- INTERFACES TAB --}}
  <div x-show="tab==='interfaces'" x-cloak class="space-y-4">
    <div class="flex items-center gap-3">
      <div class="relative flex-1">
        <input type="text" placeholder="Cari hostname / interface / lokasi…" x-model="qIf"
               class="w-full rounded-md border-gray-300 pl-10 pr-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
      </div>
      <select x-model="statusFilterIf" class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">All</option><option value="up">Up</option><option value="down">Down</option>
      </select>
      <button @click="refreshAllInterfaces()" class="inline-flex items-center gap-2 rounded-md bg-gray-100 px-3 py-2 text-sm hover:bg-gray-200">Refresh</button>
    </div>

    <template x-if="loadingAllIf">
      <div class="flex items-center gap-2 text-sm text-gray-500">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <circle cx="12" cy="12" r="10" stroke-width="4" class="opacity-25"></circle>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M4 12a8 8 0 018-8"></path>
        </svg> Loading all interfaces…
      </div>
    </template>

    <div x-show="!loadingAllIf" x-cloak class="overflow-x-auto rounded-lg border bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
          <tr>
            <th class="px-3 py-2 text-left">Status</th>
            <th class="px-3 py-2 text-left">Hostname</th>
            <th class="px-3 py-2 text-left">Interface</th>
            <th class="px-3 py-2 text-left">Alias</th>
            <th class="px-3 py-2 text-left">OS</th>
            <th class="px-3 py-2 text-left">SysName</th>
            <th class="px-3 py-2 text-left">Hardware</th>
            <th class="px-3 py-2 text-left">Location</th>
            <th class="px-3 py-2 text-left">Graph</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-sm">
          <template x-for="r in filteredAllIf()" :key="r.port_id + '-' + r.device_id">
            <tr class="hover:bg-gray-50">
              <td class="px-3 py-2">
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                      :class="r.ifStatus==='down' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'">
                  <span class="mr-1 h-2 w-2 rounded-full" :class="r.ifStatus==='down' ? 'bg-rose-500' : 'bg-emerald-500'"></span>
                  <span x-text="r.ifStatus==='down' ? 'Down' : 'Up'"></span>
                </span>
              </td>
              <td class="px-3 py-2 font-medium" x-text="r.hostname"></td>
              <td class="px-3 py-2" x-text="r.ifName"></td>
              <td class="px-3 py-2 text-gray-600" x-text="r.alias || '-'"></td>
              <td class="px-3 py-2 text-gray-600" x-text="r.os || '-'"></td>
              <td class="px-3 py-2 text-gray-600" x-text="r.sysname || '-'"></td>
              <td class="px-3 py-2 text-gray-600" x-text="r.hardware || '-'"></td>
              <td class="px-3 py-2 text-gray-600" x-text="r.location || '-'"></td>
              <td class="px-3 py-2">
                <button @click="openGraph({ port_id:r.port_id, title: r.hostname+' — '+r.ifName })"
                        class="text-indigo-600 hover:text-indigo-800">View</button>
              </td>
            </tr>
          </template>
          <tr x-show="filteredAllIf().length===0" x-cloak>
            <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">Tidak ada data.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- GRAPH MODAL --}}
  <template x-teleport="body">
    <div x-show="graph.open" x-cloak class="fixed inset-0 z-[9998] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
         @keydown.escape.window="graph.open=false">
      <div @click.outside="graph.open=false" class="w-full max-w-5xl rounded-xl bg-white shadow-2xl overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-indigo-600 to-blue-600">
          <h3 class="text-white font-semibold" x-text="graph.title"></h3>
          <button class="text-white/80 hover:text-white" @click="graph.open=false"><x-heroicon-o-x-mark class="h-6 w-6"/></button>
        </div>
        <div class="px-6 pt-4">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs text-gray-500">Range:</span>
            <template x-for="r in ['1h','6h','24h','7d','30d']" :key="r">
              <button class="rounded-md px-2 py-1 text-sm border"
                      :class="graph.range===r ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white hover:bg-gray-50'"
                      @click="setRange(r)">
                <span x-text="r"></span>
              </button>
            </template>
            <div class="ml-auto flex items-center gap-2 text-sm">
              <label>From</label>
              <input type="datetime-local" x-model="graph.from" class="rounded-md border-gray-300 text-sm">
              <label>To</label>
              <input type="datetime-local" x-model="graph.to" class="rounded-md border-gray-300 text-sm">
              <button class="rounded-md bg-gray-100 px-3 py-1.5 hover:bg-gray-200" @click="applyCustom()">Apply</button>
            </div>
          </div>
        </div>
        <div class="p-6">
          <img class="w-full rounded-md border" :src="graphSrc()" alt="graph">
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t text-right">
          <button class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" @click="graph.open=false">Close</button>
        </div>
      </div>
    </div>
  </template>
</div>

<script>
function nmsPage({devices, stats, urls}) {
  return {
    tab: 'devices',
    devices: devices || [],
    stats: stats || {total:0,up:0,down:0},
    urls,

    openDeviceId: null,

    // Interfaces (all)
    allIf: [],
    loadingAllIf: false,
    qIf: '',
    statusFilterIf: '',

    // Search w/ suggest
    search: { q:'', open:false, items:[] },

    // Graph
    graph: { open:false, port_id:null, title:'', range:'24h', from:'', to:'', _from:null, _to:null },

    async togglePorts(d) {
      this.openDeviceId = (this.openDeviceId === d.device_id) ? null : d.device_id;
      if (this.openDeviceId && !d._ports) {
        d._portsLoading = true;
        try {
          const r = await fetch(`${this.urls.deviceIf}/${d.device_id}/interfaces`);
          const json = await r.json();
          d._ports = json.interfaces || [];
        } catch(e) {
          d._ports = [];
        } finally { d._portsLoading = false; }
      }
    },

    openInterfacesTab() {
      this.tab = 'interfaces';
      if (!this.allIf.length) this.refreshAllInterfaces();
    },

    async refreshAllInterfaces() {
      this.loadingAllIf = true;
      try {
        const r = await fetch(this.urls.allIf);
        const j = await r.json();
        this.allIf = j.interfaces || [];
      } catch(e) {
        this.allIf = [];
      } finally { this.loadingAllIf = false; }
    },

    filteredAllIf() {
      const q = this.qIf.toLowerCase();
      return this.allIf.filter(r => {
        const okQ = !q || [r.hostname,r.ifName,r.alias,r.location,r.os,r.sysname,r.hardware]
          .some(v => (v||'').toLowerCase().includes(q));
        const okS = !this.statusFilterIf || r.ifStatus === this.statusFilterIf;
        return okQ && okS;
      });
    },

    async searchSuggest() {
      const q = this.search.q.trim();
      if (!q) { this.search.items=[]; this.search.open=false; return; }
      try {
        const r = await fetch(`${this.urls.searchIf}?q=${encodeURIComponent(q)}`);
        const j = await r.json();
        this.search.items = j.items || [];
        this.search.open  = this.search.items.length>0;
      } catch(e) {
        this.search.items = [];
        this.search.open  = false;
      }
    },

    openGraph({port_id, title}) {
      this.search.open=false;
      this.graph.open   = true;
      this.graph.port_id= port_id;
      this.graph.title  = title;
      this.graph.range  = '24h';
      this.graph.from   = '';
      this.graph.to     = '';
      this.graph._from = this.graph._to = null;
    },
    setRange(r){ this.graph.range=r; this.graph.from=''; this.graph.to=''; this.graph._from=this.graph._to=null; },
    applyCustom(){
      this.graph.range='';
      const toEpoch = s => s ? Math.floor(new Date(s).getTime()/1000) : null;
      this.graph._from = toEpoch(this.graph.from);
      this.graph._to   = toEpoch(this.graph.to);
    },
    graphSrc() {
      const base = `${this.urls.graph}?type=port_bits&id=${encodeURIComponent(this.graph.port_id)}&width=1100&height=300`;
      if (this.graph.range) return `${base}&range=${this.graph.range}`;
      const f = this.graph._from || Math.floor(Date.now()/1000 - 86400);
      const t = this.graph._to   || Math.floor(Date.now()/1000);
      return `${base}&from=${f}&to=${t}`;
    }
  }
}
</script>
@endsection
