@extends('layouts.app')

@section('title', 'Ticket Management')

@section('content')
<div class="space-y-6">
  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-gray-800">Ticket Management</h1>
      <p class="text-sm text-gray-600 mt-1">Manage and track all support tickets</p>
    </div>
    <a href="{{ route('tickets.create') }}"
       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
      <svg class="-ml-0.5 mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
      </svg>
      New Ticket
    </a>
  </div>

  {{-- Table Container --}}
  <div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tickets</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timeline</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @foreach($tickets as $i => $t)
          <tr class="hover:bg-gray-50">
            {{-- Number --}}
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ $i + 1 + ($tickets->currentPage()-1)*$tickets->perPage() }}
            </td>

            {{-- Customer Info --}}
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ optional($t->customer)->customer }}</div>
              <div class="text-sm text-gray-500">{{ optional($t->customer)->cid_abh }}</div>
            </td>

            {{-- Supplier Info --}}
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ optional($t->customer->supplier)->nama_supplier }}</div>
              <div class="text-sm text-gray-500">{{ optional($t->customer)->cid_supp }}</div>
            </td>

            {{-- Issue --}}
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ $t->issue_type }}</div>
              <div class="text-sm text-gray-500">{{ Str::limit($t->problem_detail, 20, '...') ?: '—' }}</div>
            </td>

            {{-- Ticket Numbers --}}
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-mono text-gray-900">{{ $t->ticket_number }}</div>
              <div class="text-sm text-gray-500">{{ $t->supplier_ticket_number ?: '—' }}</div>
            </td>

            {{-- Timeline --}}
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">
                {{ optional($t->start_time)->format('M j, H:i') ?: '—' }}
                @if($t->start_time && $t->end_time)
                  <span class="text-gray-400 mx-1">→</span>
                  {{ $t->end_time->format('M j, H:i') }}
                @endif
              </div>
              <div class="text-sm text-gray-500">
                @if($t->start_time && $t->end_time)
                  {{ $t->end_time->diffInMinutes($t->start_time) }} minutes
                @elseif($t->start_time)
                  Ongoing ({{ now()->diffInMinutes($t->start_time) }}m)
                @else
                  Not started
                @endif
              </div>
            </td>

            {{-- Status --}}
            <td class="px-6 py-4 whitespace-nowrap">
              @if($t->end_time)
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                  Closed
                </span>
              @else
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                  Open
                </span>
              @endif
            </td>

            {{-- Actions --}}
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center justify-end space-x-2">
                <a href="{{ route('tickets.show',$t) }}" class="text-indigo-600 hover:text-indigo-900" title="View/Edit">
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                  </svg>
                </a>
                @unless($t->end_time)
                <form action="{{ route('tickets.close',$t) }}" method="POST" onsubmit="return confirm('Close this ticket?')">
                  @csrf @method('PATCH')
                  <button type="submit" class="text-green-600 hover:text-green-900" title="Close Ticket">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </form>
                @endunless
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
      {{ $tickets->links('vendor.pagination.tailwind') }}
    </div>
  </div>
</div>
@endsection