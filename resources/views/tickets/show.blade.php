@extends('layouts.app')

@section('title', "Ticket #{$ticket->ticket_number}")

@section('content')
@php
    use App\Models\EscalationLevel;
    $escalationLevels = EscalationLevel::orderBy('level')->get();
    $duration = $ticket->start_time && $ticket->end_time 
        ? $ticket->end_time->diffInMinutes($ticket->start_time).' minutes' 
        : '—';
@endphp

<div
    x-data="{
        showRfo: false,
        editRfo: false,
        showEsc: false,
        escalateLevel: {{ $escalationLevels->first()->level ?? 0 }},
        showClose: false,

        // RFO
        openRfo() { this.showRfo = true; this.editRfo = false; },
        closeRfo() { this.showRfo = false; },
        toggleEditRfo() { this.editRfo = !this.editRfo; },

        // Escalation
        openEsc() { this.showEsc = true; },
        closeEsc() { this.showEsc = false; },

        // Close-Ticket
        openClose() { this.showClose = true; },
        closeClose() { this.showClose = false; }
    }"
    class="py-8"
>
    {{-- Page Header --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-6">
            <div>
                <div class="flex items-center gap-3">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-900">Ticket #{{ $ticket->ticket_number }}</h1>
                </div>
                <p class="mt-1 text-sm text-gray-600">Review and manage ticket details</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('tickets.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <x-heroicon-o-arrow-left class="-ml-1 mr-2 h-5 w-5" />
                    Back to Tickets
                </a>
                <button
                    x-on:click="openRfo()"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                >
                    <x-heroicon-o-document-text class="-ml-1 mr-2 h-5 w-5" />
                    Generate RFO
                </button>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left Column: Ticket Details Form --}}
            <div class="lg:col-span-1">
                <div class="bg-white shadow overflow-hidden rounded-lg">
                    <div class="px-6 py-5 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Ticket Information</h3>
                    </div>
                    <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="divide-y divide-gray-200">
                        @csrf @method('PATCH')
                        
                        {{-- Basic Info --}}
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-2">
                                <div>
                                    <label for="open_date" class="block text-sm font-medium text-gray-700">Open Date</label>
                                    <input
                                        type="datetime-local"
                                        id="open_date"
                                        name="open_date"
                                        value="{{ optional($ticket->open_date)->format('Y-m-d\TH:i') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                    @error('open_date')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="issue_type" class="block text-sm font-medium text-gray-700">Issue Type</label>
                                    <input
                                        type="text"
                                        id="issue_type"
                                        name="issue_type"
                                        value="{{ $ticket->issue_type }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                    @error('issue_type')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        {{-- Customer Info --}}
                        <div class="px-6 py-4">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Customer Information</h4>
                            <div class="space-y-2">
                                <div>
                                    <p class="text-sm text-gray-500">Customer Name</p>
                                    <p class="text-sm font-medium">{{ optional($ticket->customer)->customer }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Customer SID</p>
                                    <p class="text-sm font-medium">{{ optional($ticket->customer)->cid_abh }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Supplier Name</p>
                                    <p class="text-sm font-medium">{{ optional(optional($ticket->customer)->supplier)->nama_supplier }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Supplier SID</p>
                                    <p class="text-sm font-medium">{{ optional($ticket->customer)->cid_supp }}</p>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Ticket Numbers --}}
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">ABH Ticket #</label>
                                    <p class="mt-1 text-sm font-mono">{{ $ticket->ticket_number }}</p>
                                </div>
                                <div>
                                    <label for="supplier_ticket_number" class="block text-sm font-medium text-gray-700">Supplier Ticket #</label>
                                    <input
                                        type="text"
                                        id="supplier_ticket_number"
                                        name="supplier_ticket_number"
                                        value="{{ $ticket->supplier_ticket_number }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                    @error('supplier_ticket_number')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        {{-- Timeline --}}
                        <div class="px-6 py-4">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Timeline</h4>
                            <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-2">
                                <div>
                                    <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time</label>
                                    <input
                                        type="datetime-local"
                                        id="start_time"
                                        name="start_time"
                                        value="{{ optional($ticket->start_time)->format('Y-m-d\TH:i') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                    @error('start_time')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="end_time" class="block text-sm font-medium text-gray-700">End Time</label>
                                    <input
                                        type="datetime-local"
                                        id="end_time"
                                        name="end_time"
                                        value="{{ optional($ticket->end_time)->format('Y-m-d\TH:i') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                    @error('end_time')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Duration</label>
                                    <p class="mt-1 text-sm">{{ $duration }}</p>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Details --}}
                        <div class="px-6 py-4">
                            <label for="problem_detail" class="block text-sm font-medium text-gray-700">Root Cause Analysis</label>
                            <textarea
                                id="problem_detail"
                                name="problem_detail"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >{{ $ticket->problem_detail }}</textarea>
                            @error('problem_detail')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="px-6 py-4">
                            <label for="action_taken" class="block text-sm font-medium text-gray-700">Corrective Actions</label>
                            <textarea
                                id="action_taken"
                                name="action_taken"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >{{ $ticket->action_taken }}</textarea>
                            @error('action_taken')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="px-6 py-4">
                            <label for="preventive_action" class="block text-sm font-medium text-gray-700">Preventive & Improvement</label>
                            <textarea
                                id="preventive_action"
                                name="preventive_action"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >{{ $ticket->preventive_action }}</textarea>
                            @error('preventive_action')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        {{-- Form Actions --}}
                        <div class="px-6 py-4 bg-gray-50 text-right">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Right Column: Chronology & Actions --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Chronology --}}
                <div class="bg-white shadow overflow-hidden rounded-lg">
                    <div class="px-6 py-5 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Problem Details & Chronology</h3>
                    </div>
                    <div class="px-6 py-4">
                        @if($ticket->updates->count() > 0)
                            <div class="flow-root">
                                <ul class="-mb-8">
                                    @foreach($ticket->updates as $update)
                                        <li>
                                            <div class="relative pb-8">
                                                @if(!$loop->last)
                                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                                @endif
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center ring-8 ring-white">
                                                            <x-heroicon-o-user class="h-5 w-5 text-white" />
                                                        </span>
                                                    </div>
                                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                        <div>
                                                            <p class="text-sm text-gray-800">
                                                                <span class="font-medium text-gray-900">{{ optional($update->user)->name ?? 'System' }}</span>
                                                                updated this ticket
                                                            </p>
                                                            <p class="text-sm text-gray-500">{{ $update->detail }}</p>
                                                        </div>
                                                        <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                                            <time datetime="{{ $update->created_at->format('Y-m-d') }}">{{ $update->created_at->format('M j, Y g:i A') }}</time>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="text-gray-500 italic">No updates recorded yet.</p>
                        @endif
                    </div>
                    
                    @unless($ticket->end_time)
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div class="space-y-4">
                                {{-- Add Update Form --}}
                                <form method="POST" action="{{ route('tickets.updates.store', $ticket) }}" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label for="detail" class="block text-sm font-medium text-gray-700">Add New Update</label>
                                        <textarea
                                            id="detail"
                                            name="detail"
                                            rows="3"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            required
                                        ></textarea>
                                    </div>
                                    <div class="flex justify-end space-x-3">
                                        <button
                                            type="button"
                                            x-on:click="openEsc()"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                                        >
                                            <x-heroicon-o-arrow-trending-up class="-ml-1 mr-2 h-5 w-5" />
                                            Escalate
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click="openClose()"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                        >
                                            <x-heroicon-o-check-circle class="-ml-1 mr-2 h-5 w-5" />
                                            Close Ticket
                                        </button>
                                        <button
                                            type="submit"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        >
                                            <x-heroicon-o-plus-circle class="-ml-1 mr-2 h-5 w-5" />
                                            Add Update
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div class="rounded-md bg-green-50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <x-heroicon-o-check-circle class="h-5 w-5 text-green-400" />
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-800">
                                            This ticket was closed on {{ $ticket->end_time->format('F j, Y, g:i a') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endunless
                </div>
            </div>
        </div>
    </div>

    {{-- RFO Modal --}}
    <div
        x-show="showRfo"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="closeRfo()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
    >
        <div @click.outside="closeRfo()" class="bg-white rounded-lg shadow-xl overflow-hidden w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h2 class="text-xl font-semibold text-gray-900">Official Incident Report</h2>
                <button @click="closeRfo()" class="text-gray-400 hover:text-gray-500">
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
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                    >
                        <x-heroicon-o-pencil class="-ml-1 mr-2 h-5 w-5" />
                        Edit
                    </button>
                    <button
                        x-show="editRfo"
                        @click="toggleEditRfo()"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Cancel
                    </button>
                </div>
                <form method="POST" action="{{ route('tickets.rfo.pdf', $ticket) }}" target="_blank" class="flex space-x-3">
                    @csrf
                    <input type="hidden" name="problem_detail" x-bind:value="rfo.problem_detail">
                    <input type="hidden" name="action_taken" x-bind:value="rfo.action_taken">
                    <input type="hidden" name="preventive_action" x-bind:value="rfo.preventive_action">
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        <x-heroicon-o-arrow-down-tray class="-ml-1 mr-2 h-5 w-5" />
                        Download PDF
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Escalation Modal --}}
    <div
        x-show="showEsc"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="closeEsc()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
    >
        <div @click.outside="closeEsc()" class="bg-white rounded-lg shadow-xl overflow-hidden w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Escalate Ticket</h2>
                <button @click="closeEsc()" class="text-gray-400 hover:text-gray-500">
                    <x-heroicon-o-x-mark class="h-6 w-6" />
                </button>
            </div>
            <form action="{{ route('tickets.escalate', $ticket) }}" method="POST">
                @csrf
                <div class="p-6 space-y-4">
                    <div>
                        <label for="level" class="block text-sm font-medium text-gray-700">Select Escalation Level</label>
                        <select
                            id="level"
                            name="level"
                            x-model="escalateLevel"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            @foreach($escalationLevels as $lvl)
                                <option value="{{ $lvl->level }}">
                                    Level {{ $lvl->level }} – {{ $lvl->label }} ({{ $lvl->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end space-x-3">
                    <button
                        type="button"
                        @click="closeEsc()"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                    >
                        <x-heroicon-o-arrow-trending-up class="-ml-1 mr-2 h-5 w-5" />
                        Escalate
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Close Ticket Modal --}}
    <div
        x-show="showClose"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="closeClose()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
    >
        <div @click.outside="closeClose()" class="bg-white rounded-lg shadow-xl overflow-hidden w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Close Ticket</h2>
                <button @click="closeClose()" class="text-gray-400 hover:text-gray-500">
                    <x-heroicon-o-x-mark class="h-6 w-6" />
                </button>
            </div>
            <div class="p-6">
                <p class="text-gray-700">
                    Are you sure you want to close <strong>Ticket #{{ $ticket->ticket_number }}</strong>?
                    This action cannot be undone.
                </p>
            </div>
            <div class="px-6 py-4 border-t bg-gray-50 flex justify-end space-x-3">
                <button
                    @click="closeClose()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Cancel
                </button>
                <form method="POST" action="{{ route('tickets.close', $ticket) }}">
                    @csrf @method('PATCH')
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        <x-heroicon-o-check-circle class="-ml-1 mr-2 h-5 w-5" />
                        Confirm Close
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection