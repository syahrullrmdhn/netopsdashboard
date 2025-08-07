@extends('layouts.app')

@section('title', 'Network Monitoring Tools')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">Network Monitoring Tools</h1>
    <div class="space-y-4">
        @forelse($monitorings as $tool)
            <a href="{{ $tool->url }}" target="_blank" class="block transition hover:scale-105">
                <div class="flex items-center gap-4 p-4 rounded-xl bg-white shadow hover:bg-indigo-50 border border-gray-100">
                    <x-dynamic-component :component="$tool->icon" class="w-8 h-8 text-indigo-500"/>
                    <div>
                        <div class="text-lg font-semibold">{{ $tool->name }}</div>
                        <div class="text-xs text-gray-500">{{ $tool->desc }}</div>
                        <div class="text-xs text-blue-500 mt-1">{{ $tool->url }}</div>
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center text-gray-500">Belum ada data monitoring.</div>
        @endforelse
    </div>
</div>
@endsection
