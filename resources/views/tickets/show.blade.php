@extends('layouts.app')

@section('title', "Ticket #{$ticket->ticket_number}")

@section('content')
@php
    use Carbon\Carbon;
    $escalationLevels = \App\Models\EscalationLevel::orderBy('level')->get();
    $duration = ($ticket->start_time && $ticket->end_time)
        ? $ticket->end_time->diffInMinutes($ticket->start_time).' minutes'
        : '-';
@endphp

<div
  x-data="{
    // modal states
    showRfo: false,
    editRfo: false,
    showEsc: false,
    showSummary: false,
    showClose: false,

    // AI summary state
    chronoSummaryText: '',
    chronoRecommendations: [],
    isLoadingChronoSummary: false,
    _summaryAbort: null,

    // form & lainnya
    escalateLevel: {{ $escalationLevels->first()->level ?? 0 }},
    rfo: {
      problem_detail: @js($ticket->problem_detail),
      action_taken:   @js($ticket->action_taken),
      preventive_action: @js($ticket->preventive_action)
    },

    // helpers modal
    openRfo()  { this.showRfo = true; this.editRfo = false; },
    closeRfo() { this.showRfo = false; },
    toggleEditRfo() { this.editRfo = !this.editRfo; },
    openEsc()  { this.showEsc = true; },
    closeEsc() { this.showEsc = false; },
    openClose(){ this.showClose = true; },
    closeClose(){ this.showClose = false; },
    openSummary(){ this.showSummary = true; },

    // Generate / regenerate summary + recommendations
    fetchChronoSummary(force = false) {
      // jika sudah ada dan tidak force dan tidak loading, skip
      if (!force && this.chronoSummaryText && !this.isLoadingChronoSummary) return;

      // batalkan request sebelumnya kalau ada
      if (this._summaryAbort) this._summaryAbort.abort();
      this._summaryAbort = new AbortController();

      this.isLoadingChronoSummary = true;
      if (force) {
        this.chronoSummaryText = '';
        this.chronoRecommendations = [];
      }

      fetch(`/api/tickets/{{ $ticket->id }}/chronology-summary`, {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        cache: 'no-store',
        signal: this._summaryAbort.signal,
      })
      .then(async (res) => {
        if (!res.ok) {
          let msg = 'Gagal memuat ringkasan. Silakan periksa log server.';
          try { const j = await res.json(); msg = j.summary || j.error || msg; } catch (_) {}
          throw new Error(msg);
        }
        return res.json();
      })
      .then((data) => {
        this.chronoSummaryText = data.summary ?? 'Ringkasan kosong.';
        this.chronoRecommendations = Array.isArray(data.recommendations) ? data.recommendations : [];
      })
      .catch((err) => {
        this.chronoSummaryText = err?.message || 'Gagal memuat ringkasan. Silakan periksa log server.';
        this.chronoRecommendations = [];
      })
      .finally(() => {
        this.isLoadingChronoSummary = false;
        this._summaryAbort = null;
      });
    },
  }"
  x-effect="
    // kunci scroll body saat ada modal aktif
    document.body.classList.toggle('overflow-hidden', showRfo || showEsc || showClose || showSummary)
  "
  class="py-8"
