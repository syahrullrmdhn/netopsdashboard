@extends('layouts.app')
@section('title', 'Shift Handover')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4 space-y-8">

  <!-- Header Section -->
  <div class="bg-white shadow rounded-lg p-6 flex justify-between items-center">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">
        Shift Handover:
        <span class="text-blue-600">{{ ucfirst($cur) }}</span>
        &rarr;
        <span class="text-green-600">{{ ucfirst($next) }}</span>
      </h1>
      <p class="mt-1 text-gray-700">
        Current: <span class="font-medium">{{ $curUser->name ?? 'N/A' }}</span> —
        Next:    <span class="font-medium">{{ $nextUser->name ?? 'N/A' }}</span>
      </p>
    </div>
    <div class="text-sm text-gray-500">
      {{ now()->format('l, F j, Y H:i') }}
    </div>
  </div>

  @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-400 p-4">
      <p class="text-green-800">{{ session('success') }}</p>
    </div>
  @endif

  <!-- Open Issues Table -->
  <div class="bg-white shadow rounded-lg overflow-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ticket #</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">CID</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Issue Type</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Opened</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Last Update</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        @foreach($tickets as $t)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-2 text-sm font-mono text-blue-600">{{ $t->ticket_number }}</td>
            <td class="px-4 py-2 text-sm text-gray-900">{{ $t->customer->customer }}</td>
            <td class="px-4 py-2 text-sm text-gray-900">{{ $t->customer->cid_abh }}</td>
            <td class="px-4 py-2 text-sm text-gray-900">{{ $t->customer->supplier->nama_supplier }}</td>
            <td class="px-4 py-2 text-sm text-gray-900">{{ $t->issue_type }}</td>
            <td class="px-4 py-2 text-sm text-gray-500">{{ $t->open_date->format('d/m H:i') }}</td>
            <td class="px-4 py-2 text-sm text-gray-500">
              {{ optional($t->updates->first())->created_at?->format('d/m H:i') ?? '—' }}
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- Handover Form -->
  <form action="{{ route('noc.storeHandover') }}" method="POST" class="space-y-6">
    @csrf
    <input type="hidden" name="shift"      value="{{ $cur }}">
    <input type="hidden" name="to_user_id" value="{{ $nextUser->id ?? '' }}">

    <!-- Issues Summary -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Issues Summary</h2>
      </div>
      <div class="px-6 py-4">
      <textarea id="issuesEditor" name="issues" class="hidden" rows="20" cols="100">
      @php
      // Inisialisasi array untuk menampung setiap baris output
      $outputLines = [];
      $no = 1;

      // Loop melalui setiap tiket untuk membangun string-nya
      foreach ($tickets as $t) {
          // Tentukan teks update: gunakan keterangan jika ada, jika tidak, gunakan teks default
          $updateText = ($t->updates->isNotEmpty() && $t->updates->first()->keterangan)
              ? trim($t->updates->first()->keterangan)
              : 'Belum ada update.';

          // Buat satu baris lengkap untuk tiket saat ini
          $outputLines[] = "{$no}. {$t->ticket_number} | {$t->customer->customer} | {$t->issue_type} | {$updateText}";
          
          $no++;
      }

      // Gabungkan semua baris menjadi satu string tunggal dengan pemisah baris baru
      echo implode("\n", $outputLines);
      @endphp
      </textarea>
        <div class="mt-2 flex space-x-2">
          <button type="button" onclick="copyMarkdown()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm font-medium bg-white hover:bg-gray-50">
            Copy as Markdown
          </button>
          <button type="button" onclick="insertTableTemplate()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm font-medium bg-white hover:bg-gray-50">
            Insert Table Template
          </button>
        </div>
      </div>
    </div>

    <!-- Notes & Tasks -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Notes & Tasks</h2>
      </div>
      <div class="px-6 py-4">
        <textarea id="notesEditor" name="notes" class="hidden">{{ old('notes') }}</textarea>
        <div class="mt-2">
          <button type="button" onclick="copyNotes()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm font-medium bg-white hover:bg-gray-50">
            Copy Notes
          </button>
        </div>
      </div>
    </div>

    <div class="flex justify-end">
      <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md shadow">
        Handover to {{ $nextUser->name ?? 'Next Engineer' }}
      </button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/z191hzbqyi5onz961oh7immhn0prmwsmmduc42iolf5bxu8j/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#issuesEditor',
    plugins: 'autolink lists table paste help wordcount',
    toolbar: 'undo redo | styles | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | table | help',
    menubar: false,
    height: 300,
    forced_root_block : 'p', // Penting agar tiap enter jadi <p> (paragraf)
    setup: editor => editor.on('change', () => editor.save())
  });

  tinymce.init({
    selector: '#notesEditor',
    plugins: 'autolink lists paste help wordcount',
    toolbar: 'undo redo | styles | bold italic | bullist numlist outdent indent | help',
    menubar: false,
    height: 200,
    setup: editor => editor.on('change', () => editor.save())
  });

  function copyMarkdown() {
    const editor = tinymce.get('issuesEditor');
    const text = editor.getContent({ format: 'text' })
      .trim()
      .replace(/\r\n|\r/g, '\n');
    navigator.clipboard.writeText(text)
      .then(() => notify('Markdown copied to clipboard!', 'green'))
      .catch(() => notify('Failed to copy text', 'red'));
  }

  function copyNotes() {
    const html = tinymce.get('notesEditor').getContent();
    const text = html.replace(/<\/p>/gi, '\n').replace(/<[^>]+>/g, '').trim();
    navigator.clipboard.writeText(text)
      .then(() => notify('Notes copied to clipboard!', 'green'))
      .catch(() => notify('Failed to copy text', 'red'));
  }

  function insertTableTemplate() {
    const template = `
      <table style="width:100%; border-collapse: collapse;">
        <thead><tr style="background-color:#f3f4f6;">
          <th style="border:1px solid #d1d5db; padding:8px;">Ticket #</th>
          <th style="border:1px solid #d1d5db; padding:8px;">Customer</th>
          <th style="border:1px solid #d1d5db; padding:8px;">Issue</th>
          <th style="border:1px solid #d1d5db; padding:8px;">Status</th>
        </tr></thead>
        <tbody>
          <tr>
            <td style="border:1px solid #d1d5db; padding:8px;">-</td>
            <td style="border:1px solid #d1d5db; padding:8px;">-</td>
            <td style="border:1px solid #d1d5db; padding:8px;">-</td>
            <td style="border:1px solid #d1d5db; padding:8px;">-</td>
          </tr>
        </tbody>
      </table>`;
    tinymce.get('issuesEditor').execCommand('mceInsertContent', false, template);
  }

  function notify(msg, color) {
    const n = document.createElement('div');
    n.className = `fixed bottom-4 right-4 bg-${color}-500 text-white px-4 py-2 rounded shadow-lg`;
    n.textContent = msg;
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 3000);
  }
</script>
@endpush
