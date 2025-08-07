@extends('layouts.app')
@section('title','Email Detail')

@section('content')
<div class="max-w-3xl mx-auto bg-white shadow rounded flex flex-col">

  {{-- Cek apakah user sudah connect Google --}}
  @if (!Auth::user()->google_token)
    <div class="flex flex-col items-center py-12">
      <svg class="w-14 h-14 text-gray-300 mb-3" viewBox="0 0 48 48">
        <g>
          <circle cx="24" cy="24" r="24" fill="#eee"/>
          <path fill="#ea4335" d="M36 24.2c0-1.1-.1-2.1-.2-3H24v5.7h6.7c-.3 1.6-1.2 2.9-2.5 3.8v3.2h4.1c2.4-2.2 3.7-5.4 3.7-9.7z"/>
          <path fill="#34a853" d="M24 38c3.2 0 5.8-1.1 7.7-2.9l-4.1-3.2c-1.1.7-2.6 1.1-4.1 1.1-3.1 0-5.7-2.1-6.7-5h-4.2v3.2C15.2 36.6 19.3 38 24 38z"/>
          <path fill="#4a90e2" d="M17.3 27.2c-.3-1-.5-2-.5-3.2s.2-2.2.5-3.2V17.6h-4.2C12.4 19.5 12 21.7 12 24s.4 4.5 1.1 6.4l4.2-3.2z"/>
          <path fill="#fbbc05" d="M24 15.5c1.7 0 3.1.6 4.2 1.7l3.1-3.1C29.8 12.3 27.2 11 24 11c-4.7 0-8.8 1.4-11.8 3.9l4.2 3.2c1-2.9 3.6-5 6.7-5z"/>
        </g>
      </svg>
      <a href="{{ route('google.redirect') }}"
         class="inline-flex items-center px-5 py-2 bg-red-600 text-white font-bold rounded shadow hover:bg-red-700 transition mb-2">
        <svg class="w-5 h-5 mr-2" viewBox="0 0 48 48">
          <g><path fill="#fff" d="M44.5,20H24v8.5h11.7c-1.5,4-5.2,6.9-9.7,6.9c-5.5,0-10-4.5-10-10 s4.5-10,10-10c2.4,0,4.7,0.9,6.4,2.3l6.4-6.4C34.1,7.7,29.3,6,24,6C12.9,6,4,14.9,4,26s8.9,20,20,20c11.1,0,20-8.9,20-20 C44,23.3,44.3,21.7,44.5,20z"/></g>
        </svg>
        Login with Google
      </a>
      <div class="text-gray-500 text-sm">
        Hubungkan akun Google Anda untuk mengakses fitur email.<br>
        Setelah terhubung, reload halaman ini.
      </div>
    </div>
  @else

    {{-- Header --}}
    <header class="flex items-center justify-between px-6 py-3 bg-gray-100 border-b">
      <a href="{{ route('settings.mail.inbox') }}"
         class="text-indigo-600 hover:underline">&larr; Back to Inbox</a>
      <button onclick="window.print()"
              class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
        Print
      </button>
    </header>

    {{-- Message Info --}}
    <div class="px-6 py-4 border-b">
      <h1 class="text-xl font-semibold mb-2">{{ $meta['Subject'] ?? '(No Subject)' }}</h1>
      <p class="text-sm text-gray-600">
        From: {{ $meta['From'] ?? '-' }}<br>
        Date: {{ isset($meta['Date']) ? \Carbon\Carbon::parse($meta['Date'])->format('l, F j, Y H:i') : '-' }}
      </p>
    </div>

    {{-- Body --}}
    <div class="prose max-w-none p-6 overflow-auto flex-1">
      {!! $body !!}
    </div>

    {{-- Reply Toolbar --}}
    <div class="px-6 py-3 bg-gray-50 border-t flex space-x-4">
      <button onclick="document.getElementById('replyForm').classList.toggle('hidden')"
              class="px-4 py-1 bg-green-600 text-white rounded hover:bg-green-700">
        Reply
      </button>
      <button onclick="location.reload()"
              class="px-3 py-1 text-gray-600 hover:text-gray-800">
        Refresh
      </button>
    </div>

    {{-- Hidden Reply Form --}}
    <form id="replyForm" action="{{ route('settings.mail.reply',$id) }}" method="POST"
          class="hidden flex flex-col px-6 py-4 border-t space-y-3">
      @csrf
      <textarea id="replyEditor" name="body" class="hidden">{{ old('body') }}</textarea>
      @error('body')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
      <button type="submit"
              class="self-end px-4 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
        Send Reply
      </button>
    </form>
  @endif

</div>
@endsection

@push('scripts')
@if(Auth::user()->google_token)
<script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY') }}/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#replyEditor',
    plugins: 'autolink lists link paste code help wordcount',
    toolbar: 'undo redo | fontselect | bold italic underline | bullist numlist | link | code',
    menubar: false,
    height: 200,
    forced_root_block: 'p',
    font_formats: 'Poppins=Poppins;Arial=arial,helvetica,sans-serif;Times New Roman=times new roman,times;Courier New=courier new,courier;',
    content_style: "body { font-family: '{{ Auth::user()->default_font }}', sans-serif; }",
    setup: editor => editor.on('change', () => editor.save())
  });
</script>
@endif
@endpush
