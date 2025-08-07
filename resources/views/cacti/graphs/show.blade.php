{{-- resources/views/cacti/graphs/show.blade.php --}}

@extends('layouts.app')
@section('title','Graph Detail')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
  {{-- Title: Host – Interface --}}
  <h2 class="text-xl font-semibold mb-4">
    {{ $graph->host_description }} – {{ $graph->interface_title }}
  </h2>

  <div class="mb-4 text-sm text-gray-500">
    ID: {{ $graph->id }} &mdash; Host ID: {{ $graph->host_id }}
  </div>

  {{-- Date+Time & Export CSV --}}
  <form method="get" class="flex flex-wrap items-end gap-4 mb-6">
    <div>
      <label class="block text-xs text-gray-600">From</label>
      <input type="datetime-local" name="start" value="{{ $startInput }}"
             class="border px-2 py-1 rounded text-sm" />
    </div>
    <div>
      <label class="block text-xs text-gray-600">To</label>
      <input type="datetime-local" name="end" value="{{ $endInput }}"
             class="border px-2 py-1 rounded text-sm" />
    </div>
    <button type="submit"
            class="bg-indigo-600 text-white px-3 py-1 rounded text-sm">
      Show
    </button>
    <a href="{{ route('cacti.graphs.export', [
                  'id'=>$graph->id,
                  'start'=>$startInput,
                  'end'=>$endInput
               ]) }}"
       class="bg-green-600 text-white px-3 py-1 rounded text-sm" target="_blank">
      Export CSV
    </a>
  </form>

  {{-- Graph Image --}}
  <div class="bg-white border rounded-lg p-4">
    <img src="{{ route('cacti.graphs.image', [
                   'id'=>$graph->id,
                   'start'=>$startTs,
                   'end'=>$endTs
               ]) }}"
         alt="Graph {{ $graph->id }}"
         class="w-full rounded"
         loading="lazy" />
  </div>
</div>
@endsection
