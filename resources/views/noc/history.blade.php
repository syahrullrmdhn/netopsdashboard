@extends('layouts.app')
@section('title', 'Handover History')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 space-y-6">
  <h1 class="text-2xl font-bold">
    Handover History for {{ \Illuminate\Support\Carbon::parse($today)->format('d F Y') }}
  </h1>

  @if($logs->isEmpty())
    <p class="text-gray-500">Belum ada handover tercatat hari ini.</p>
  @else
    <div class="bg-white shadow rounded-lg overflow-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Shift</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">From</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">To</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Issues</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @foreach($logs as $i => $log)
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-2 text-sm">{{ $i + 1 }}</td>
              <td class="px-4 py-2 text-sm">{{ ucfirst($log->shift) }}</td>
              <td class="px-4 py-2 text-sm">{{ $log->created_at->format('H:i') }}</td>
              <td class="px-4 py-2 text-sm">{{ $log->fromUser->name }}</td>
              <td class="px-4 py-2 text-sm">{{ $log->toUser->name }}</td>
              <td class="px-4 py-2 text-sm whitespace-pre-wrap">{{ $log->issues }}</td>
              <td class="px-4 py-2 text-sm whitespace-pre-wrap">{{ $log->notes }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
