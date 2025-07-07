@extends('layouts.app')
@section('title', 'Create Role Management')
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Create New Role</h1>
    <p class="mt-1 text-sm text-gray-500">Define a new role and assign permissions</p>
  </div>

  <div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <form method="POST" action="{{ route('roles.store') }}" class="px-6 py-5">
      @csrf

      {{-- Role Name --}}
      <div class="mb-6">
        <label for="name" class="block text-sm font-medium text-gray-700">Role Name</label>
        <div class="mt-1 relative rounded-md shadow-sm">
          <input type="text" id="name" name="name" value="{{ old('name') }}"
                 class="block w-full pr-10 sm:text-sm rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-300 text-red-900 placeholder-red-300 focus:outline-none focus:ring-red-500 focus:border-red-500 @enderror"
                 placeholder="e.g. content-editor"
                 required>
          @error('name')
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </div>
          @enderror
        </div>
        @error('name')
          <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @else
          <p class="mt-2 text-sm text-gray-500">A unique name for the role (e.g. editor, manager)</p>
        @enderror
      </div>

      {{-- Permissions --}}
      <div class="mb-6">
        <fieldset>
          <legend class="text-sm font-medium text-gray-700">Permissions</legend>
          <p class="mt-1 text-sm text-gray-500">Select all permissions that apply to this role</p>
          
          <div class="mt-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              @foreach($permissions as $perm)
                <div class="relative flex items-start">
                  <div class="flex items-center h-5">
                    <input id="perm-{{ $perm->id }}" name="permissions[]" type="checkbox" value="{{ $perm->name }}"
                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                  </div>
                  <div class="ml-3 text-sm">
                    <label for="perm-{{ $perm->id }}" class="font-medium text-gray-700">
                      {{ $labels[$perm->name] ?? $perm->name }}
                    </label>
                    @if(isset($labels[$perm->name]) && $labels[$perm->name] != $perm->name)
                      <p class="text-gray-500">{{ $perm->name }}</p>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </fieldset>
      </div>

      <div class="flex justify-end space-x-3">
        <a href="{{ route('roles.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          Cancel
        </a>
        <button type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          Save Role
        </button>
      </div>
    </form>
  </div>
</div>
@endsection