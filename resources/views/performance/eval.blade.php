@extends('layouts.app')
@section('title','Evaluation Dashboard')

@section('content')
<div x-data="evalData()" x-init="init()" class="min-h-screen bg-gray-50 py-8">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Performance Evaluation</h1>
      <p class="mt-2 text-sm text-gray-600">Comprehensive analysis of system performance metrics</p>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <template x-for="t in tabs" :key="t.id">
          <button @click="tab=t.id"
            :class="tab===t.id
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
            x-text="t.label"></button>
        </template>
      </nav>
    </div>

    <!-- Recap Chart -->
    <div x-show="tab==='recap'" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Monthly Ticket Volume</h2>
            <p class="mt-1 text-sm text-gray-500">Ticket count over the last 6 months</p>
          </div>
          <button @click="downloadRecap()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Export CSV
          </button>
        </div>
      </div>
      <div class="p-6">
        <div class="h-80">
          <canvas id="recapChart"></canvas>
        </div>
        <div class="mt-6 overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tickets</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <template x-for="(m,i) in recapLabels" :key="i">
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="m"></td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right" x-text="recapData[i]"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- KPI Radar -->
    <div x-show="tab==='performance'" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Key Performance Indicators</h2>
            <p class="mt-1 text-sm text-gray-500">Core system performance metrics</p>
          </div>
          <button @click="downloadPerformance()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Export CSV
          </button>
        </div>
      </div>
      <div class="p-6">
        <div class="h-80">
          <canvas id="perfChart"></canvas>
        </div>
        <div class="mt-6 overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metric</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <template x-for="(m,i) in perfLabels" :key="i">
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="m"></td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right" x-text="perfData[i] + '%'"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Operational Review -->
    <div x-show="tab==='operational'" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Response Time Analysis</h2>
            <p class="mt-1 text-sm text-gray-500">Average first response time by week</p>
          </div>
          <button @click="downloadOperational()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Export CSV
          </button>
        </div>
      </div>
      <div class="p-6">
        <div class="h-80">
          <canvas id="opChart"></canvas>
        </div>
        <div class="mt-6 overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Week</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Response Time</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <template x-for="(w,i) in opLabels" :key="i">
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="w"></td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right" x-text="opData[i] + ' min'"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Utilization -->
    <div x-show="tab==='utilization'" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Resource Utilization</h2>
            <p class="mt-1 text-sm text-gray-500">Customer utilization percentages</p>
          </div>
          <button @click="downloadAllUtilization()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Export All CSV
          </button>
        </div>
      </div>
      <div class="p-6">
        <div class="h-80">
          <canvas id="utilChart"></canvas>
        </div>
        <div class="mt-6 overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Utilization</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <template x-for="(c,i) in utilLabels" :key="i">
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                    <a href="#" class="hover:underline" x-text="c"></a>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right" x-text="utilData[i] + '%'"></td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                    <button @click="downloadUtilization(c,utilData[i])" class="inline-flex items-center px-2.5 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                      Export
                    </button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function evalData(){
  return {
    tabs:[
      {id:'recap',label:'Ticket Recap'},
      {id:'performance',label:'Performance KPI'},
      {id:'operational',label:'Operational Metrics'},
      {id:'utilization',label:'Utilization'}
    ],
    tab:'recap',
    recapLabels:@json($recapLabels), recapData:@json($recapData),
    perfLabels:@json($perfLabels),   perfData:@json($perfData),
    opLabels:@json($opLabels),       opData:@json($opData),
    utilLabels:@json($utilLabels),   utilData:@json($utilData),

    init(){
      /* Recap Bar */
      new Chart(document.getElementById('recapChart').getContext('2d'), {
        type:'bar', 
        data:{
          labels:this.recapLabels,
          datasets:[{
            data:this.recapData,
            backgroundColor:'rgba(59, 130, 246, 0.7)',
            borderColor:'rgba(59, 130, 246, 1)',
            borderWidth:1,
            borderRadius:6,
            barPercentage:0.7,
            categoryPercentage:0.8
          }]
        }, 
        options:{
          maintainAspectRatio:false,
          responsive:true,
          scales:{
            x:{
              grid:{display:false, drawBorder:false},
              ticks:{color:'#6B7280'}
            },
            y:{
              beginAtZero:true,
              grid:{
                color:'rgba(0,0,0,0.03)',
                drawBorder:false
              },
              ticks:{
                color:'#6B7280',
                padding:10
              }
            }
          },
          plugins:{
            legend:{display:false},
            tooltip:{
              backgroundColor:'#1F2937',
              titleColor:'#fff',
              bodyColor:'#fff',
              cornerRadius:6,
              padding:12,
              displayColors:false
            }
          }
        }
      });
      
      /* KPI Radar */
      new Chart(document.getElementById('perfChart').getContext('2d'), {
        type:'radar', 
        data:{
          labels:this.perfLabels,
          datasets:[{
            data:this.perfData,
            backgroundColor:'rgba(59, 130, 246, 0.2)',
            borderColor:'#3B82F6',
            borderWidth:2,
            pointBackgroundColor:'#3B82F6',
            pointRadius:5,
            pointHoverRadius:7
          }]
        }, 
        options:{
          maintainAspectRatio:false,
          responsive:true,
          scales:{
            r:{
              angleLines:{color:'rgba(0,0,0,0.05)'},
              grid:{color:'rgba(0,0,0,0.05)'},
              suggestedMin:0,
              suggestedMax:100,
              ticks:{
                stepSize:20,
                color:'#6B7280',
                backdropColor:'transparent'
              },
              pointLabels:{
                color:'#374151',
                font:{size:12}
              }
            }
          },
          plugins:{
            legend:{display:false},
            tooltip:{
              backgroundColor:'#1F2937',
              titleColor:'#fff',
              bodyColor:'#fff',
              cornerRadius:6,
              padding:12,
              callbacks:{
                label:function(context) {
                  return ' ' + context.raw + '%';
                }
              }
            }
          }
        }
      });
      
      /* Operational Line */
      new Chart(document.getElementById('opChart').getContext('2d'), {
        type:'line', 
        data:{
          labels:this.opLabels,
          datasets:[{
            data:this.opData,
            borderColor:'#10B981',
            backgroundColor:'rgba(16, 185, 129, 0.1)',
            borderWidth:2,
            tension:0.3,
            fill:true,
            pointRadius:4,
            pointHoverRadius:6,
            pointBackgroundColor:'#10B981'
          }]
        }, 
        options:{
          maintainAspectRatio:false,
          responsive:true,
          scales:{
            x:{
              grid:{display:false, drawBorder:false},
              ticks:{color:'#6B7280'}
            },
            y:{
              beginAtZero:true,
              grid:{
                color:'rgba(0,0,0,0.03)',
                drawBorder:false
              },
              ticks:{
                color:'#6B7280',
                padding:10,
                callback:function(value) {
                  return value + 'm';
                }
              }
            }
          },
          plugins:{
            legend:{display:false},
            tooltip:{
              backgroundColor:'#1F2937',
              titleColor:'#fff',
              bodyColor:'#fff',
              cornerRadius:6,
              padding:12,
              displayColors:false,
              callbacks:{
                label:function(context) {
                  return ' ' + context.parsed.y + ' min';
                }
              }
            }
          }
        }
      });
      
      /* Utilization Line */
      new Chart(document.getElementById('utilChart').getContext('2d'), {
        type:'line', 
        data:{
          labels:this.utilLabels,
          datasets:[{
            data:this.utilData,
            borderColor:'#F59E0B',
            backgroundColor:'rgba(245, 158, 11, 0.1)',
            borderWidth:2,
            tension:0.3,
            fill:true,
            pointRadius:4,
            pointHoverRadius:6,
            pointBackgroundColor:'#F59E0B'
          }]
        }, 
        options:{
          maintainAspectRatio:false,
          responsive:true,
          scales:{
            x:{
              grid:{display:false, drawBorder:false},
              ticks:{color:'#6B7280'}
            },
            y:{
              beginAtZero:true,
              max:100,
              grid:{
                color:'rgba(0,0,0,0.03)',
                drawBorder:false
              },
              ticks:{
                color:'#6B7280',
                padding:10,
                callback:function(value) {
                  return value + '%';
                }
              }
            }
          },
          plugins:{
            legend:{display:false},
            tooltip:{
              backgroundColor:'#1F2937',
              titleColor:'#fff',
              bodyColor:'#fff',
              cornerRadius:6,
              padding:12,
              displayColors:false,
              callbacks:{
                label:function(context) {
                  return ' ' + context.parsed.y + '%';
                }
              }
            }
          }
        }
      });
    },
    
    downloadCSV(fn,hdr,rows){
      let csv=hdr.join(',')+'\n';
      rows.forEach(r=>csv+=r.join(',')+'\n');
      let b=new Blob([csv],{type:'text/csv'});
      let u=URL.createObjectURL(b);
      let a=document.createElement('a');
      a.href=u;
      a.download=fn+'.csv';
      a.click();
      URL.revokeObjectURL(u);
    },
    
    downloadRecap(){
      this.downloadCSV(
        'ticket_recap',
        ['Month','Tickets'],
        this.recapLabels.map((m,i)=>[m,this.recapData[i]])
      );
    },
    
    downloadPerformance(){
      this.downloadCSV(
        'performance_metrics',
        ['Metric','Score'],
        this.perfLabels.map((m,i)=>[m,this.perfData[i]+'%'])
      );
    },
    
    downloadOperational(){
      this.downloadCSV(
        'response_times',
        ['Week','ResponseTime(min)'],
        this.opLabels.map((w,i)=>[w,this.opData[i]])
      );
    },
    
    downloadAllUtilization(){
      this.downloadCSV(
        'utilization_report',
        ['Customer','Utilization(%)'],
        this.utilLabels.map((c,i)=>[c,this.utilData[i]+'%'])
      );
    },
    
    downloadUtilization(c,v){
      this.downloadCSV(
        `utilization_${c.replace(/[^a-z0-9]/gi,'_').toLowerCase()}`,
        ['Customer','Utilization(%)'],
        [[c,v+'%']]
      );
    }
  }
}
</script>
@endpush