@extends('layouts.app')

@section('title',"Sensor {$sensorId} – {$sensorName}")

@section('content')
  <div class="mb-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-800">{{ $sensorName }}</h1>
        <p class="text-sm text-gray-500">Sensor ID: {{ $sensorId }}</p>
      </div>
      <a href="{{ route('sla.index') }}"
         class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to all sensors
      </a>
    </div>
    
    <div class="mt-4 border-b border-gray-200"></div>
  </div>

  <div x-data="sensorDashboard({
        host:'{{ rtrim(config('prtg.host'), '/') }}',
        user:'{{ config('prtg.username') }}',
        passhash:'{{ config('prtg.passhash') }}',
        sensorId: {{ $sensorId }},
        sensorName: '{{ $sensorName }}'
      })"
       x-init="init()"
       class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">

    {{-- Time Range Selector --}}
    <div class="flex space-x-2">
      <template x-for="(range, index) in timeRanges" :key="index">
        <button 
          @click="setSpan(index)"
          :class="{
            'bg-blue-50 text-blue-700 border-blue-200': span === index,
            'text-gray-600 hover:text-gray-800 hover:bg-gray-50 border-gray-200': span !== index
          }"
          class="px-4 py-2 text-sm font-medium rounded-md border transition-colors"
          x-text="range.label">
        </button>
      </template>
    </div>

    {{-- Chart Container --}}
    <div class="bg-gray-50 rounded-md border border-gray-200 p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-medium text-gray-800" x-text="sensorName"></h3>
        <span class="text-sm text-gray-500" x-text="timeRangeLabel"></span>
      </div>
      <div class="overflow-auto">
        <img :src="chartUrl" alt="PRTG Traffic Chart" class="w-full h-auto min-w-[800px]" loading="lazy"/>
      </div>
    </div>

    {{-- Statistics Panel --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-gray-50 rounded-md border border-gray-200 p-4">
        <h4 class="text-sm font-medium text-gray-500 mb-1">Median (50th Percentile)</h4>
        <p class="text-2xl font-semibold text-gray-800" x-text="percentile50"></p>
      </div>
      <div class="bg-gray-50 rounded-md border border-gray-200 p-4">
        <h4 class="text-sm font-medium text-gray-500 mb-1">95th Percentile</h4>
        <p class="text-2xl font-semibold text-gray-800" x-text="percentile95"></p>
      </div>
      <div class="bg-gray-50 rounded-md border border-gray-200 p-4">
        <h4 class="text-sm font-medium text-gray-500 mb-1">Data Resolution</h4>
        <p class="text-2xl font-semibold text-gray-800">5-minute avg</p>
      </div>
    </div>

    {{-- Actions --}}
    <div class="flex justify-end pt-2">
      <a :href="csvUrl"
         download
         class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        Download CSV Data
      </a>
    </div>

  </div>
@endsection

@push('scripts')
<script>
function sensorDashboard({host, user, passhash, sensorId, sensorName}) {
  return {
    timeRanges: [
      { label: 'Live View', days: 0 },
      { label: 'Last 48 Hours', days: 2 },
      { label: 'Last 30 Days', days: 30 },
      { label: 'Last Year', days: 365 }
    ],
    span: 2,
    chartUrl: '',
    csvUrl: '',
    percentile50: '–',
    percentile95: '–',
    sdate: '',
    edate: '',
    isLoading: false,

    get timeRangeLabel() {
      return this.timeRanges[this.span].label;
    },

    init() {
      this.setSpan(this.span);
    },

    setSpan(idx) {
      this.span = idx;
      this.isLoading = true;
      
      const now = new Date();
      const past = new Date(now);
      past.setDate(now.getDate() - this.timeRanges[this.span].days);

      const fmt = d => [
        d.getFullYear(),
        String(d.getMonth()+1).padStart(2,'0'),
        String(d.getDate()).padStart(2,'0'),
        String(d.getHours()).padStart(2,'0'),
        String(d.getMinutes()).padStart(2,'0'),
        String(d.getSeconds()).padStart(2,'0'),
      ].join('-');

      this.sdate = fmt(past);
      this.edate = fmt(now);

      // Build URLs
      this.chartUrl = `${host}/chart.png`
        + `?type=graph&width=800&height=250`
        + `&graphid=${this.span}`
        + `&avg=300`
        + `&id=${sensorId}`
        + `&username=${user}&passhash=${passhash}`;

      this.csvUrl = `${host}/api/historicdata.csv`
        + `?id=${sensorId}`
        + `&avg=300`
        + `&sdate=${this.sdate}&edate=${this.edate}`
        + `&username=${user}&passhash=${passhash}`;

      this.fetchForPercentiles();
    },

    async fetchForPercentiles() {
      try {
        const resp = await fetch(`${host}/api/historicdata.json?` + new URLSearchParams({
          id: sensorId,
          avg: 300,
          usecaption: 1,
          sdate: this.sdate,
          edate: this.edate,
          username: user,
          passhash: passhash
        }));
        
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
        
        const json = await resp.json();
        const arr = (json.historicdata || [])
          .map(d => parseFloat(d.value))
          .filter(v => !isNaN(v));
          
        this.calculatePercentiles(arr);
      } catch(e) {
        console.error("Failed to load percentiles:", e);
        this.percentile50 = '–';
        this.percentile95 = '–';
      } finally {
        this.isLoading = false;
      }
    },

    calculatePercentiles(arr) {
      if (!arr.length) {
        this.percentile50 = this.percentile95 = '–';
        return;
      }
      
      arr.sort((a, b) => a - b);
      this.percentile50 = this.formatValue(this.pctl(arr, 50));
      this.percentile95 = this.formatValue(this.pctl(arr, 95));
    },

    pctl(sortedArr, p) {
      const idx = (p/100)*(sortedArr.length-1);
      const lo = Math.floor(idx), hi = Math.ceil(idx);
      if (lo === hi) return sortedArr[lo];
      return sortedArr[lo] + (sortedArr[hi]-sortedArr[lo])*(idx-lo);
    },
    
    formatValue(value) {
      return value.toFixed(3);
    }
  }
}

document.addEventListener('alpine:init', () => {
  Alpine.data('sensorDashboard', sensorDashboard);
});
</script>
@endpush