@extends('layouts.app')

@section('title','PRTG Sensor Tree')

@section('content')
  <h1 class="text-2xl font-bold mb-4">PRTG Sensor Tree</h1>

  <div class="w-full h-[800px] border">
    <iframe
      src="{{ config('prtg.host') }}/sensortree.htm?username={{ config('prtg.username') }}&passhash={{ config('prtg.passhash') }}"
      class="w-full h-full"
      frameborder="0"
      scrolling="auto"
    ></iframe>
  </div>
@endsection
