@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Roles" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
            Manage user roles and the permissions attached to them.
        </p>
        @can('create', \Spatie\Permission\Models\Role::class)
            <a href="{{ route('roles.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">
                Add Role
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div
            class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-theme-sm text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div
            class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-theme-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @if ($roles->isEmpty())
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">No roles defined yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-200 text-theme-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th scope="col" class="px-3 py-3">Name</th>
                            <th scope="col" class="px-3 py-3">Users</th>
                            <th scope="col" class="px-3 py-3">Permissions</th>
                            <th scope="col" class="px-3 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($roles as $role)
                            <tr>
                                <td class="px-3 py-3 text-theme-sm font-medium text-gray-800 dark:text-white">
                                    {{ $role->name }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $role->users_count }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $role->permissions_count }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        @can('update', $role)
                                            <a href="{{ route('roles.edit', $role) }}"
                                                class="text-theme-sm font-medium text-brand-500 hover:text-brand-600">Edit</a>
                                        @endcan
                                        @can('delete', $role)
                                            @if ($role->users_count > 0)
                                                <button type="button" disabled title="Role has user assignments"
                                                    class="cursor-not-allowed text-theme-sm font-medium text-red-300 dark:text-red-700">Delete</button>
                                            @else
                                                <form action="{{ route('roles.destroy', $role) }}" method="POST" class="inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this role?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="text-theme-sm font-medium text-red-500 hover:text-red-600">Delete</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $roles->links() }}
            </div>
        @endif
    </div>
@endsection