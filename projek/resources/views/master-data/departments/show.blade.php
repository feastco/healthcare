@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Department Detail" />

    @if (session('success'))
        <div
            class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-theme-sm text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div
            class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-theme-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
            Department:
            <span class="font-medium text-gray-800 dark:text-white">{{ $department->name }}</span>
        </p>
        <div class="flex items-center gap-3">
            @can('update', $department)
                <a href="{{ route('departments.edit', $department) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">
                    Edit Department
                </a>
            @endcan
            @can('delete', $department)
                <x-common.delete-confirmation :action="route('departments.destroy', $department)" label="Delete" />
            @endcan
        </div>
    </div>

    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Name</dt>
                <dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white">{{ $department->name }}</dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Created At</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">
                    {{ $department->created_at?->format('d M Y H:i') }}</dd>
            </div>
        </dl>
    </div>
@endsection