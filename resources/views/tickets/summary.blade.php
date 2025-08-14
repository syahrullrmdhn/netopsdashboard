{{-- resources/views/tickets/summary.blade.php --}}
<template x-teleport="body">
  <div
    x-show="showSummary"
    x-cloak
    x-transition.opacity
    @keydown.escape.window="showSummary = false"
    class="fixed inset-0 z-[9998] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
    aria-modal="true" role="dialog"
  >
    <div
      @click.outside="showSummary = false"
      x-transition
      class="bg-white rounded-xl shadow-2xl overflow-hidden w-full max-w-2xl"
    >
      {{-- Header --}}
      <div class="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-indigo-600 to-blue-600">
        <div class="flex items-center space-x-3">
          <x-heroicon-o-ticket class="h-6 w-6 text-white" />
          <h2 class="text-xl font-bold text-white">Ticket Summary</h2>
        </div>
        <button @click="showSummary = false" class="text-white/80 hover:text-white transition-colors" aria-label="Close">
          <x-heroicon-o-x-mark class="h-6 w-6" />
        </button>
      </div>

      {{-- Body --}}
      <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
        {{-- Ticket Header Info --}}
        <div class="flex flex-col sm:flex-row justify-between gap-4">
          <div class="space-y-1">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket Number</span>
            <p class="text-2xl font-bold text-indigo-600">#{{ $ticket->ticket_number }}</p>
          </div>
          <div class="space-y-1">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</span>
            <div class="flex items-center gap-2">
              <span @class([
                  'px-3 py-1 text-xs font-semibold rounded-full tracking-wide',
                  'bg-green-100 text-green-800' => $ticket->status === 'Closed',
                  'bg-blue-100 text-blue-800' => $ticket->status === 'Open',
                  'bg-yellow-100 text-yellow-800' => !in_array($ticket->status, ['Open', 'Closed']),
              ])>
                {{ $ticket->status ?? 'N/A' }}
              </span>
            </div>
          </div>
        </div>

        {{-- Subject --}}
        <div class="space-y-1">
          <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</span>
          <p class="text-lg font-semibold text-gray-800 leading-snug">{{ $ticket->subject ?? 'N/A' }}</p>
        </div>

        <div class="border-t border-gray-100 my-4"></div>

        {{-- Detail Info Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors duration-200">
            <div class="flex items-start gap-3">
              <div class="p-2 bg-indigo-50 rounded-lg">
                <x-heroicon-o-user class="h-5 w-5 text-indigo-600" />
              </div>
              <div class="space-y-1">
                <span class="text-xs font-medium text-gray-500">Customer</span>
                <p class="text-sm font-medium text-gray-800">{{ $ticket->customer->customer ?? 'N/A' }}</p>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors duration-200">
            <div class="flex items-start gap-3">
              <div class="p-2 bg-blue-50 rounded-lg">
                <x-heroicon-o-user-circle class="h-5 w-5 text-blue-600" />
              </div>
              <div class="space-y-1">
                <span class="text-xs font-medium text-gray-500">PIC</span>
                <p class="text-sm font-medium text-gray-800">{{ $ticket->user->name ?? 'Unassigned' }}</p>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors duration-200">
            <div class="flex items-start gap-3">
              <div class="p-2 bg-purple-50 rounded-lg">
                <x-heroicon-o-calendar class="h-5 w-5 text-purple-600" />
              </div>
              <div class="space-y-1">
                <span class="text-xs font-medium text-gray-500">Open Date</span>
                <p class="text-sm font-medium text-gray-800">
                  {{ optional($ticket->open_date)->format('M j, Y \a\t g:i A') ?? 'N/A' }}
                </p>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors duration-200">
            <div class="flex items-start gap-3">
              <div class="p-2 bg-amber-50 rounded-lg">
                <x-heroicon-o-clock class="h-5 w-5 text-amber-600" />
              </div>
              <div class="space-y-1">
                <span class="text-xs font-medium text-gray-500">Duration</span>
                <p class="text-sm font-medium text-gray-800">
                  @if($ticket->start_time && $ticket->end_time)
                    {{ $ticket->start_time->diffForHumans($ticket->end_time, true) }}
                  @else
                    In Progress
                  @endif
                </p>
              </div>
            </div>
          </div>
        </div>

        {{-- Last Update --}}
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <x-heroicon-o-chat-bubble-left-right class="h-5 w-5 text-gray-400" />
            <span class="text-sm font-semibold text-gray-700">Last Update</span>
          </div>
          <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors duration-200">
            <div class="text-sm text-gray-700">
              <p class="whitespace-pre-wrap">{{ $ticket->updates->last()->detail ?? 'No updates yet.' }}</p>
              @if ($lastUpdate = $ticket->updates->last())
                <p class="text-xs text-gray-500 mt-2">
                  Updated by <span class="font-medium">{{ $lastUpdate->user->name ?? 'System' }}</span>
                  • {{ $lastUpdate->created_at->diffForHumans() }}
                </p>
              @endif
            </div>
          </div>
        </div>

        {{-- AI Summary --}}
        <div class="space-y-3">
          <div class="flex items-center justify-between border-t border-gray-200 pt-4">
            <div class="flex items-center gap-2">
              <x-heroicon-o-sparkles class="h-5 w-5 text-amber-500" />
              <span class="text-sm font-semibold text-gray-700">AI Summary</span>
            </div>
            <button
              @click.prevent="fetchChronoSummary(true)"
              class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition-colors duration-200 disabled:opacity-50"
              :disabled="isLoadingChronoSummary"
            >
              <span x-text="isLoadingChronoSummary ? 'Generating...' : 'Generate Summary'"></span>
              <svg x-show="isLoadingChronoSummary" class="animate-spin h-4 w-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </button>
          </div>

          {{-- Output Summary --}}
          <div x-show="chronoSummaryText || isLoadingChronoSummary" x-cloak class="bg-indigo-50/50 border border-indigo-100 rounded-lg p-4">
            <div x-show="isLoadingChronoSummary" class="flex items-center gap-3 text-sm text-indigo-700">
              <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>Generating AI-powered summary...</span>
            </div>
            <div x-show="!isLoadingChronoSummary && chronoSummaryText" class="space-y-2">
              <p x-text="chronoSummaryText" class="text-sm text-gray-700 leading-relaxed"></p>
              <p class="text-xs text-indigo-600 italic">AI-generated content. Please verify accuracy.</p>
            </div>
          </div>
        </div>

        {{-- AI Recommendations (Langkah-langkah) --}}
        <div class="space-y-3" x-show="chronoRecommendations && chronoRecommendations.length" x-cloak>
          <div class="flex items-center gap-2">
            <x-heroicon-o-light-bulb class="h-5 w-5 text-amber-500" />
            <span class="text-sm font-semibold text-gray-700">AI Recommendations (Next Steps)</span>
          </div>
          <div class="bg-amber-50/60 border border-amber-200 rounded-lg p-4">
            <ol class="list-decimal pl-5 space-y-2 text-sm text-gray-800">
              <template x-for="(rec, idx) in chronoRecommendations" :key="idx">
                <li x-text="rec"></li>
              </template>
            </ol>
            <p class="text-xs text-amber-700 mt-2">Catatan: ini saran AI, verifikasi sebelum eksekusi.</p>
          </div>
        </div>
      </div>

      {{-- Footer --}}
      <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
        <button
          @click="showSummary = false"
          class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>
