{{-- resources/views/customers/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Customer List')

@section('content')
@php
    // group the current page of customers by customer_group_id
    $groups = $customers->getCollection()->groupBy('customer_group_id');
@endphp

<div
    x-data="{
        // modal state
        isAddModalOpen: false,
        isDocModalOpen: false,

        // document viewer state
        docUrl: '',
        docTitle: '',
        docIsImage: false,
        isLoading: false,
        hasError: false,
        zoomLevel: 1,

        // open viewer
        openDocModal(url) {
            this.docUrl      = url;
            this.docTitle    = url.split('/').pop();
            this.docIsImage  = /\.(jpe?g|png|gif|svg)$/i.test(url);
            this.zoomLevel   = 1;
            this.isLoading   = true;
            this.hasError    = false;
            this.isDocModalOpen = true;
        },
        // close viewer
        closeDocModal() {
            this.isDocModalOpen = false;
            setTimeout(() => this.docUrl = '', 300);
        }
    }"
    x-on:keydown.escape.window="isDocModalOpen && closeDocModal()"
>
  <div class="space-y-6">

    {{-- ─── HEADER & “ADD” BUTTON ─────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h1 class="text-2xl font-bold text-gray-900">Customer Management</h1>
      <button
        x-on:click="isAddModalOpen = true"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
        <x-heroicon-o-plus class="w-5 h-5"/> Add New Customer
      </button>
    </div>

    {{-- ─── FILTER BAR ─────────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('customers.index') }}"
          class="flex flex-col sm:flex-row sm:items-end gap-4">
      {{-- Search --}}
      <div class="flex-1">
        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
        <input
          type="text"
          name="search"
          id="search"
          value="{{ request('search') }}"
          placeholder="Search customers…"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        />
      </div>

      {{-- Customer Group --}}
      <div>
        <label for="group_id" class="block text-sm font-medium text-gray-700">Group</label>
        <select
          name="group_id"
          id="group_id"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        >
          <option value="">All Groups</option>
          @foreach($groupsList as $g)
            <option value="{{ $g->id }}" {{ request('group_id') == $g->id ? 'selected' : '' }}>
              {{ $g->group_name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Status --}}
      <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select
          name="status"
          id="status"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        >
          <option value="">All Statuses</option>
          @foreach($statuses as $key => $label)
            <option value="{{ $key }}" {{ (string)request('status') === (string)$key ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Apply / Reset --}}
      <div class="flex gap-2">
        <button
          type="submit"
          class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
        >Filter</button>
        <a
          href="{{ route('customers.index') }}"
          class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
        >Reset</a>
      </div>
    </form>

    {{-- ─── GROUP CARDS ────────────────────────────────────────────────────── --}}
    @foreach($groups as $groupCustomers)
      <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        {{-- Group header --}}
        <div class="px-6 py-4 bg-gray-50 border-b flex items-center gap-3">
          <x-heroicon-o-user-group class="w-7 h-7 text-indigo-500"/>
          <div>
            <h2 class="text-xl font-bold text-gray-800">
              {{ $groupCustomers->first()->group->group_name ?? 'Uncategorized' }}
            </h2>
            <p class="text-sm text-gray-500">
              {{ $groupCustomers->count() }} customer(s)
            </p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm divide-y divide-gray-200">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">#</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Name</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">ABH SID</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Supplier</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">SID Supplier</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Service</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Status</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Documents</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase">Network Info</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              @foreach($groupCustomers as $cust)
                <tr class="hover:bg-indigo-50/50">
                  <td class="px-4 py-3">{{ $loop->iteration }}</td>
                  <td class="px-4 py-3 font-semibold">{{ $cust->customer }}</td>
                  <td class="px-4 py-3">{{ $cust->cid_abh }}</td>
                  <td class="px-4 py-3">{{ optional($cust->supplier)->nama_supplier ?? 'N/A' }}</td>
                  <td class="px-4 py-3">{{ optional($cust->supplier)->cid_supplier ?? 'N/A' }}</td>
                  <td class="px-4 py-3">{{ optional($cust->serviceType)->service_name ?? '–' }}</td>
                  <td class="px-4 py-3">
                    @php
                      $map = [
                        1 => ['bg-green-100','text-green-800','Active'],
                        2 => ['bg-yellow-100','text-yellow-800','Pending'],
                        3 => ['bg-orange-100','text-orange-800','Suspended'],
                        4 => ['bg-red-100','text-red-800','Terminated'],
                      ];
                      [$bg,$tx,$label] = $map[$cust->status] ?? ['bg-gray-100','text-gray-800','Unknown'];
                    @endphp
                    <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full {{ $bg }} {{ $tx }}">
                      {{ $label }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    @if($cust->no_sdn)
                      <button
                        x-on:click="openDocModal('https://customer.abhinawa.com/uploads/{{ $cust->no_sdn }}')"
                        class="text-indigo-600 hover:underline block"
                      >SDN: View</button>
                    @endif
                    @if($cust->topology)
                      <button
                        x-on:click="openDocModal('https://customer.abhinawa.com/uploads/{{ $cust->topology }}')"
                        class="text-indigo-600 hover:underline block"
                      >Topology: View</button>
                    @endif
                  </td>
                  <td class="px-4 py-3">
                    <ul class="text-xs text-gray-600 space-y-1">
                      <li><strong>VLAN:</strong> {{ $cust->vlan ?? '–' }}</li>
                      <li><strong>IP:</strong> {{ $cust->ip_address ?? '–' }}</li>
                      <li><strong>Prefix:</strong> {{ $cust->prefix ?? '–' }}</li>
                      <li><strong>XC-ID:</strong> {{ $cust->xconnect_id ?? '–' }}</li>
                    </ul>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endforeach

    {{-- ─── PAGINATION ────────────────────────────────────────────────────── --}}
    @if($customers->hasPages())
      <div class="pt-4 flex justify-center">
        {{ $customers->onEachSide(1)->links('vendor.pagination.tailwind') }}
      </div>
    @endif

  </div>

  {{-- ─── ADD CUSTOMER INFO MODAL ───────────────────────────────────────── --}}
  <div
    x-show="isAddModalOpen"
    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"  x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60"
    style="display: none;"
  >
    <div
      x-on:click.outside="isAddModalOpen = false"
      class="bg-white rounded-lg shadow-lg max-w-md w-full p-6"
    >
      <h3 class="text-lg font-semibold mb-4">Add New Customer</h3>
      <p class="mb-6">
        Untuk menambahkan customer, silakan akses
        <a href="https://customer.abhinawa.com" target="_blank" class="text-indigo-600 hover:underline">
          customer.abhinawa.com
        </a>.
      </p>
      <div class="text-right">
        <button
          x-on:click="isAddModalOpen = false"
          class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
        >Close</button>
      </div>
    </div>
  </div>

  {{-- ─── DOCUMENT VIEWER MODAL ──────────────────────────────────────────── --}}
  <div
    x-show="isDocModalOpen"
    x-transition
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80"
    style="display: none;"
  >
    <div
      x-on:click.outside="closeDocModal()"
      class="bg-white rounded-lg overflow-hidden shadow-xl max-w-4xl w-full flex flex-col h-[90vh]"
    >
      {{-- header --}}
      <div class="flex items-center justify-between bg-gray-800 p-3 flex-shrink-0">
        <h3 x-text="docTitle" class="text-white truncate"></h3>
        <div class="flex items-center space-x-2">
          <button
            x-on:click="zoomLevel = Math.max(0.2, zoomLevel - 0.2)"
            :disabled="!docIsImage"
            class="p-2 disabled:opacity-50"
          ><x-heroicon-o-magnifying-glass-minus class="w-5 h-5 text-white"/></button>
          <button
            x-on:click="zoomLevel += 0.2"
            :disabled="!docIsImage"
            class="p-2 disabled:opacity-50"
          ><x-heroicon-o-magnifying-glass-plus class="w-5 h-5 text-white"/></button>
          <a :href="docUrl" download class="p-2">
            <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-white"/>
          </a>
          <button x-on:click="closeDocModal()" class="p-2">
            <x-heroicon-o-x-mark class="w-5 h-5 text-white"/>
          </button>
        </div>
      </div>
      {{-- body --}}
      <div class="p-4 flex-grow flex items-center justify-center relative bg-gray-200">
        {{-- loading --}}
        <div
          x-show="isLoading"
          class="absolute inset-0 flex items-center justify-center bg-gray-800/50"
        >
          <svg class="animate-spin h-8 w-8 text-white" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962
                     7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
          </svg>
        </div>
        {{-- error --}}
        <div
          x-show="hasError"
          class="absolute inset-0 flex flex-col items-center justify-center text-red-500"
        >
          <x-heroicon-o-exclamation-triangle class="w-12 h-12"/>
          <p class="mt-2 font-semibold">Failed to load document.</p>
        </div>
        {{-- image preview --}}
        <template x-if="docIsImage">
          <img
            :src="docUrl"
            x-on:load="isLoading = false"
            x-on:error="isLoading = false; hasError = true"
            class="max-w-full max-h-full transition-transform duration-300"
            :style="{ transform: 'scale(' + zoomLevel + ')' }"
          />
        </template>
        {{-- PDF preview --}}
        <template x-if="!docIsImage">
          <iframe
            :src="docUrl + '#toolbar=0'"
            x-on:load="isLoading = false"
            x-on:error="isLoading = false; hasError = true"
            class="w-full h-full border-0"
          ></iframe>
        </template>
      </div>
    </div>
  </div>

</div>
@endsection
