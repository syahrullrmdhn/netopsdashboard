@extends('layouts.app')
@section('title', isset($role) ? 'Edit Role' : 'Create Role')

@section('content')
<div class="max-w-xl mx-auto py-6">
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">{{ isset($role) ? 'Edit Role' : 'Add Role' }}</h2>
        <form action="{{ isset($role) ? route('roles.update',$role) : route('roles.store') }}" method="POST" class="space-y-5">
            @csrf
            @if(isset($role)) @method('PUT') @endif
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Role Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $role->name ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Menu Access</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($permissions as $perm)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                {{ (isset($role) && $role->permissions->pluck('id')->contains($perm->id)) ? 'checked' : '' }}>
                            <span class="text-gray-800">{{ ucwords(str_replace('_',' ',$perm->name)) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="flex justify-end">
                <a href="{{ route('roles.index') }}" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</a>
                <button type="submit" class="ml-2 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
