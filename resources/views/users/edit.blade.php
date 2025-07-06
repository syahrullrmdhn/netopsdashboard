@extends('layouts.app')

@section('title','Edit User')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
  <h2 class="text-xl font-semibold mb-6">Edit User</h2>

  <form action="{{ route('users.update',$user) }}" method="POST" class="space-y-6">
    @csrf @method('PUT')

    <div>
      <label class="block text-sm font-medium">Name</label>
      <input type="text" name="name" value="{{ old('name',$user->name) }}"
             class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
      @error('name') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium">Email</label>
      <input type="email" name="email" value="{{ old('email',$user->email) }}"
             class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
      @error('email') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium">New Password <span class="text-gray-400">(optional)</span></label>
        <input type="password" name="password"
               class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
        @error('password') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="block text-sm font-medium">Confirm Password</label>
        <input type="password" name="password_confirmation"
               class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
      </div>
    </div>

    <div>
      <p class="block text-sm font-medium mb-1">Assign Roles</p>
      <div class="space-y-2">
        @foreach($roles as $role)
          <label class="inline-flex items-center">
            <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                   class="h-4 w-4 text-indigo-600 border-gray-300 rounded"
                   @if($user->hasRole($role->name)) checked @endif>
            <span class="ml-2 text-sm">{{ $role->name }}</span>
          </label>
        @endforeach
      </div>
      @error('roles') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
    </div>

    <div class="flex justify-end space-x-2">
      <a href="{{ route('users.index') }}"
         class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</a>
      <button type="submit"
              class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
        Save Changes
      </button>
    </div>
  </form>
</div>
@endsection
