@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Patient Detail" />

    @if (session('success'))
        <div
            class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-theme-sm text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
            Identifier:
            <span class="font-medium text-gray-800 dark:text-white">{{ $patient->identifier_pat }}</span>
        </p>
        @can('update', $patient)
            <a href="{{ route('patients.edit', $patient) }}"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">
                Edit Patient
            </a>
        @endcan
    </div>

    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Full Name</dt>
                <dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white">{{ $patient->name }}</dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Date of Birth</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">{{ $patient->dob?->format('d M Y') }}</dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Gender</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">{{ $patient->gender }}</dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Registered At</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">{{ $patient->created_at?->format('d M Y H:i') }}</dd>
            </div>
        </dl>
    </div>
@endsection