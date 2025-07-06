@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto py-8">
    <h2 class="text-2xl font-bold mb-4">Assign Roles to {{ $user->name }}</h2>
    <form method="POST" action="{{ route('users.roles.update', $user) }}">
        @csrf
        <div class="mb-4">
            <div class="grid grid-cols-2 gap-2">
                @foreach($roles as $role)
                    <label class="flex items-center">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                               class="mr-2"
                               @if(in_array($role->id, $userRoles)) checked @endif>
                        {{ $role->name }}
                    </label>
                @endforeach
            </div>
        </div>
        <div class="flex space-x-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Save</button>
            <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-200 rounded">Back</a>
        </div>
    </form>
</div>
@endsection
