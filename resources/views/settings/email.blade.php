@extends('layouts.app')

@section('title','Email Settings')

@section('content')
<div class="max-w-2xl mx-auto py-8 space-y-6">
  <h1 class="text-2xl font-bold">Email Settings</h1>

  @if(session('success'))
    <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
  @endif

  <form method="POST" action="{{ route('settings.email.update') }}"
        class="bg-white p-6 rounded shadow space-y-6">
    @csrf

    @foreach($levels as $lvl)
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Level {{ $lvl->level }} – {{ $lvl->label }}</label>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600">Name</label>
          <input type="text" name="levels[{{ $lvl->level }}][name]"
                 value="{{ old("levels.{$lvl->level}.name", $lvl->name) }}"
                 class="mt-1 block w-full border rounded px-3 py-2" required>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600">Phone</label>
          <input type="text" name="levels[{{ $lvl->level }}][phone]"
                 value="{{ old("levels.{$lvl->level}.phone", $lvl->phone) }}"
                 class="mt-1 block w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600">Email</label>
          <input type="email" name="levels[{{ $lvl->level }}][email]"
                 value="{{ old("levels.{$lvl->level}.email", $lvl->email) }}"
                 class="mt-1 block w-full border rounded px-3 py-2" required>
        </div>
      </div>
      <hr class="my-4">
    @endforeach

    <div class="text-right">
      <button type="submit"
              class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
        Save Settings
      </button>
    </div>
  </form>
</div>
@endsection
