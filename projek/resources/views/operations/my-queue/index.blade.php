@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="My Queue" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
            Today's queue of patients waiting for consultation.
        </p>
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
        @if ($appointments->isEmpty())
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">No patients in your queue today.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-200 text-theme-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th scope="col" class="px-3 py-3">Patient</th>
                            <th scope="col" class="px-3 py-3">Doctor</th>
                            <th scope="col" class="px-3 py-3">Starts At</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                            <th scope="col" class="px-3 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($appointments as $appointment)
                            <tr>
                                <td class="px-3 py-3 text-theme-sm font-medium text-gray-800 dark:text-white">
                                    {{ $appointment->patient?->name }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $appointment->doctor?->name }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $appointment->starts_at?->format('d M Y H:i') }}
                                </td>
                                <td class="px-3 py-3">
                                    <x-common.status-badge :status="$appointment->status" />
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        @can('updateStatus', [$appointment, \App\Enums\AppointmentStatus::IN_PROGRESS])
                                            <form method="POST" action="{{ route('my-queue.status', $appointment) }}"
                                                x-data="{ submitting: false }" @submit="submitting = true">
                                                @csrf
                                                <input type="hidden" name="status"
                                                    value="{{ \App\Enums\AppointmentStatus::IN_PROGRESS->value }}">
                                                <button type="submit" :disabled="submitting"
                                                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60">
                                                    Start
                                                </button>
                                            </form>
                                        @endcan

                                        @can('updateStatus', [$appointment, \App\Enums\AppointmentStatus::COMPLETED])
                                            <form method="POST" action="{{ route('my-queue.status', $appointment) }}"
                                                x-data="{ submitting: false }" @submit="submitting = true">
                                                @csrf
                                                <input type="hidden" name="status"
                                                    value="{{ \App\Enums\AppointmentStatus::COMPLETED->value }}">
                                                <button type="submit" :disabled="submitting"
                                                    class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60">
                                                    Finish
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $appointments->links() }}
            </div>
        @endif
    </div>
@endsection