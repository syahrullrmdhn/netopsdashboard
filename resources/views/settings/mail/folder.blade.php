@extends('layouts.app')
@section('title', $title ?? 'Inbox')

@section('content')
<div class="max-w-5xl mx-auto">
    @if (!Auth::user()->google_token)
        <div class="flex flex-col items-center py-16">
            <svg class="w-16 h-16 text-gray-300 mb-5" viewBox="0 0 48 48">
                <circle cx="24" cy="24" r="24" fill="#eee"/>
                <path fill="#ea4335" d="M36 24.2c0-1.1-.1-2.1-.2-3H24v5.7h6.7c-.3 1.6-1.2 2.9-2.5 3.8v3.2h4.1c2.4-2.2 3.7-5.4 3.7-9.7z"/>
                <path fill="#34a853" d="M24 38c3.2 0 5.8-1.1 7.7-2.9l-4.1-3.2c-1.1.7-2.6 1.1-4.1 1.1-3.1 0-5.7-2.1-6.7-5h-4.2v3.2C15.2 36.6 19.3 38 24 38z"/>
                <path fill="#4a90e2" d="M17.3 27.2c-.3-1-.5-2-.5-3.2s.2-2.2.5-3.2V17.6h-4.2C12.4 19.5 12 21.7 12 24s.4 4.5 1.1 6.4l4.2-3.2z"/>
                <path fill="#fbbc05" d="M24 15.5c1.7 0 3.1.6 4.2 1.7l3.1-3.1C29.8 12.3 27.2 11 24 11c-4.7 0-8.8 1.4-11.8 3.9l4.2 3.2c1-2.9 3.6-5 6.7-5z"/>
            </svg>
            <a href="{{ route('google.redirect') }}"
                class="inline-flex items-center px-6 py-2 bg-red-600 text-white font-bold rounded shadow hover:bg-red-700 transition mb-2">
                <svg class="w-5 h-5 mr-2" viewBox="0 0 48 48">
                    <g><path fill="#fff" d="M44.5,20H24v8.5h11.7c-1.5,4-5.2,6.9-9.7,6.9c-5.5,0-10-4.5-10-10 s4.5-10,10-10c2.4,0,4.7,0.9,6.4,2.3l6.4-6.4C34.1,7.7,29.3,6,24,6C12.9,6,4,14.9,4,26s8.9,20,20,20c11.1,0,20-8.9,20-20 C44,23.3,44.3,21.7,44.5,20z"/></g>
                </svg>
                Login with Google
            </a>
            <div class="text-gray-500 text-base mt-2">
                Hubungkan akun Google Anda untuk melihat email.
            </div>
        </div>
    @else
        {{-- Ini bagian lama inbox-mu --}}
        <div class="bg-white shadow rounded p-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <nav class="inline-flex gap-6 font-semibold text-gray-700 mb-3">
                        <a href="{{ route('settings.mail.inbox') }}" class="{{ request()->routeIs('settings.mail.inbox') ? 'text-indigo-600' : 'hover:text-indigo-600' }}">Inbox</a>
                        <a href="{{ route('settings.mail.sent') }}" class="{{ request()->routeIs('settings.mail.sent') ? 'text-indigo-600' : 'hover:text-indigo-600' }}">Sent</a>
                        <a href="{{ route('settings.mail.spam') }}" class="{{ request()->routeIs('settings.mail.spam') ? 'text-indigo-600' : 'hover:text-indigo-600' }}">Spam</a>
                    </nav>
                </div>
                <a href="{{ route('settings.mail.create') }}" class="px-5 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                    <i class="fa fa-plus mr-2"></i> Compose
                </a>
            </div>

            @if (empty($messages))
                <div class="flex flex-col items-center justify-center py-20">
                    <svg class="w-14 h-14 text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5A2.25 2.25 0 0 1 19.5 19.5h-15A2.25 2.25 0 0 1 2.25 17.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75m19.5 0v.243a2.25 2.25 0 0 1-.692 1.619l-7.5 7.5a2.25 2.25 0 0 1-3.182 0l-7.5-7.5A2.25 2.25 0 0 1 2.25 6.993V6.75"/>
                    </svg>
                    <div class="text-xl font-semibold text-gray-600 mb-1">No messages</div>
                    <div class="text-gray-400 text-base">There are no messages in your {{ $title ?? 'Inbox' }} folder.</div>
                </div>
            @else
                {{-- Loop messages --}}
                @foreach($messages as $m)
                    <a href="{{ route('settings.mail.show', $m['id']) }}" class="block border-b hover:bg-gray-50 transition px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-gray-800">{{ $m['meta']['Subject'] ?? '(No Subject)' }}</div>
                                <div class="text-gray-600 text-sm">{{ $m['meta']['From'] ?? '-' }}</div>
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ isset($m['meta']['Date']) ? \Carbon\Carbon::parse($m['meta']['Date'])->format('M d, H:i') : '-' }}
                            </div>
                        </div>
                        <div class="text-gray-600 mt-1 text-sm truncate">
                            {{ $m['snippet'] ?? '' }}
                        </div>
                    </a>
                @endforeach
            @endif
        </div>
    @endif
</div>
@endsection
