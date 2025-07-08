<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') – {{ config('app.name', 'Network Ops') }}</title>
    <meta name="description" content="@yield('description', 'Network Operations Dashboard')">

    {{-- Google Fonts: Mulish --}}  
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">

    {{-- Favicons --}}  
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    
    {{-- Compiled CSS (Laravel Mix) --}}  
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">

    <style>[x-cloak]{display:none!important}</style>
</head>

<body class="h-full font-sans antialiased bg-gray-50">

    {{-- ===== TOPBAR (fixed, h-16) ===== --}}
    @include('partials.topbar')

    {{-- ===== SIDEBAR (fixed, w-56) ===== --}}
    @include('partials.sidebar')

    {{-- ===== MAIN CONTENT ===== --}}
    <main
        class="ml-56 pt-20 pb-10 px-6
               min-h-[calc(100vh-4rem)] overflow-x-auto">
        @yield('content')
    </main>

    {{-- ===== FOOTER ===== --}}
    @include('partials.footer')

    {{-- Compiled JS (Laravel Mix) --}}  
    <script src="{{ mix('js/app.js') }}" defer></script>

    {{-- ===== EXTRA PAGE-LEVEL SCRIPTS ===== --}}
    @stack('scripts')
</body>
</html>
