@extends('layouts.app')
@section('title','Compose Email')

@section('content')
<div class="max-w-3xl mx-auto bg-white shadow rounded flex flex-col h-[calc(100vh-6rem)]">
  {{-- Header Toolbar --}}
  <header class="flex items-center space-x-4 px-4 py-2 bg-gray-100 border-b">
    <button type="submit" form="composeForm"
            class="px-4 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
      Send
    </button>
    <button type="button" onclick="document.getElementById('composeForm').reset()"
            class="px-3 py-1 text-gray-600 hover:text-gray-800">
      Discard
    </button>
  </header>

  {{-- Form & Fields --}}
  <form id="composeForm" action="{{ route('settings.mail.store') }}" method="POST" class="flex-1 flex flex-col overflow-hidden">
    @csrf

    <div class="px-6 py-4 flex-shrink-0 space-y-3">
      <div>
        <input name="to" type="email" placeholder="To"
               class="w-full border-b border-gray-300 focus:outline-none focus:border-blue-500"
               value="{{ old('to') }}">
        @error('to')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
      </div>
      <div>
        <input name="subject" type="text" placeholder="Subject"
               class="w-full border-b border-gray-300 focus:outline-none focus:border-blue-500"
               value="{{ old('subject') }}">
        @error('subject')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
      </div>
    </div>

    {{-- Editor Container --}}
    <div class="flex-1 px-6 overflow-auto">
      <textarea id="bodyEditor" name="body" class="hidden">{{ old('body') }}</textarea>
      @error('body')<p class="text-red-600 text-sm mt-1 px-6">{{ $message }}</p>@enderror
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY') }}/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#bodyEditor',
    plugins: 'autolink lists link paste code help wordcount',
    toolbar: 'undo redo | fontselect | bold italic underline | bullist numlist | link | code',
    menubar: false,
    height: document.querySelector('#bodyEditor').closest('.flex-1').clientHeight,
    forced_root_block: 'p',
    font_formats: 'Poppins=Poppins;Arial=arial,helvetica,sans-serif;Times New Roman=times new roman,times;Courier New=courier new,courier;',
    content_style: "body { font-family: '{{ $defaultFont }}', sans-serif; }",
    setup: editor => {
      editor.on('init', () => {
        // Only insert signature if the composer is empty
        if (!editor.getContent().trim()) {
          let sig = `{!! nl2br(e($signature)) !!}`;
          // wrap signature in its own paragraph
          editor.setContent('<p><br></p>' + `<p>${sig}</p>`);
          editor.save();
        }
      });
      editor.on('change', () => editor.save());
    }
  });
</script>
@endpush
