@extends('layouts.app')
@section('title','Performance Summary')

@section('content')
<div class="min-h-screen bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Performance Dashboard</h1>
      <p class="mt-2 text-sm text-gray-600">Comprehensive overview of system performance metrics</p>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Navigation Card -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          <div class="p-6 bg-gradient-to-r from-blue-600 to-blue-800">
            <h2 class="text-lg font-semibold text-white">Performance Navigation</h2>
            <p class="mt-1 text-sm text-blue-100">Access detailed performance metrics</p>
          </div>
          <div class="p-6">
            <ul class="space-y-3">
              <li>
                <a href="{{ route('performance.eval') }}" class="flex items-center p-3 rounded-lg hover:bg-blue-50 group transition-colors">
                  <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 group-hover:bg-blue-200 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0-01-2-2z" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">Evaluation Dashboard</h3>
                    <p class="text-xs text-gray-500">Interactive performance charts</p>
                  </div>
                  <div class="ml-auto text-gray-400 group-hover:text-blue-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </a>
              </li>
              @foreach(['reliability','availability','responsiveness','quality','utilization'] as $metric)
                <li>
                  <a href="{{ route('performance.detail', $metric) }}" class="flex items-center p-3 rounded-lg hover:bg-blue-50 group transition-colors">
                    <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-gray-100 text-gray-600 group-hover:bg-blue-100 group-hover:text-blue-600 transition-colors">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                      </svg>
                    </div>
                    <div class="ml-4">
                      <h3 class="text-sm font-medium text-gray-900">Detail {{ ucfirst($metric) }}</h3>
                      <p class="text-xs text-gray-500">In-depth metric analysis</p>
                    </div>
                    <div class="ml-auto text-gray-400 group-hover:text-blue-600 transition-colors">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </a>
                </li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>

      <!-- Main Chart Card -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-gray-900">Ticket Volume (6 Months)</h2>
                <p class="mt-1 text-sm text-gray-500">Monthly ticket count overview</p>
              </div>
              <div class="flex space-x-2">
                <button type="button" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                  Export
                </button>
              </div>
            </div>
          </div>
          <div class="p-6">
            <div class="h-80">
              <canvas id="indexRecapChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
      @foreach ([
          ['label'=>'Reliability','value'=>$reliability,'icon'=>'check','color'=>'green'],
          ['label'=>'Availability','value'=>$availability,'icon'=>'clock','color'=>'blue'],
          ['label'=>'Responsiveness','value'=>$responsiveness,'icon'=>'bolt','color'=>'purple'],
          ['label'=>'Quality','value'=>$quality,'icon'=>'thumbs-up','color'=>'yellow'],
          ['label'=>'Utilization','value'=>$utilization,'icon'=>'chart-bar','color'=>'red'],
      ] as $stat)
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0 bg-{{ $stat['color'] }}-100 rounded-md p-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-{{ $stat['color'] }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  @switch($stat['icon'])
                    @case('check')
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      @break
                    @case('clock')
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      @break
                    @case('bolt')
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                      @break
                    @case('thumbs-up')
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 9V5a3 3 0 00-6 0v4H5l1 9h6l1-9h1z" />
                      @break
                    @case('chart-bar')
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 17v-6a2 2 0 00-2-2h-2a2 2 0 00-2 2v6M12 13v-4a2 2 0 00-2-2H8a2 2 0 00-2 2v4" />
                      @break
                  @endswitch
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">{{ $stat['label'] }}</dt>
                  <dd class="text-2xl font-semibold text-gray-900">{{ $stat['value'] }}%</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const idxLabels = @json($recapLabels);
  const idxData   = @json($recapData);

  new Chart(
    document.getElementById('indexRecapChart').getContext('2d'),
    {
      type: 'bar',
      data: {
        labels: idxLabels,
        datasets: [{
          label: 'Tickets',
          data: idxData,
          backgroundColor: '#3B82F6',
          borderRadius: 4,
          barPercentage: 0.8,
          categoryPercentage: 0.8
        }]
      },
      options: {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#1F2937',
            titleFont: { size: 14, weight: 'bold' },
            bodyFont: { size: 12 },
            padding: 12,
            cornerRadius: 8,
            displayColors: false
          }
        },
        scales: {
          x: { 
            grid: { display: false, drawBorder: false },
            ticks: { color: '#6B7280' }
          },
          y: { 
            beginAtZero: true, 
            grid: { 
              color: 'rgba(0,0,0,0.05)',
              drawBorder: false
            }, 
            ticks: { 
              color: '#6B7280',
              padding: 10
            } 
          }
        }
      }
    }
  );
</script>
@endpush
