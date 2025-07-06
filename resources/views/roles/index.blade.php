@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-8 space-y-4">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Role Management</h2>
        <a href="{{ route('roles.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">+ Add Role</a>
    </div>

    <table class="w-full border rounded bg-white shadow">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 text-left">Role</th>
                <th class="px-4 py-2 text-left">Permissions</th>
                <th class="px-4 py-2">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($roles as $role)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $role->name }}</td>
                    <td class="px-4 py-2">
                        {{ $role->permissions->pluck('name')->join(', ') }}
                    </td>
                    <td class="px-4 py-2 text-center">
                        <a href="{{ route('roles.edit', $role) }}" class="text-indigo-600 hover:underline">Edit</a>
                        <form action="{{ route('roles.destroy', $role) }}" method="POST" class="inline" onsubmit="return confirm('Delete this role?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline ml-2" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
