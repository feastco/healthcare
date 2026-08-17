@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Manage Permissions" />

    <p class="text-theme-sm text-gray-500 dark:text-gray-400">
        Assign permissions to role <span class="font-medium text-gray-800 dark:text-white">{{ $role->name }}</span>.
    </p>

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <form action="{{ route('permissions.update', $role) }}" method="POST"
            x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($permissions as $permission)
                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3.5 py-2.5 dark:border-gray-700">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                            @checked(in_array($permission->id, old('permissions', $role->permissions->pluck('id')->all()), true))
                            class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-300 dark:border-gray-700">
                        <span class="text-theme-sm text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                    </label>
                @empty
                    <p class="text-theme-sm text-gray-500 dark:text-gray-400">No permissions available.</p>
                @endforelse
            </div>

            @error('permissions')
                <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('permissions.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Cancel
                </a>
                <button type="submit" :disabled="submitting"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">
                    Save Permissions
                </button>
            </div>
        </form>
    </div>
@endsection