@extends('layouts.app')
@section('title','All Cacti Graphs')

@push('scripts')
    <script src="https://unpkg.com/alpinejs@3" defer></script>
@endpush

@section('content')
<div class="flex h-screen bg-gray-50">

    {{-- Sidebar --}}
    <aside class="w-64 bg-white border-r border-gray-200 shadow-sm overflow-auto">
        <div class="p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Graph Trees</h2>
            <div class="relative">
                <input type="text"
                       placeholder="Search tree..."
                       class="w-full pl-8 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <ul class="space-y-1 px-2 pb-4 text-sm">
            @foreach($trees as $tree)
                <li x-data="{ open: @json((int)$tree->id === (int)($treeId ?? 0) || collect($items[$tree->id] ?? [])->pluck('item_id')->contains($treeItemId)) }" class="rounded-lg">
                    <div @click="open = !open"
                         class="flex items-center px-3 py-2 hover:bg-gray-100 rounded-lg cursor-pointer transition-colors duration-200">
                        <svg :class="{ 'rotate-90': open }"
                             class="w-4 h-4 mr-2 transform text-gray-500 transition-transform duration-200 flex-shrink-0"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="font-medium text-gray-700">
                            {{ $tree->id }}. {{ $tree->name }}
                        </span>
                    </div>

                    <ul x-show="open" x-collapse class="ml-6 pl-2 mt-1 space-y-1 border-l border-gray-200">
                        @foreach($items[$tree->id] ?? [] as $node)
                            @include('cacti.graphs.partials.tree-item', [
                                'node'       => $node,
                                'treeItemId' => $treeItemId,
                            ])
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Header & Filters --}}
        <div class="bg-white border-b px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Graph Explorer</h1>

                {{-- MODIFIKASI 1: Tambahkan pengecekan sebelum memanggil ->total() --}}
                @if ($graphs instanceof \Illuminate\Pagination\AbstractPaginator)
                    <div class="text-sm text-gray-500">Showing {{ $graphs->total() }} results</div>
                @else
                    <div class="text-sm text-gray-500">Showing {{ $graphs->count() }} results</div>
                @endif

            </div>
        </div>
        <div class="bg-white border-b px-6 py-4">
            <form method="get" class="flex items-end gap-4">
                <input type="hidden" name="tree_item_id" value="{{ $treeItemId }}">
                <div class="flex-1">
                    <label class="block text-xs text-gray-500 mb-1">Search</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ $search }}"
                               placeholder="Search graphs..."
                               class="w-full pl-8 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">From</label>
                    <input type="date" name="start" value="{{ $start }}"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">To</label>
                    <input type="date" name="end" value="{{ $end }}"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm">
                    Apply Filters
                </button>
                <a href="{{ route('cacti.graphs.index') }}"
                   class="text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-100">
                    Reset
                </a>
            </form>
        </div>

        {{-- Grid --}}
        <div class="p-6 bg-gray-50 overflow-auto flex-1">
            @if($graphs->isEmpty())
                <div class="text-center text-gray-500 py-20">
                    @if(! $treeItemId && ! $search)
                        Select a tree or enter a search term to see graphs.
                    @else
                        No graphs found.
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($graphs as $g)
                        @php
                            $s = \Carbon\Carbon::parse($start)->startOfDay()->timestamp;
                            $e = \Carbon\Carbon::parse($end)->endOfDay()->timestamp;
                        @endphp
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
                            <a href="{{ route('cacti.graphs.show',['id'=>$g->id,'start'=>$start,'end'=>$end]) }}"
                               target="_blank" class="block p-4">
                                <img src="{{ route('cacti.graphs.image',['id'=>$g->id,'start'=>$s,'end'=>$e]) }}"
                                     alt="{{ $g->graph_title }}"
                                     class="w-full h-48 object-contain"
                                     loading="lazy"/>
                            </a>
                            <div class="px-4 py-3 bg-gray-50 border-t border-gray-100">
                                <h3 class="text-sm font-medium text-gray-800 truncate">{{ $g->graph_title }}</h3>
                                <div class="text-xs text-gray-500 mt-1">ID: {{ $g->id }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- MODIFIKASI 2: Tambahkan pengecekan sebelum menampilkan link paginasi --}}
                @if ($graphs instanceof \Illuminate\Pagination\AbstractPaginator)
                    <div class="mt-6 px-6">{{ $graphs->links() }}</div>
                @endif

            @endif
        </div>
    </main>
</div>
@endsection
