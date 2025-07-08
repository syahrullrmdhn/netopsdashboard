<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ config('app.name','Network Ops') }}</title>

  <!-- Inter font -->
  <link 
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" 
    rel="stylesheet"
  >

  <!-- Compiled CSS (Laravel Mix) -->
  <link rel="stylesheet" href="{{ mix('css/app.css') }}">

  <!-- Alpine.js (jika masih diperlukan) -->
  <script 
    src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" 
    defer
  ></script>
</head>
<body class="h-full font-sans antialiased">
  {{ $slot }}

  <!-- Compiled JS (Laravel Mix) -->
  <script src="{{ mix('js/app.js') }}" defer></script>
</body>
</html>