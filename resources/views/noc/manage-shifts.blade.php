@extends('layouts.app')
@section('title', 'Manage NOC Shifts')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header Section -->
    <div class="mb-8 bg-white shadow-sm rounded-lg p-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">NOC Shift Management</h1>
          <p class="mt-2 text-sm text-gray-600">Current date: <span class="font-medium">{{ $today }}</span></p>
        </div>
        <div class="mt-4 md:mt-0">
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ now()->format('l, F j, Y') }}
          </span>
        </div>
      </div>
    </div>

    @if(session('success'))
      <div class="mb-6 rounded-md bg-green-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
          </div>
        </div>
      </div>
    @endif

    <!-- Shift Assignment Form -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Shift Assignments</h2>
        <p class="mt-1 text-sm text-gray-500">Assign engineers to each shift period</p>
      </div>
      <form action="{{ route('noc.updateShifts') }}" method="POST" class="divide-y divide-gray-200">
        @csrf
        
        @foreach([
          'pagi' => ['label' => 'Morning Shift', 'time' => '06:00 - 14:00', 'icon' => 'sun'],
          'siang' => ['label' => 'Afternoon Shift', 'time' => '14:00 - 22:00', 'icon' => 'clock'],
          'malam' => ['label' => 'Night Shift', 'time' => '22:00 - 06:00', 'icon' => 'moon']
        ] as $shift => $details)
          <div class="px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center">
              <div class="w-full sm:w-1/3 mb-4 sm:mb-0">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      @if($details['icon'] === 'sun')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                      @elseif($details['icon'] === 'moon')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                      @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      @endif
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-900">{{ $details['label'] }}</h3>
                    <p class="text-sm text-gray-500">{{ $details['time'] }}</p>
                  </div>
                </div>
              </div>
              <div class="w-full sm:w-2/3">
                <select
                  name="assignment[{{ $shift }}]"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                >
                  <option value="">— Select Engineer —</option>
                  @foreach($users as $u)
                    <option
                      value="{{ $u->id }}"
                      @if(old("assignment.$shift", optional($assignments->get($shift))->user_id) == $u->id)
                        selected
                      @endif
                    >
                      {{ $u->name }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
        @endforeach

        <div class="px-6 py-4 bg-gray-50 text-right">
          <button
            type="submit"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Save Assignments
          </button>
        </div>
      </form>
    </div>

    <!-- Current Assignments -->
    <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Current Assignments</h2>
        <p class="mt-1 text-sm text-gray-500">Engineers assigned to today's shifts</p>
      </div>
      <div class="bg-white divide-y divide-gray-200">
        @foreach([
          'pagi' => 'Morning (06:00-14:00)',
          'siang' => 'Afternoon (14:00-22:00)',
          'malam' => 'Night (22:00-06:00)'
        ] as $shift => $label)
          <div class="px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                  {{ substr($label, 0, 1) }}
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ $label }}</div>
                  <div class="text-sm text-gray-500">
                    @if($assignments->get($shift))
                      {{ $assignments->get($shift)->user->name }}
                    @else
                      <span class="text-gray-400">Not assigned</span>
                    @endif
                  </div>
                </div>
              </div>
              <div class="ml-2 flex-shrink-0 flex">
                @if($assignments->get($shift))
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                    Assigned
                  </span>
                @else
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                    Pending
                  </span>
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection