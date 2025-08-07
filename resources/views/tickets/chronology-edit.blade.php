{{-- tickets/chronology-edit.blade.php --}}
<div class="flex flex-col">
  {{-- Header --}}
  <div class="px-6 py-4 border-b flex justify-between items-center">
    <h2 class="text-lg font-semibold text-gray-900">Edit Chronology</h2>
    <button @click="$parent.showChronoEdit = false" class="text-gray-400 hover:text-gray-600">&times;</button>
  </div>

  <form method="POST" action="{{ route('tickets.chronology.update', $ticket) }}" class="p-6 space-y-6">
    @csrf
    @method('PATCH')

    @foreach($ticket->updates as $update)
      <div class="space-y-2">
        <h3 class="font-medium text-gray-700">Entry #{{ $loop->iteration }}</h3>

        <div>
          <label class="block text-sm font-medium text-gray-600">Detail</label>
          <textarea
            name="updates[{{ $update->id }}][detail]"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"
          >{{ old("updates.{$update->id}.detail", $update->detail) }}</textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-600">Date &amp; Time</label>
          <input
            type="datetime-local"
            name="updates[{{ $update->id }}][timestamp]"
            value="{{ old("updates.{$update->id}.timestamp", $update->created_at->format('Y-m-d\TH:i')) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"
          />
        </div>
      </div>

      @if(! $loop->last)
        <hr class="border-gray-200" />
      @endif
    @endforeach

    <div class="pt-4 flex justify-end space-x-3 border-t">
      <button
        type="button"
        @click="$parent.showChronoEdit = false"
        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
      >
        Save All
      </button>
    </div>
  </form>
</div>