>
  {{-- Page Header --}}
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-6">
      <div>
        <div class="flex items-center gap-3">
          <x-heroicon-o-document-text class="h-8 w-8 text-indigo-600" />
          <h1 class="text-2xl font-bold text-gray-900">Ticket #{{ $ticket->ticket_number }}</h1>
        </div>
        <p class="mt-1 text-sm text-gray-600">Review and manage ticket details</p>
      </div>
      <div class="flex items-center gap-3">
        <a href="{{ route('tickets.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
          <x-heroicon-o-arrow-left class="h-5 w-5 mr-2" />
          Back to Tickets
        </a>
        <button @click="openRfo()" class="inline-flex items-center px-4 py-2 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700">
          <x-heroicon-o-document-text class="h-5 w-5 mr-2" />
          Generate RFO
        </button>
      </div>
    </div>
  </div>

  {{-- Main Content --}}
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

      {{-- Left Column: Ticket Information & Edit Form --}}
      <div class="lg:col-span-1">
        <div class="bg-white shadow rounded-lg overflow-hidden">
          <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="divide-y divide-gray-200">
            @csrf @method('PATCH')

            <div class="bg-gray-50 px-6 py-5 border-b">
              <h3 class="text-lg font-medium text-gray-900">Ticket Information</h3>
            </div>
            <div class="px-6 py-4 space-y-4">
              {{-- Open Date & Issue Type --}}
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Open Date</label>
                  <input
                    type="datetime-local"
                    name="open_date"
                    value="{{ optional($ticket->open_date)->format('Y-m-d\TH:i') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500"
                  >
                  @error('open_date')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Issue Type</label>
                  <input
                    type="text"
                    name="issue_type"
                    value="{{ $ticket->issue_type }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500"
                  >
                  @error('issue_type')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>
              </div>

              {{-- Customer Info --}}
              <div>
                <h4 class="text-sm font-medium text-gray-500 mb-1">Customer Information</h4>
                <p class="text-sm"><strong>Name:</strong> {{ $ticket->customer->customer ?? '-' }}</p>
                <p class="text-sm"><strong>CID:</strong> {{ $ticket->customer->cid_abh ?? '-' }}</p>
                <p class="text-sm"><strong>Supplier:</strong> {{ $ticket->customer->supplier->nama_supplier ?? '-' }}</p>
              </div>

              {{-- Ticket Numbers --}}
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">ABH Ticket #</label>
                  <p class="mt-1 font-mono">{{ $ticket->ticket_number }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Supplier Ticket #</label>
                  <input
                    type="text"
                    name="supplier_ticket_number"
                    value="{{ $ticket->supplier_ticket_number }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500"
                  >
                  @error('supplier_ticket_number')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>
              </div>

              {{-- Timeline --}}
              <div>
                <h4 class="text-sm font-medium text-gray-500 mb-1">Timeline</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                  <div>
                    <label class="block text-gray-500">Start Time</label>
                    <input
                      type="datetime-local"
                      name="start_time"
                      value="{{ optional($ticket->start_time)->format('Y-m-d\TH:i') }}"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                    @error('start_time')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                  </div>
                  <div>
                    <label class="block text-gray-500">End Time</label>
                    <input
                      type="datetime-local"
                      name="end_time"
                      value="{{ optional($ticket->end_time)->format('Y-m-d\TH:i') }}"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                    @error('end_time')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                  </div>
                  <div>
                    <label class="block text-gray-500">Duration</label>
                    <p>{{ $duration }}</p>
                  </div>
                </div>
              </div>

              {{-- Details Sections --}}
              <div>
                <label class="block text-sm font-medium text-gray-700">Root Cause Analysis</label>
                <textarea
                  name="problem_detail"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500"
                >{{ $ticket->problem_detail }}</textarea>
                @error('problem_detail')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Corrective Actions</label>
                <textarea
                  name="action_taken"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500"
                >{{ $ticket->action_taken }}</textarea>
                @error('action_taken')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Preventive &amp; Improvement</label>
                <textarea
                  name="preventive_action"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500"
                >{{ $ticket->preventive_action }}</textarea>
                @error('preventive_action')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
              </div>
            </div>

            {{-- Save Changes --}}
            <div class="px-6 py-4 bg-gray-50 text-right">
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>

      {{-- Right Column: Chronology & Actions --}}
      <div class="lg:col-span-2 space-y-6">

        {{-- Problem Details & Chronology --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
          {{-- Header bar --}}
          <div class="bg-gray-50 px-6 py-5 border-b flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Problem Details &amp; Chronology</h3>
            <div class="flex gap-2">
              <button
                type="button"
                x-data
                @click="window.dispatchEvent(new CustomEvent('open-chrono-edit'))"
                class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700"
              >
                Edit Chronology
              </button>
              <button
                type="button"
                @click="showSummary = true; fetchChronoSummary(true)"
                class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700"
              >
                <x-heroicon-o-document-text class="h-5 w-5 mr-2 inline" />View Summary
              </button>
            </div>
          </div>

          {{-- Timeline list --}}
          <div class="px-6 py-4">
            @if($ticket->updates->isNotEmpty())
              <ul class="-mb-8">
                @foreach($ticket->updates as $update)
                  <li class="relative pb-8">
                    @if(! $loop->last)
                      <span class="absolute top-4 left-4 h-full w-0.5 bg-gray-200"></span>
                    @endif
                    <div class="relative flex items-start space-x-3">
                      <div>
                        <span class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center ring-8 ring-white">
                          <x-heroicon-o-user class="h-5 w-5 text-white" />
                        </span>
                      </div>
                      <div class="flex-1 py-1 space-y-1">
                        <p class="text-sm text-gray-800">
                          <span class="font-medium">{{ $update->user->name ?? 'System' }}</span>
                          &bull; {{ $update->created_at->format('M j, Y g:i A') }}
                        </p>
                        <p class="text-sm text-gray-600">{{ $update->detail }}</p>
                      </div>
                    </div>
                  </li>
                @endforeach
              </ul>
            @else
              <p class="text-gray-500 italic">No updates recorded yet.</p>
            @endif
          </div>

          {{-- Add New Update --}}
          @unless($ticket->end_time)
            <div class="px-6 py-4 bg-gray-50">
              <form method="POST" action="{{ route('tickets.updates.store', $ticket) }}" class="space-y-4">
                @csrf
                <div>
                  <label for="detail" class="block text-sm font-medium text-gray-700">Add New Update</label>
                  <textarea id="detail" name="detail" rows="3" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500"
                  ></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                  <button @click.prevent="openEsc()" type="button" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                    <x-heroicon-o-arrow-trending-up class="h-5 w-5 mr-2 inline"/>Escalate
                  </button>
                  <button @click.prevent="openClose()" type="button" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    <x-heroicon-o-check-circle class="h-5 w-5 mr-2 inline"/>Close Ticket
                  </button>
                  <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    <x-heroicon-o-plus-circle class="h-5 w-5 mr-2 inline"/>Add Update
                  </button>
                </div>
              </form>
            </div>
          @else
            <div class="px-6 py-4 bg-green-50">
              <div class="flex items-center space-x-2">
                <x-heroicon-o-check-circle class="h-5 w-5 text-green-400" />
                <p class="text-sm font-medium text-green-800">
                  This ticket was closed on {{ $ticket->end_time->format('F j, Y, g:i a') }}
                </p>
              </div>
            </div>
          @endunless
        </div>

      </div>
    </div>
  </div>

  {{-- RFO Modal --}}
  <template x-teleport="body">
    <div
      x-show="showRfo"
      x-cloak
      x-transition.opacity
      @keydown.escape.window="closeRfo()"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
    >
      <div @click.outside="closeRfo()" class="bg-white rounded-lg shadow-xl overflow-hidden w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h2 class="text-xl font-semibold text-gray-900">Official Incident Report</h2>
          <button @click="closeRfo()" class="text-gray-400 hover:text-gray-500" aria-label="Close">
            <x-heroicon-o-x-mark class="h-6 w-6" />
          </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
          @include('tickets.rfo', ['ticket' => $ticket])
        </div>
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
          <div>
            <button
              x-show="!editRfo"
              @click="toggleEditRfo()"
              class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700"
            >Edit</button>
            <button
              x-show="editRfo"
              @click="toggleEditRfo()"
              class="px-4 py-2 bg-white text-gray-700 border rounded-md hover:bg-gray-50"
            >Cancel</button>
          </div>
          <form method="POST" action="{{ route('tickets.rfo.pdf', $ticket->id) }}" target="_blank" class="flex space-x-3">
            @csrf
            <input type="hidden" name="problem_detail"    x-bind:value="rfo.problem_detail">
            <input type="hidden" name="action_taken"      x-bind:value="rfo.action_taken">
            <input type="hidden" name="preventive_action" x-bind:value="rfo.preventive_action">
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
              Download PDF
            </button>
          </form>
        </div>
      </div>
    </div>
  </template>

  {{-- Escalation Modal --}}
  <template x-teleport="body">
    <div
      x-show="showEsc"
      x-cloak
      x-transition.opacity
      @keydown.escape.window="closeEsc()"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
    >
      <div @click.outside="closeEsc()" class="bg-white rounded-lg shadow-xl overflow-hidden w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h2 class="text-lg font-semibold text-gray-900">Escalate Ticket</h2>
          <button @click="closeEsc()" class="text-gray-400 hover:text-gray-500" aria-label="Close">
            <x-heroicon-o-x-mark class="h-6 w-6" />
          </button>
        </div>
        <form action="{{ route('tickets.escalate', $ticket->id) }}" method="POST">
          @csrf
          <div class="p-6 space-y-4">
            <label class="block text-sm font-medium text-gray-700">Select Escalation Level</label>
            <select
              name="level"
              x-model="escalateLevel"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500"
            >
              @foreach($escalationLevels as $lvl)
                <option value="{{ $lvl->level }}">Level {{ $lvl->level }} – {{ $lvl->label }}</option>
              @endforeach
            </select>
          </div>
          <div class="px-6 py-4 border-t bg-gray-50 text-right">
            <button type="button" @click="closeEsc()" class="px-4 py-2 bg-white text-gray-700 rounded-md hover:bg-gray-100">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">Escalate</button>
          </div>
        </form>
      </div>
    </div>
  </template>

  {{-- Close Ticket Modal --}}
  <template x-teleport="body">
    <div
      x-show="showClose"
      x-cloak
      x-transition.opacity
      @keydown.escape.window="closeClose()"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
    >
      <div @click.outside="closeClose()" class="bg-white rounded-lg shadow-xl overflow-hidden w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h2 class="text-lg font-semibold text-gray-900">Close Ticket</h2>
          <button @click="closeClose()" class="text-gray-400 hover:text-gray-500" aria-label="Close">
            <x-heroicon-o-x-mark class="h-6 w-6" />
          </button>
        </div>
        <div class="p-6">
          <p class="text-gray-700">Are you sure you want to close <strong>Ticket #{{ $ticket->ticket_number }}</strong>? This cannot be undone.</p>
        </div>
        <div class="px-6 py-4 border-t bg-gray-50 text-right space-x-3">
          <button @click="closeClose()" class="px-4 py-2 bg-white text-gray-700 rounded-md hover:bg-gray-100">Cancel</button>
          <form method="POST" action="{{ route('tickets.close', $ticket->id) }}" class="inline">
            @csrf @method('PATCH')
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Confirm Close</button>
          </form>
        </div>
      </div>
    </div>
  </template>

  {{-- Chronology Edit Modal (event-driven, di-teleport oleh partial) --}}
  @include('tickets.chronology-edit', ['ticket' => $ticket])

  {{-- Summary Modal (teleport; akan baca chronoSummaryText & chronoRecommendations dari x-data) --}}
  @include('tickets.summary', ['ticket' => $ticket])

</div>
@endsection
