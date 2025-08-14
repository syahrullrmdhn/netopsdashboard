{{-- tickets/chronology-edit.blade.php --}}
<div
  x-data="{ open:false }"
  x-on:open-chrono-edit.window="open = true"
  x-on:close-chrono-edit.window="open = false"
  x-effect="document.body.classList.toggle('overflow-hidden', open)"
  x-cloak
>
  <template x-teleport="body">
    <div
      x-show="open"
      x-transition.opacity
      class="fixed inset-0 z-[9999]"
      role="dialog"
      aria-modal="true"
      @keydown.escape.window="open = false"
    >
      {{-- Backdrop blur + dim --}}
      <div
        class="absolute inset-0 bg-black/40 backdrop-blur-sm"
        @click="open=false"
        x-transition.opacity
      ></div>

      {{-- Wrapper agar bisa scroll --}}
      <div class="absolute inset-0 p-4 sm:p-6 flex items-center justify-center overflow-y-auto">
        <div
          x-transition
          class="w-full max-w-3xl bg-white rounded-lg shadow-xl flex flex-col"
        >
          {{-- Header (sticky) --}}
          <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
            <h2 class="text-lg font-semibold text-gray-900">Edit Chronology</h2>
            <button @click="open=false" class="text-gray-400 hover:text-gray-600" aria-label="Close">&times;</button>
          </div>

          {{-- Body (scrollable) --}}
          <form method="POST" action="{{ route('tickets.chronology.update', $ticket) }}"
                class="px-6 pt-6 space-y-6 overflow-y-auto max-h-[75vh]">
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
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
                  >{{ old("updates.{$update->id}.detail", $update->detail) }}</textarea>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-600">Date &amp; Time</label>
                  <input
                    type="datetime-local"
                    name="updates[{{ $update->id }}][timestamp]"
                    value="{{ old("updates.{$update->id}.timestamp", $update->created_at->format('Y-m-d\TH:i')) }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>
              </div>

              @if(! $loop->last)
                <hr class="border-gray-200" />
              @endif
            @endforeach

            {{-- Footer (sticky) --}}
            <div class="pt-4 border-t sticky bottom-0 bg-white -mx-6 px-6 pb-6">
              <div class="flex justify-end space-x-3">
                <button type="button" @click="open=false"
                  class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                  Cancel
                </button>
                <button type="submit"
                  class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                  Save All
                </button>
              </div>
            </div>
          </form>

        </div>
      </div>
    </div>
  </template>
</div>
