{{--
  Gunakan x-data untuk menginisialisasi state 'open' jika item ini punya anak.
  State 'open' akan mengontrol visibilitas dari sub-item (anak).
--}}
<li @if(!empty($node->children)) x-data="{ open: false }" @endif>
    @php
        $sel = ((int)($treeItemId ?? 0) === $node->item_id);
    @endphp

    {{-- Gunakan flexbox untuk mensejajarkan nama item dan tombol panah --}}
    <div class="flex items-center justify-between pr-2 rounded-lg text-sm transition-colors duration-200
                {{ $sel ? 'bg-indigo-50' : 'hover:bg-gray-100' }}">

        {{-- LINK UNTUK NAVIGASI (HANYA TEKS) --}}
        <a href="{{ route('cacti.graphs.index', array_merge(request()->except('page'), ['tree_item_id' => $node->item_id])) }}"
           class="flex-grow px-3 py-2 {{ $sel ? 'text-indigo-700 font-medium' : 'text-gray-700' }}">
            <span class="truncate">{{ $node->title }}</span>
        </a>

        {{-- TOMBOL PANAH (HANYA MUNCUL JIKA ADA ANAK) --}}
        @if(!empty($node->children))
            {{-- Tombol ini berfungsi untuk mengubah state 'open' saat di-klik --}}
            <button @click="open = !open" class="p-1 rounded-full hover:bg-gray-200">
                {{-- Ikon panah akan berputar 90 derajat jika 'open' bernilai true --}}
                <svg class="w-4 h-4 text-gray-500 transform transition-transform duration-200"
                     :class="{ 'rotate-90': open }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        @endif
    </div>

    {{-- DAFTAR ANAK (SUB-ITEM) --}}
    @if(!empty($node->children))
        {{-- Gunakan x-show dan x-collapse untuk menampilkan/menyembunyikan dengan animasi --}}
        <ul x-show="open" x-collapse class="ml-6 pl-2 mt-1 space-y-1 border-l border-gray-200">
            @foreach($node->children as $child)
                @include('cacti.graphs.partials.tree-item', [
                    'node'       => $child,
                    'treeItemId' => $treeItemId,
                ])
            @endforeach
        </ul>
    @endif
</li>
