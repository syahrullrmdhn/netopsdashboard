@extends('layouts.app')

@section('title', 'Trouble Ticket Report')

@section('content')
<!-- ===== CHART.JS CDN ===== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div x-data="{
    period: '7',
    start: '',
    end: '',
    customer: '',
    group: '',
    issueType: '',
    init() { this.updateDateRange(); },
    updateDateRange() {
        if (this.period !== 'custom') {
            const days = parseInt(this.period);
            this.start = new Date(new Date().setDate(new Date().getDate() - days + 1)).toISOString().slice(0,10);
            this.end = new Date().toISOString().slice(0,10);
        }
    },
    generateExportUrl(type) {
        let url = type === 'excel'
            ? `{{ route('reports.exportSpout') }}?start_date=${this.start}&end_date=${this.end}`
            : `{{ route('reports.exportPdf') }}?start_date=${this.start}&end_date=${this.end}`;
        if (this.customer) url += `&customer_id=${this.customer}`;
        if (this.group) url += `&group_id=${this.group}`;
        if (this.issueType) url += `&issue_type=${this.issueType}`;
        return url;
    },
    getCustomerName() {
        if (!this.customer) return 'All';
        const el = document.querySelector(`select[x-model='customer'] option[value='${this.customer}']`);
        return el ? el.textContent : 'All';
    },
    getGroupName() {
        if (!this.group) return 'All';
        const el = document.querySelector(`select[x-model='group'] option[value='${this.group}']`);
        return el ? el.textContent : 'All';
    },
    getIssueTypeName() {
        if (!this.issueType) return 'All';
        const el = document.querySelector(`select[x-model='issueType'] option[value='${this.issueType}']`);
        return el ? el.textContent : 'All';
    }
}" x-init="init()" x-cloak class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- ==== SUMMARY CHARTS & INFO ==== -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-blue-50 rounded-lg p-4 text-center shadow">
        <div class="text-xs uppercase text-blue-900 font-bold">Total Tickets</div>
        <div class="text-2xl font-bold text-blue-900">{{ number_format($chartData['total']) }}</div>
      </div>
      <div class="bg-green-50 rounded-lg p-4 text-center shadow">
        <div class="text-xs uppercase text-green-900 font-bold">Closed Tickets</div>
        <div class="text-2xl font-bold text-green-900">{{ number_format($chartData['total_closed']) }}</div>
      </div>
      <div class="bg-red-50 rounded-lg p-4 text-center shadow">
        <div class="text-xs uppercase text-red-900 font-bold">Open Tickets</div>
        <div class="text-2xl font-bold text-red-900">{{ number_format($chartData['total_open']) }}</div>
      </div>
      <div class="bg-indigo-50 rounded-lg p-4 text-center shadow">
        <div class="text-xs uppercase text-indigo-900 font-bold">Avg. Open/Month</div>
        <div class="text-2xl font-bold text-indigo-900">{{ number_format($chartData['avg_open_per_month'],1) }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-8">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="font-semibold mb-2 text-gray-700 text-sm">Tickets by Issue Type</div>
        <canvas id="chartByIssue" height="160"></canvas>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="font-semibold mb-2 text-gray-700 text-sm">Tickets Trend Per Month</div>
        <canvas id="chartPerMonth" height="160"></canvas>
      </div>
    </div>
    <!-- ================================ -->

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Trouble Ticket Verification Report</h1>
            <p class="text-sm text-gray-500 mt-1">Analyze and export trouble ticket data</p>
        </div>
        <div class="mt-4 md:mt-0">
            <div class="flex space-x-2">
                <a
                    :href="generateExportUrl('excel')"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                >
                    <!-- icon -->
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    Export to Excel
                </a>
                <a
                    :href="generateExportUrl('pdf')"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                >
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    Export to PDF
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 space-y-6">
            {{-- Report Period Section --}}
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-3">Report Period</h3>
                <div class="inline-flex bg-gray-50 rounded-lg p-1 border border-gray-200">
                    <template x-for="opt in [
                        {v:'1',    label:'Today'},
                        {v:'7',    label:'7 Days'},
                        {v:'30',   label:'30 Days'},
                        {v:'custom', label:'Custom Range'}
                    ]" :key="opt.v">
                        <button
                            :class="period === opt.v
                                ? 'bg-white text-blue-600 shadow-sm ring-1 ring-gray-200'
                                : 'text-gray-600 hover:bg-white hover:shadow-sm'"
                            @click.prevent="period = opt.v; updateDateRange()"
                            class="px-4 py-2 text-sm font-medium rounded-md transition-all"
                        >
                            <span x-text="opt.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Custom Date Range Section --}}
            <div x-show="period === 'custom'" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <div class="relative rounded-md shadow-sm">
                        <input
                            type="date"
                            x-model="start"
                            class="block w-full pr-10 pl-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <div class="relative rounded-md shadow-sm">
                        <input
                            type="date"
                            x-model="end"
                            class="block w-full pr-10 pl-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                    </div>
                </div>
            </div>

            {{-- Filters Section --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                    <select
                        x-model="customer"
                        class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md shadow-sm"
                    >
                        <option value="">All Customers</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->customer }} ({{ $c->cid_abh }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Group</label>
                    <select
                        x-model="group"
                        class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md shadow-sm"
                    >
                        <option value="">All Groups</option>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}">{{ $g->group_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type of Issue</label>
                   <select x-model="issueType"
                                class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md shadow-sm">
                            <option value="">All Types</option>
                            @foreach($issueTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                </div>
            </div>
        </div>

        {{-- Summary Preview --}}
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Date Range</h4>
                    <p class="text-sm font-medium text-gray-900" x-text="start + ' to ' + end"></p>
                </div>
                <div class="hidden md:block">
                    <div class="flex space-x-4">
                        <template x-if="customer">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Customer</h4>
                                <p class="text-sm font-medium text-gray-900" x-text="getCustomerName()"></p>
                            </div>
                        </template>
                        <template x-if="group">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Group</h4>
                                <p class="text-sm font-medium text-gray-900" x-text="getGroupName()"></p>
                            </div>
                        </template>
                        <template x-if="issueType">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Type of Issue</h4>
                                <p class="text-sm font-medium text-gray-900" x-text="getIssueTypeName()"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CHART.JS SCRIPTS --}}
<script>
document.addEventListener("alpine:init", () => {
    // Data from backend
    const issueTypeLabels = {!! json_encode(array_map('ucwords', array_keys($chartData['by_issue_type']))) !!};
    const issueTypeData   = {!! json_encode(array_values($chartData['by_issue_type'])) !!};
    const monthLabels     = {!! json_encode(array_keys($chartData['per_month'])) !!};
    const monthData       = {!! json_encode(array_values($chartData['per_month'])) !!};

    // Bar chart for Issue Type
    new Chart(document.getElementById('chartByIssue').getContext('2d'), {
        type: 'bar',
        data: {
            labels: issueTypeLabels,
            datasets: [{
                label: 'Tickets',
                data: issueTypeData,
                backgroundColor: '#1e293b'
            }]
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });

    // Line chart for Monthly Trend
    new Chart(document.getElementById('chartPerMonth').getContext('2d'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Tickets per Month',
                data: monthData,
                fill: false,
                borderColor: '#0ea5e9',
                backgroundColor: '#bae6fd',
                tension: 0.3
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
@endsection
