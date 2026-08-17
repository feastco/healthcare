@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Users" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
            Manage system users and their roles.
        </p>
        @can('create', \App\Models\User::class)
            <a href="{{ route('users.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">
                Add User
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
        @if ($users->isEmpty())
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">No users registered yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-200 text-theme-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th scope="col" class="px-3 py-3">Name</th>
                            <th scope="col" class="px-3 py-3">Email</th>
                            <th scope="col" class="px-3 py-3">Roles</th>
                            <th scope="col" class="px-3 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-3 py-3 text-theme-sm font-medium text-gray-800 dark:text-white">
                                    {{ $user->name }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $user->email }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $user->roles->pluck('name')->join(', ') ?: '—' }}
                                </td>
                                <td class="px-3 py-3">
                                    @can('update', $user)
                                        <a href="{{ route('users.edit', $user) }}"
                                            class="text-theme-sm font-medium text-brand-500 hover:text-brand-600">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection