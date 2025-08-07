@extends('layouts.app')
@section('title', 'Handover History')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
  <div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="p-6 border-b border-gray-200">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold text-gray-900">
          Handover History - {{ \Illuminate\Support\Carbon::parse($today)->format('l, F j, Y') }}
        </h1>
        <div class="flex items-center space-x-2">
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
            <svg class="-ml-1 mr-1.5 h-2 w-2 text-blue-400" fill="currentColor" viewBox="0 0 8 8">
              <circle cx="4" cy="4" r="3" />
            </svg>
            {{ $logs->count() }} records
          </span>
        </div>
      </div>
    </div>

    @if($logs->isEmpty())
      <div class="p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No handover records</h3>
        <p class="mt-1 text-sm text-gray-500">There are no handover logs recorded for today.</p>
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issues</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            @foreach($logs as $i => $log)
              <tr class="@if($i % 2 === 0) bg-white @else bg-gray-50 @endif hover:bg-gray-100 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $i + 1 }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                    @if($log->shift === 'morning') bg-blue-100 text-blue-800
                    @elseif($log->shift === 'afternoon') bg-yellow-100 text-yellow-800
                    @else bg-purple-100 text-purple-800 @endif">
                    {{ ucfirst($log->shift) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ $log->created_at->format('H:i') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <svg class="h-10 w-10 rounded-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ $log->fromUser->name }}</div>
                      <div class="text-sm text-gray-500">{{ $log->fromUser->position ?? 'Staff' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <svg class="h-10 w-10 rounded-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ $log->toUser->name }}</div>
                      <div class="text-sm text-gray-500">{{ $log->toUser->position ?? 'Staff' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs break-words">
                  @if($log->issues)
                    <div class="prose prose-sm max-w-none">
                           {!! $log->issues !!}
                    </div>
                  @else
                    <span class="text-gray-400">-</span>
                  @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs break-words">
                  @if($log->notes)
                    <div class="prose prose-sm max-w-none">
                      {!! ($log->notes) !!}
                    </div>
                  @else
                    <span class="text-gray-400">-</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
        <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0">
          <div class="text-sm text-gray-500">
            Showing <span class="font-medium">{{ $logs->firstItem() }}</span> to <span class="font-medium">{{ $logs->lastItem() }}</span> of <span class="font-medium">{{ $logs->total() }}</span> results
          </div>
          @if($logs->hasPages())
            <div class="flex space-x-1">
              @if($logs->onFirstPage())
                <span class="px-3 py-1 rounded-md bg-gray-100 text-gray-400 cursor-not-allowed text-sm">Previous</span>
              @else
                <a href="{{ $logs->previousPageUrl() }}" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm">Previous</a>
              @endif

              @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm">Next</a>
              @else
                <span class="px-3 py-1 rounded-md bg-gray-100 text-gray-400 cursor-not-allowed text-sm">Next</span>
              @endif
            </div>
          @endif
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
