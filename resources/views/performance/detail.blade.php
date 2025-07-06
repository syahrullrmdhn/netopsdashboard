@extends('layouts.app')
@section('title',$title)

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header Section -->
    <div class="mb-8">
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
          <p class="mt-2 text-sm text-gray-600">{{ $desc }}</p>
        </div>
        <a href="{{ route('performance.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to Summary
        </a>
      </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
      @if($type === 'utilization')
        <!-- Utilization Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tickets This Month</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              @foreach($data as $cust)
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $cust->customer }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ number_format($cust->tickets_count) }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <!-- Ticket Details Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket #</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Open Date</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              @foreach($data as $t)
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-medium text-blue-600">{{ $t->ticket_number }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ optional($t->customer)->customer }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ optional($t->open_date)->format('Y-m-d H:i') }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  @if($t->end_time)
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Closed</span>
                  @else
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Open</span>
                  @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                  @if(in_array($type,['responsiveness','quality']))
                    <div class="space-y-1">
                      @foreach($t->updates as $up)
                      <div class="flex items-start">
                        <div class="flex-shrink-0 h-5 w-5 text-gray-400">
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd" />
                          </svg>
                        </div>
                        <div class="ml-1">
                          <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($up->created_at)->format('Y-m-d H:i') }}</div>
                          <div class="text-sm">{{ \Illuminate\Support\Str::limit($up->detail, 100) }}</div>
                        </div>
                      </div>
                      @endforeach
                    </div>
                  @else
                    <span class="text-gray-400">&mdash;</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    <!-- Pagination would go here if needed -->
  </div>
</div>
@endsection