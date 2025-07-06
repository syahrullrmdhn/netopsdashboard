@extends('layouts.app')

@section('content')
{{-- 
    Komponen Alpine.js 'dashboard' diinisialisasi di sini.
    Data awal dari controller Laravel di-passing ke state Alpine menggunakan @json.
    Ini memungkinkan kita untuk mengelola tampilan dengan JavaScript di sisi klien.
--}}
<div x-data="dashboard()" x-cloak class="space-y-6">

    {{-- Bagian Statistik Cards (Dikelola oleh Alpine.js) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-card class="bg-gradient-to-br from-blue-50 to-blue-100 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Customers</p>
                    {{-- Menggunakan x-text untuk menampilkan data dari Alpine --}}
                    <p class="text-3xl font-semibold text-gray-800" x-text="stats.totalCust.toLocaleString('id-ID')"></p>
                </div>
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <x-heroicon-o-user-group class="w-6 h-6" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-br from-red-50 to-red-100 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Down Customers</p>
                    <p class="text-3xl font-semibold text-gray-800" x-text="stats.downCust.toLocaleString('id-ID')"></p>
                    {{-- Logika persentase juga dikelola oleh Alpine --}}
                    <template x-if="stats.totalCust > 0 && stats.downCust > 0">
                        <p class="text-xs text-red-500 mt-1" x-text="`${((stats.downCust / stats.totalCust) * 100).toFixed(1)}% of total`"></p>
                    </template>
                </div>
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-br from-amber-50 to-amber-100 border-l-4 border-amber-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Open Tickets</p>
                    <p class="text-3xl font-semibold text-gray-800" x-text="stats.openTickets.toLocaleString('id-ID')"></p>
                </div>
                <div class="p-3 rounded-full bg-amber-100 text-amber-600">
                    <x-heroicon-o-ticket class="w-6 h-6" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-br from-purple-50 to-purple-100 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Warning Tickets</p>
                    <p class="text-3xl font-semibold text-gray-800" x-text="stats.warnTickets.toLocaleString('id-ID')"></p>
                    <template x-if="stats.openTickets > 0 && stats.warnTickets > 0">
                        <p class="text-xs text-purple-500 mt-1" x-text="`${((stats.warnTickets / stats.openTickets) * 100).toFixed(1)}% of open`"></p>
                    </template>
                </div>
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <x-heroicon-o-bell-alert class="w-6 h-6" />
                </div>
            </div>
        </x-card>
    </div>

    {{-- Bagian Grafik (Tetap menggunakan Chart.js) --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-800">Customer Growth Trend</h3>
            <div class="flex space-x-2">
                <button id="monthlyBtn"   class="px-3 py-1 text-sm bg-blue-100 text-blue-600 rounded-md">Monthly</button>
                <button id="quarterlyBtn" class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-md">Quarterly</button>
                <button id="yearlyBtn"    class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-md">Yearly</button>
            </div>
        </div>
        <div class="h-80">
            <canvas id="growthChart"></canvas>
        </div>
    </div>

    {{-- Grafik Ticket Trend --}}
<div class="bg-white rounded-xl shadow-sm p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-800">Ticket Trend</h3>
        <div class="flex space-x-2">
            <button id="ticketMonthlyBtn"   class="px-3 py-1 text-sm bg-blue-100 text-blue-600 rounded-md">Monthly</button>
            <button id="ticketQuarterlyBtn" class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-md">Quarterly</button>
            <button id="ticketYearlyBtn"    class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-md">Yearly</button>
        </div>
    </div>
    <div class="h-80">
        <canvas id="ticketChart"></canvas>
    </div>
</div>

    {{-- Bagian Tabel Tiket Terbaru (Dikelola oleh Alpine.js) --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <div class="px-6 py-4 border-b border-gray-100">
    <h3 class="text-lg font-semibold text-gray-800">Recent Tickets</h3>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase">Ticket #</th>
          <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase">Customer</th>
          <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase">Open Date</th>
          <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase">Status</th>
          <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <template x-if="recentTickets.length === 0">
          <tr>
            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No recent tickets found.</td>
          </tr>
        </template>
        <template x-for="ticket in recentTickets" :key="ticket.id">
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900" x-text="ticket.ticket_number"></td>
            <td class="px-6 py-4 text-gray-700" x-text="ticket.customer?.customer ?? 'N/A'"></td>
            <td class="px-6 py-4 text-gray-700" x-text="formatDate(ticket.open_date)"></td>
            <td class="px-6 py-4">
              <template x-if="ticket.end_time">
                <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Closed</span>
              </template>
              <template x-if="!ticket.end_time && !ticket.alert">
                <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Open</span>
              </template>
              <template x-if="!ticket.end_time && ticket.alert">
                <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800">Warning</span>
              </template>
            </td>
            <td class="px-6 py-4">
              <a :href="`/tickets/${ticket.id}`" class="text-blue-600 hover:text-blue-900 text-sm">View</a>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</div>


</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // 1. Plugin global untuk menampilkan pesan saat chart kosong
        const emptyChartPlugin = {
            id: 'emptyChart',
            afterDraw(chart) {
                const hasData = chart.data.datasets.some(ds =>
                    ds.data.some(d => d !== null && d !== 0)
                );
                if (!hasData) {
                    const { ctx, width, height } = chart;
                    ctx.save();
                    ctx.font = '16px "Inter", sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillStyle = '#9ca3af';
                    ctx.fillText('No data available to display', width / 2, height / 2);
                    ctx.restore();
                }
            }
        };

        // 2. Alpine.js component (growth chart tetap sama)
        function dashboard() {
            return {
                stats: {
                    totalCust: {{ $totalCust ?? 0 }},
                    downCust: {{ $downCust ?? 0 }},
                    openTickets: {{ $openTickets ?? 0 }},
                    warnTickets: {{ $warnTickets ?? 0 }},
                },
                recentTickets: @json($recent ?? []),

                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-GB', {
                        day: '2-digit', month: 'short', year: 'numeric'
                    });
                },

                init() {
                    this.initChart();
                },

                initChart() {
                    // — Customer Growth Trend (tidak diubah) —
                    const chartData = {
                        monthly: {
                            labels: {!! json_encode(($monthlyData ?? collect())->keys()) !!},
                            datasets: [{
                                label: 'New Customers',
                                data: {!! json_encode(($monthlyData ?? collect())->values()) !!},
                                tension: 0.3,
                                fill: true,
                                borderWidth: 2,
                                backgroundColor: 'rgba(59,130,246,0.1)',
                                borderColor: 'rgba(59,130,246,1)'
                            }]
                        },
                        quarterly: {
                            labels: {!! json_encode(($quarterlyData ?? collect())->keys()) !!},
                            datasets: [{
                                label: 'New Customers',
                                data: {!! json_encode(($quarterlyData ?? collect())->values()) !!},
                                tension: 0.3,
                                fill: true,
                                borderWidth: 2,
                                backgroundColor: 'rgba(59,130,246,0.1)',
                                borderColor: 'rgba(59,130,246,1)'
                            }]
                        },
                        yearly: {
                            labels: {!! json_encode(($yearlyData ?? collect())->keys()) !!},
                            datasets: [{
                                label: 'New Customers',
                                data: {!! json_encode(($yearlyData ?? collect())->values()) !!},
                                tension: 0.3,
                                fill: true,
                                borderWidth: 2,
                                backgroundColor: 'rgba(59,130,246,0.1)',
                                borderColor: 'rgba(59,130,246,1)'
                            }]
                        }
                    };
                    const ctx = document.getElementById('growthChart').getContext('2d');
                    const growthChart = new Chart(ctx, {
                        type: 'line',
                        data: chartData.monthly,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { drawBorder: false } },
                                x: { grid: { display: false } }
                            }
                        },
                        plugins: [emptyChartPlugin]
                    });

                    const buttons = {
                        monthly: document.getElementById('monthlyBtn'),
                        quarterly: document.getElementById('quarterlyBtn'),
                        yearly: document.getElementById('yearlyBtn')
                    };
                    function setActiveButton(activePeriod) {
                        for (const period in buttons) {
                            const btn = buttons[period];
                            const isActive = period === activePeriod;
                            btn.classList.toggle('bg-blue-100', isActive);
                            btn.classList.toggle('text-blue-600', isActive);
                            btn.classList.toggle('bg-gray-100', !isActive);
                            btn.classList.toggle('text-gray-600', !isActive);
                        }
                    }
                    for (const period in buttons) {
                        buttons[period].addEventListener('click', () => {
                            setActiveButton(period);
                            growthChart.data = chartData[period];
                            growthChart.update();
                        });
                    }
                }
            }
        }

        // 3. Ticket Trend Chart (gunakan data yang benar)
        const ticketChartData = {
            monthly: {
                labels: {!! json_encode(($monthlyTickets ?? collect())->keys()) !!},
                datasets: [{
                    label: 'Tickets',
                    data: {!! json_encode(($monthlyTickets ?? collect())->values()) !!},
                    tension: 0.3,
                    fill: true,
                    borderWidth: 2,
                    backgroundColor: 'rgba(249,115,22,0.12)',
                    borderColor: 'rgba(249,115,22,1)'
                }]
            },
            quarterly: {
                labels: {!! json_encode(($quarterlyTickets ?? collect())->keys()) !!},
                datasets: [{
                    label: 'Tickets',
                    data: {!! json_encode(($quarterlyTickets ?? collect())->values()) !!},
                    tension: 0.3,
                    fill: true,
                    borderWidth: 2,
                    backgroundColor: 'rgba(251,191,36,0.13)',
                    borderColor: 'rgba(251,191,36,1)'
                }]
            },
            yearly: {
                labels: {!! json_encode(($yearlyTickets ?? collect())->keys()) !!},
                datasets: [{
                    label: 'Tickets',
                    data: {!! json_encode(($yearlyTickets ?? collect())->values()) !!},
                    tension: 0.3,
                    fill: true,
                    borderWidth: 2,
                    backgroundColor: 'rgba(168,85,247,0.12)',
                    borderColor: 'rgba(168,85,247,1)'
                }]
            }
        };

        const ticketCtx = document.getElementById('ticketChart').getContext('2d');
        const ticketChart = new Chart(ticketCtx, {
            type: 'line',
            data: ticketChartData.monthly,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { drawBorder: false } },
                    x: { grid: { display: false } }
                }
            },
            plugins: [emptyChartPlugin]
        });

        const ticketButtons = {
            monthly: document.getElementById('ticketMonthlyBtn'),
            quarterly: document.getElementById('ticketQuarterlyBtn'),
            yearly: document.getElementById('ticketYearlyBtn')
        };
        function setTicketActiveButton(activePeriod) {
            for (const period in ticketButtons) {
                const btn = ticketButtons[period];
                const isActive = period === activePeriod;
                btn.classList.toggle('bg-blue-100', isActive);
                btn.classList.toggle('text-blue-600', isActive);
                btn.classList.toggle('bg-gray-100', !isActive);
                btn.classList.toggle('text-gray-600', !isActive);
            }
        }
        for (const period in ticketButtons) {
            ticketButtons[period].addEventListener('click', () => {
                setTicketActiveButton(period);
                ticketChart.data = ticketChartData[period];
                ticketChart.update();
            });
        }

        // Registrasi Alpine.js
        document.addEventListener('alpine:init', () => {
            Alpine.data('dashboard', dashboard);
        });
    </script>
@endpush
