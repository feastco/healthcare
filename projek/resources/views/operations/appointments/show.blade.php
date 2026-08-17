@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Appointment Detail" />

    @if (session('success'))
        <div
            class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-theme-sm text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Patient</dt>
                <dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white">
                    {{ $appointment->patient?->name }}
                </dd>
            </div>

            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Doctor</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">
                    {{ $appointment->doctor?->name }}
                </dd>
            </div>

            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Starts At</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">
                    {{ $appointment->starts_at?->format('d M Y H:i') }}
                </dd>
            </div>

            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ends At</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">
                    {{ $appointment->ends_at?->format('d M Y H:i') }}
                </dd>
            </div>

            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="mt-1">
                    <x-common.status-badge :status="$appointment->status" />
                </dd>
            </div>
        </dl>
    </div>
@endsection