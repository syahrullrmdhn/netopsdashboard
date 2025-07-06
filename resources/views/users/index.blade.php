@extends('layouts.app')

@section('title','User Management')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-6">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">User Management</h1>
    <a href="{{ route('users.create') }}"
       class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
      + Add User
    </a>
  </div>

  {{-- Table --}}
  <div class="bg-white shadow rounded-lg overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roles</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        @forelse($users as $u)
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-4">{{ $u->name }}</td>
            <td class="px-6 py-4">{{ $u->email }}</td>
            <td class="px-6 py-4">
              @if($u->roles->isEmpty())
                <span class="text-xs italic text-gray-400">—</span>
              @else
                @foreach($u->roles as $r)
                  <span class="inline-block px-2 py-0.5 bg-indigo-100 text-indigo-800 text-xs rounded">
                    {{ $r->name }}
                  </span>
                @endforeach
              @endif
            </td>
            <td class="px-6 py-4 text-right space-x-2">
              <a href="{{ route('users.edit',$u) }}"
                 class="text-yellow-600 hover:underline">Edit</a>

              <form action="{{ route('users.resetPassword',$u) }}"
                    method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="text-blue-600 hover:underline"
                        onclick="return confirm('Reset password ke “password”?')">
                  Reset PW
                </button>
              </form>

              <form action="{{ route('users.destroy',$u) }}"
                    method="POST" class="inline">
                @csrf @method('DELETE')
                <button type="submit"
                        class="text-red-600 hover:underline"
                        onclick="return confirm('Hapus user ini?')">
                  Delete
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
              No users found.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($users->hasPages())
    <div>
      {{ $users->links('vendor.pagination.tailwind') }}
    </div>
  @endif

</div>
@endsection
