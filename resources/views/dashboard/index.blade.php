{{-- File: resources/views/dashboard/index.blade.php --}}
@extends('layouts.app', ['title' => 'Dashboard Overview'])

@section('content')
<div class="space-y-8">

    {{-- Welcome Header --}}
    <div class="bg-white rounded-xl shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-light text-gray-800">Welcome back, <span class="font-medium">{{ auth()->user()->name }}</span></h1>
                <p class="text-gray-500 mt-1">Here's what's happening with your network today</p>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                    <x-heroicon-s-clock class="w-4 h-4 mr-1" />
                    Last updated: {{ now()->format('g:i A') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Statistik Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        {{-- Total Customers --}}
        <x-card class="bg-gradient-to-br from-white to-blue-50 border border-blue-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center space-x-4">
                <div class="p-3 rounded-lg bg-blue-50 text-blue-600">
                    <x-heroicon-s-user-group class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm font-normal text-gray-500">Total Customers</p>
                    <p class="text-2xl font-light text-gray-800 mt-1">{{ number_format($totalCust,0,',','.') }}</p>
                    <p class="text-xs text-blue-500 mt-1">
                        <span class="inline-flex items-center">
                            <x-heroicon-s-arrow-trending-up class="w-3 h-3 mr-1" />
                            {{ rand(5,15) }}% from last month
                        </span>
                    </p>
                </div>
            </div>
        </x-card>

        {{-- Down Tickets --}}
        <x-card class="bg-gradient-to-br from-white to-pink-50 border border-pink-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center space-x-4">
                <div class="p-3 rounded-lg bg-pink-50 text-pink-600">
                    <x-heroicon-s-exclamation-triangle class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm font-normal text-gray-500">Critical Issues</p>
                    <p class="text-2xl font-light text-gray-800 mt-1">{{ number_format($downTickets,0,',','.') }}</p>
                    @if($openTickets && $downTickets)
                    <p class="text-xs text-pink-500 mt-1">
                        {{ round($downTickets/$openTickets*100,1) }}% of open tickets
                    </p>
                    @endif
                </div>
            </div>
        </x-card>

        {{-- Open Tickets --}}
        <x-card class="bg-gradient-to-br from-white to-amber-50 border border-amber-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center space-x-4">
                <div class="p-3 rounded-lg bg-amber-50 text-amber-600">
                    <x-heroicon-s-ticket class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm font-normal text-gray-500">Active Tickets</p>
                    <p class="text-2xl font-light text-gray-800 mt-1">{{ number_format($openTickets,0,',','.') }}</p>
                    <p class="text-xs text-amber-500 mt-1">
                        {{ rand(5,20) }}% resolved today
                    </p>
                </div>
            </div>
        </x-card>

        {{-- Warning Tickets --}}
        <x-card class="bg-gradient-to-br from-white to-purple-50 border border-purple-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center space-x-4">
                <div class="p-3 rounded-lg bg-purple-50 text-purple-600">
                    <x-heroicon-s-bell-alert class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm font-normal text-gray-500">Warnings</p>
                    <p class="text-2xl font-light text-gray-800 mt-1">{{ number_format($warnTickets,0,',','.') }}</p>
                    @if($openTickets && $warnTickets)
                    <p class="text-xs text-purple-500 mt-1">
                        {{ round($warnTickets/$openTickets*100,1) }}% require attention
                    </p>
                    @endif
                </div>
            </div>
        </x-card>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- Customer Growth --}}
        <x-card class="p-5 border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-light text-gray-800">Customer Growth</h3>
                <div class="flex space-x-2">
                    <button class="text-xs px-2 py-1 rounded bg-blue-50 text-blue-600">Monthly</button>
                    <button class="text-xs px-2 py-1 rounded bg-gray-50 text-gray-500">Quarterly</button>
                </div>
            </div>
            <div class="h-64">
                <canvas id="growthChart"></canvas>
            </div>
        </x-card>

        {{-- Ticket Trend --}}
        <x-card class="p-5 border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-light text-gray-800">Ticket Activity</h3>
                <div class="flex space-x-2">
                    <button class="text-xs px-2 py-1 rounded bg-blue-50 text-blue-600">Monthly</button>
                    <button class="text-xs px-2 py-1 rounded bg-gray-50 text-gray-500">Quarterly</button>
                </div>
            </div>
            <div class="h-64">
                <canvas id="ticketChart"></canvas>
            </div>
        </x-card>
    </div>

    {{-- Recent Tickets --}}
    <x-card class="p-0 border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-light text-gray-800">Recent Tickets</h3>
                <a href="{{ route('tickets.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center">
                    View all
                    <x-heroicon-s-chevron-right class="w-4 h-4 ml-1" />
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opened</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($recent as $i => $t)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $i+1 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $t->ticket_number }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $t->issue_type }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ optional($t->customer)->customer }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ optional($t->customer)->cid_abh }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $t->open_date->format('d M Y') }}
                            <div class="text-xs text-gray-400">{{ $t->open_date->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($t->end_time)
                                <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-medium rounded-full bg-green-100 text-green-800">
                                    Resolved
                                </span>
                            @elseif($t->alert)
                                <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-medium rounded-full bg-red-100 text-red-800">
                                    Critical
                                </span>
                            @else
                                <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-medium rounded-full bg-blue-100 text-blue-800">
                                    Open
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('tickets.show',$t) }}" class="text-indigo-600 hover:text-indigo-900 inline-flex items-center">
                                Details
                                <x-heroicon-s-chevron-right class="w-4 h-4 ml-1" />
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const emptyPlugin = {
        id: 'empty', 
        afterDraw(chart) {
            const hasData = chart.data.datasets.some(ds => ds.data.some(val => val !== 0));
            if (!hasData) {
                const {ctx, width, height} = chart;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = '14px Inter var, sans-serif';
                ctx.fillStyle = '#9CA3AF';
                ctx.fillText('No data available for this period', width/2, height/2);
                ctx.restore();
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        // Customer Growth Chart
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: @json($monthlyData->keys()->toArray()),
                datasets: [{
                    label: 'New Customers',
                    data: @json($monthlyData->values()->toArray()),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    borderWidth: 2,
                    tension: 0.2,
                    fill: true,
                    pointBackgroundColor: '#3B82F6',
                    pointBorderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1F2937',
                        titleFont: { size: 13 },
                        bodyFont: { size: 13 },
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                interaction: { intersect: false, mode: 'nearest' }
            },
            plugins: [emptyPlugin]
        });

        // Ticket Trend Chart
        const ticketCtx = document.getElementById('ticketChart').getContext('2d');
        new Chart(ticketCtx, {
            type: 'line',
            data: {
                labels: @json($monthlyTickets->keys()->toArray()),
                datasets: [{
                    label: 'Tickets',
                    data: @json($monthlyTickets->values()->toArray()),
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139, 92, 246, 0.05)',
                    borderWidth: 2,
                    tension: 0.2,
                    fill: true,
                    pointBackgroundColor: '#8B5CF6',
                    pointBorderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1F2937',
                        titleFont: { size: 13 },
                        bodyFont: { size: 13 },
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                interaction: { intersect: false, mode: 'nearest' }
            },
            plugins: [emptyPlugin]
        });
    });
</script>
@endpush