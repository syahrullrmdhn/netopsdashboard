@extends('layouts.app')

@section('title', "Device: {$device}")

@section('content')
@php
    $host     = rtrim(config('prtg.host',''), '/');
    $user     = config('prtg.username','');
    $passhash = config('prtg.passhash','');
@endphp

<div x-data="{
        modalOpen: false,
        modalSrc: '',
        modalUrl: '',
        zoomLevel: 100,
        graphInterval: '0',
        customFrom: '',
        customTo: '',
        selectedSensor: null,
        resetZoom() { this.zoomLevel = 100; },
        zoomIn() { if (this.zoomLevel < 250) this.zoomLevel += 20; },
        zoomOut() { if (this.zoomLevel > 60) this.zoomLevel -= 20; },
        updateGraph(sensorId) {
            this.selectedSensor = sensorId;
            let url = '{{ $host }}/chart.png?id=' + sensorId;
            url += '&width=1000&height=350&username={{ urlencode($user) }}&passhash={{ urlencode($passhash) }}';

            if (this.graphInterval === '4') {
                if (this.customFrom && this.customTo) {
                    // Format PRTG: YYYY-MM-DD-HH-mm-ss
                    let sdate = this.customFrom.replace('T','-').replace(/:/g,'-');
                    let edate = this.customTo.replace('T','-').replace(/:/g,'-');
                    url += '&graphid=4'
                        + '&sdate=' + sdate
                        + '&edate=' + edate;
                } else {
                    url += '&graphid=0';
                }
            } else {
                url += '&graphid=' + this.graphInterval;
            }
            this.modalSrc = url;
            this.modalUrl = url;
            this.modalOpen = true;
            this.zoomLevel = 100;
        }
    }"
    class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6"
>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Device Monitoring</h1>
            <p class="text-lg text-gray-600">{{ $device }}</p>
        </div>
        <a href="{{ route('sla.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to all devices
        </a>
    </div>

    {{-- Error Alert --}}
    @if($error)
        <div class="rounded-md bg-red-50 p-4 border border-red-100">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Failed to fetch sensors</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>{{ $error }}</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        @if(count($sensors))
            {{-- Interval Selector --}}
            <div class="mb-4 flex flex-wrap items-center space-x-4">
                <span class="font-medium text-gray-700">Graph Interval:</span>
                <select x-model="graphInterval" class="border rounded px-2 py-1 text-sm">
                    <option value="0">Detail/Now</option>
                    <option value="1">Last 2 Days</option>
                    <option value="2">Last 30 Days</option>
                    <option value="3">Last 365 Days</option>
                    <option value="4">Custom Range</option>
                </select>
                <template x-if="graphInterval === '4'">
                    <div class="flex items-center space-x-2">
                        <input type="datetime-local" x-model="customFrom" class="border rounded px-2 py-1 text-sm" placeholder="From">
                        <span>to</span>
                        <input type="datetime-local" x-model="customTo" class="border rounded px-2 py-1 text-sm" placeholder="To">
                    </div>
                </template>
            </div>
            {{-- Sensors Table --}}
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sensor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($sensors as $s)
                                @php
                                    $hasId = !empty($s['objid']);
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">
                                        {{ $s['objid'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $s['name'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $s['lastvalue'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $cls = ($s['status'] ?? '') === 'Up'
                                                ? 'bg-green-100 text-green-800'
                                                : ((($s['status'] ?? '') === 'Down')
                                                    ? 'bg-red-100 text-red-800'
                                                    : 'bg-yellow-100 text-yellow-800');
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $cls }}">
                                            {{ $s['status'] ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                        {!! $s['message'] ?? '-' !!}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @if($hasId)
                                        <div class="flex space-x-2">
                                            <button
                                                @click="updateGraph({{ $s['objid'] }})"
                                                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                            >
                                                View Graph
                                            </button>
                                            <a :href="modalUrl"
                                               x-show="modalOpen && selectedSensor === {{ $s['objid'] }}"
                                               target="_blank"
                                               class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                            >
                                                Open
                                            </a>
                                        </div>
                                        @else
                                        <span class="text-xs text-gray-400">No graph</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">No sensors found</h3>
                <p class="mt-1 text-sm text-gray-500">This device doesn't have any sensors configured.</p>
            </div>
        @endif
    @endif

    {{-- Graph Modal --}}
    <div
        x-show="modalOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Background overlay --}}
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75" @click="modalOpen = false"></div>
            </div>

            {{-- Modal panel --}}
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Sensor Graph</h3>
                        <div class="flex space-x-2">
                            <button @click="zoomOut" class="p-1 rounded-md hover:bg-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                </svg>
                            </button>
                            <span class="text-sm text-gray-500 px-2 py-1" x-text="zoomLevel + '%'"></span>
                            <button @click="zoomIn" class="p-1 rounded-md hover:bg-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </button>
                            <button @click="resetZoom" class="text-sm text-gray-500 hover:text-gray-700 px-2">
                                Reset
                            </button>
                        </div>
                        <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="overflow-auto bg-gray-100 p-2 flex justify-center">
                        <img 
                            :src="modalSrc" 
                            alt="Sensor Live Graph" 
                            class="bg-white shadow-sm border border-gray-200 transition-all duration-200"
                            :style="'max-width: 100%; width: 1000px; height: 350px; transform: scale(' + (zoomLevel/100) + '); transform-origin: center;'"
                        />
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <a 
                        :href="modalUrl" 
                        target="_blank"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                    >
                        Open in New Tab
                    </a>
                    <button 
                        @click="modalOpen = false" 
                        type="button" 
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
