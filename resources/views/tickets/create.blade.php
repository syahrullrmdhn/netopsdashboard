@extends('layouts.app')

@section('title', 'Create New Support Ticket')

@section('content')
<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Form Header -->
        <div class="mb-8">
            <div class="flex items-center">
                <svg class="h-8 w-8 text-indigo-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create New Support Ticket</h1>
                    <p class="mt-1 text-sm text-gray-600">Fill in the details below to create a new support ticket</p>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <form action="{{ route('tickets.store') }}" method="POST" x-data="ticketForm()">
                @csrf
                
                <!-- Form Sections -->
                <div class="space-y-6 divide-y divide-gray-200">
                    <!-- Ticket Information -->
                    <div class="px-6 py-5 space-y-6">
                        <h2 class="text-lg font-medium text-gray-900">Ticket Information</h2>
                        
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <!-- Open Date -->
                            <div class="sm:col-span-3">
                                <label for="open_date" class="block text-sm font-medium text-gray-700">Open Date</label>
                                <div class="mt-1">
                                    <input type="datetime-local" id="open_date" name="open_date" 
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           value="{{ old('open_date', now()->format('Y-m-d\TH:i')) }}" required>
                                </div>
                            </div>
                            
                            <!-- Issue Type -->
                            <div class="sm:col-span-3">
                                <label for="issue_type" class="block text-sm font-medium text-gray-700">Issue Type</label>
                                <div class="mt-1">
                                    <input type="text" id="issue_type" name="issue_type" 
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           value="{{ old('issue_type') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="px-6 py-5 space-y-6">
                        <h2 class="text-lg font-medium text-gray-900">Customer Information</h2>
                        
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <!-- Customer Search -->
                            <div class="sm:col-span-6">
                                <label class="block text-sm font-medium text-gray-700">Search Customer</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input type="text" x-model="search" @keydown.enter.prevent="findCustomers" 
                                           class="flex-1 min-w-0 block w-full rounded-none rounded-l-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                                           placeholder="Search by customer name or CID">
                                    <button type="button" @click="findCustomers" 
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-r-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Search
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Customer Select -->
                            <div class="sm:col-span-6">
                                <label for="customer_id" class="block text-sm font-medium text-gray-700">Select Customer</label>
                                <div class="mt-1">
                                    <select x-model="customer_id" id="customer_id" name="customer_id" 
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">— Select Customer —</option>
                                        <template x-for="c in customers" :key="c.id">
                                            <option :value="c.id" x-text="`${c.customer} (${c.cid_abh})`"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Information -->
                    <div class="px-6 py-5 space-y-6">
                        <h2 class="text-lg font-medium text-gray-900">Timeline</h2>
                        
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <!-- Start Time -->
                            <div class="sm:col-span-3">
                                <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time</label>
                                <div class="mt-1">
                                    <input type="datetime-local" id="start_time" name="start_time" 
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           value="{{ old('start_time') }}">
                                </div>
                            </div>
                            
                            <!-- End Time -->
                            <div class="sm:col-span-3">
                                <label for="end_time" class="block text-sm font-medium text-gray-700">End Time</label>
                                <div class="mt-1">
                                    <input type="datetime-local" id="end_time" name="end_time" 
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           value="{{ old('end_time') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Options -->
                    <div class="px-6 py-5 space-y-6">
                        <h2 class="text-lg font-medium text-gray-900">Additional Options</h2>
                        
                        <div class="relative flex items-start">
                            <div class="flex h-5 items-center">
                                <input id="alert" name="alert" type="checkbox" 
                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="alert" class="font-medium text-gray-700">Set Alert</label>
                                <p class="text-gray-500">Check this to receive notifications for this ticket</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="px-6 py-4 bg-gray-50 text-right">
                        <a href="{{ route('tickets.index') }}" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </a>
                        <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                            Save Ticket
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ticketForm() {
    return {
        search: '',
        customers: [],
        customer_id: '',

        findCustomers() {
            if (!this.search.trim()) {
                this.customers = [];
                return;
            }
            
            // Show loading state
            const originalText = this.$el.querySelector('[x-text="Search"]')?.textContent;
            if (this.$el.querySelector('[x-text="Search"]')) {
                this.$el.querySelector('[x-text="Search"]').textContent = 'Searching...';
            }
            
            fetch(`/customers/json?q=${encodeURIComponent(this.search)}`)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => { 
                    this.customers = data; 
                })
                .catch(error => { 
                    console.error('Error fetching customers:', error);
                    this.customers = []; 
                })
                .finally(() => {
                    if (this.$el.querySelector('[x-text="Search"]')) {
                        this.$el.querySelector('[x-text="Search"]').textContent = originalText || 'Search';
                    }
                });
        }
    }
}
</script>
@endpush