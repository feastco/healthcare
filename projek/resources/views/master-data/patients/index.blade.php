@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Patients" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
            Manage registered patients.
        </p>
        @can('create', \App\Models\Patient::class)
            <a href="{{ route('patients.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">
                Add Patient
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div
            class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-theme-sm text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @if ($patients->isEmpty())
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">No patients registered yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-200 text-theme-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th scope="col" class="px-3 py-3">Identifier</th>
                            <th scope="col" class="px-3 py-3">Name</th>
                            <th scope="col" class="px-3 py-3">Date of Birth</th>
                            <th scope="col" class="px-3 py-3">Gender</th>
                            <th scope="col" class="px-3 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($patients as $patient)
                            <tr>
                                <td class="px-3 py-3 text-theme-sm font-medium text-gray-800 dark:text-white">
                                    {{ $patient->identifier_pat }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $patient->name }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $patient->dob?->format('Y-m-d') }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $patient->gender }}
                                </td>
                                <td class="px-3 py-3">
                                    <a href="{{ route('patients.show', $patient) }}"
                                        class="text-theme-sm font-medium text-brand-500 hover:text-brand-600">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $patients->links() }}
            </div>
        @endif
    </div>
@endsection