@extends('layouts.app')
@section('title', 'Edit Role: ' . $role->name)
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <div class="mb-8">
    <div class="flex items-center">
      <a href="{{ route('roles.index') }}" class="mr-2 text-gray-400 hover:text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
        </svg>
      </a>
      <h1 class="text-2xl font-bold text-gray-900">Edit Role: {{ $role->name }}</h1>
    </div>
    <p class="mt-1 text-sm text-gray-500">Update role details and permissions</p>
  </div>

  <div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <form method="POST" action="{{ route('roles.update', $role->id) }}" class="px-6 py-5">
      @csrf
      @method('PUT')

      {{-- Role Name --}}
      <div class="mb-6">
        <label for="name" class="block text-sm font-medium text-gray-700">Role Name</label>
        <div class="mt-1 relative rounded-md shadow-sm">
          <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}"
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
          <p class="mt-2 text-sm text-gray-500">A unique name for the role</p>
        @enderror
      </div>

      {{-- Permissions --}}
      <div class="mb-6">
        <fieldset>
          <legend class="text-sm font-medium text-gray-700">Permissions</legend>
          <p class="mt-1 text-sm text-gray-500">Select permissions for this role</p>
          
          <div class="mt-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              @foreach($permissions as $perm)
                <div class="relative flex items-start">
                  <div class="flex items-center h-5">
                    <input id="perm-{{ $perm->id }}" name="permissions[]" type="checkbox" value="{{ $perm->name }}"
                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                           {{ in_array($perm->name, $rolePermissions) ? 'checked' : '' }}>
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

      <div class="flex justify-between items-center pt-5 border-t border-gray-200">
        @can('role-delete')
          <form action="{{ route('roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
              <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              Delete Role
            </button>
          </form>
        @endcan
        <div class="flex justify-end space-x-3">
          <a href="{{ route('roles.index') }}"
             class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Cancel
          </a>
          <button type="submit"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Update Role
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection