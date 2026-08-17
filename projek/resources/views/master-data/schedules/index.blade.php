@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Schedules" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
            Manage doctor weekly schedules.
        </p>
        @can('create', \App\Models\DoctorSchedule::class)
            <a href="{{ route('schedules.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">
                Add Schedule
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
        @if ($schedules->isEmpty())
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">No schedules registered yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-200 text-theme-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th scope="col" class="px-3 py-3">Doctor</th>
                            <th scope="col" class="px-3 py-3">Day</th>
                            <th scope="col" class="px-3 py-3">Start Time</th>
                            <th scope="col" class="px-3 py-3">End Time</th>
                            <th scope="col" class="px-3 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($schedules as $schedule)
                            <tr>
                                <td class="px-3 py-3 text-theme-sm font-medium text-gray-800 dark:text-white">
                                    {{ $schedule->doctor?->name }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $days[$schedule->day_of_week] ?? $schedule->day_of_week }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ \Illuminate\Support\Str::substr($schedule->start_time, 0, 5) }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ \Illuminate\Support\Str::substr($schedule->end_time, 0, 5) }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        @can('update', $schedule)
                                            <a href="{{ route('schedules.edit', $schedule) }}"
                                                class="text-theme-sm font-medium text-brand-500 hover:text-brand-600">Edit</a>
                                        @endcan
                                        @can('delete', $schedule)
                                            <x-common.delete-confirmation :action="route('schedules.destroy', $schedule)"
                                                label="Delete" />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>
@endsection