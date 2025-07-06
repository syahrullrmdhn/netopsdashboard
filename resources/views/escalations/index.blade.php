@extends('layouts.app')

@section('title', 'Escalation Management | Settings')

@section('content')
<div class="min-h-screen bg-gray-50">
  <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="mb-8">
      <div class="flex items-center">
        <svg class="h-8 w-8 text-indigo-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
        </svg>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Escalation Settings</h1>
          <p class="mt-1 text-sm text-gray-600">Configure escalation levels and responsible personnel</p>
        </div>
      </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
      <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 rounded">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
          </div>
        </div>
      </div>
    @endif

    <!-- Escalation Form -->
    <form method="POST" action="{{ route('escalations.store') }}" class="bg-white shadow rounded-lg overflow-hidden">
      @csrf
      
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-medium text-gray-900">Escalation Levels Configuration</h2>
        <p class="mt-1 text-sm text-gray-600">Set responsible persons for each escalation level</p>
      </div>

      <div class="px-6 py-5 space-y-8">
        @foreach($levels as $lvl)
          <div class="space-y-4">
            <div class="flex items-center">
              <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-indigo-100">
                <span class="text-sm font-medium text-indigo-800">L{{ $lvl->level }}</span>
              </span>
              <h3 class="ml-3 text-base font-medium text-gray-900">Level {{ $lvl->level }}</h3>
            </div>

            <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-2">
              <div>
                <label for="level_{{ $lvl->level }}_label" class="block text-sm font-medium text-gray-700">Label</label>
                <input type="text"
                      id="level_{{ $lvl->level }}_label"
                      name="levels[{{ $lvl->level }}][label]"
                      value="{{ old("levels.{$lvl->level}.label", $lvl->label) }}"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      required>
              </div>

              <div>
                <label for="level_{{ $lvl->level }}_name" class="block text-sm font-medium text-gray-700">Responsible Person</label>
                <input type="text"
                      id="level_{{ $lvl->level }}_name"
                      name="levels[{{ $lvl->level }}][name]"
                      value="{{ old("levels.{$lvl->level}.name", $lvl->name) }}"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      required>
              </div>

              <div>
                <label for="level_{{ $lvl->level }}_phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text"
                      id="level_{{ $lvl->level }}_phone"
                      name="levels[{{ $lvl->level }}][phone]"
                      value="{{ old("levels.{$lvl->level}.phone", $lvl->phone) }}"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
              </div>

              <div>
                <label for="level_{{ $lvl->level }}_email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email"
                      id="level_{{ $lvl->level }}_email"
                      name="levels[{{ $lvl->level }}][email]"
                      value="{{ old("levels.{$lvl->level}.email", $lvl->email) }}"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      required>
              </div>
            </div>
            
            @if(!$loop->last)
              <hr class="border-gray-200">
            @endif
          </div>
        @endforeach
      </div>

      <!-- Form Footer -->
      <div class="px-6 py-4 bg-gray-50 text-right">
        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
          Save Escalation Settings
        </button>
      </div>
    </form>
  </div>
</div>
@endsection