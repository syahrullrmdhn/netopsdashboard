@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto py-8">
  <h2 class="text-2xl font-bold mb-4">Buat Role Baru</h2>
  <form method="POST" action="{{ route('roles.store') }}">
    @csrf
    <div class="mb-4">
      <label class="block font-medium">Nama Role</label>
      <input type="text" name="name"
             class="mt-1 block w-full border-gray-300 rounded-md"
             value="{{ old('name') }}" required>
      @error('name')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
    </div>
    <div class="mb-4">
      <p class="font-medium">Permissions</p>
      <div class="grid grid-cols-2 gap-2 mt-2">
        @foreach($permissions as $perm)
          <label class="flex items-center">
            <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                   class="mr-2">
            {{ $perm->name }}
          </label>
        @endforeach
      </div>
    </div>
    <button type="submit"
            class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
      Simpan
    </button>
  </form>
</div>
@endsection
