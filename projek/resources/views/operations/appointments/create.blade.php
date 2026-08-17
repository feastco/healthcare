@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Create Appointment" />

    @if (session('error'))
        <div
            class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-theme-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <form action="{{ route('appointments.store') }}" method="POST" x-data="appointmentForm" @submit="submitting = true">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="patient_id"
                        class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Patient</label>
                    <select id="patient_id" name="patient_id" required
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="" disabled @selected(old('patient_id') === null)>Select patient</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" @selected(old('patient_id') == $patient->id)>
                                {{ $patient->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('patient_id')
                        <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="doctor_id"
                        class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Doctor</label>
                    <select id="doctor_id" name="doctor_id" x-model="doctorId" required
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="" disabled @selected(old('doctor_id') === null)>Select doctor</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected(old('doctor_id') == $doctor->id)>
                                {{ $doctor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('doctor_id')
                        <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="starts_at"
                        class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Starts At</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at') }}" required
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('starts_at')
                        <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ends_at"
                        class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Ends At</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at') }}" required
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('ends_at')
                        <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div x-show="doctorId" class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-theme-sm font-medium text-gray-800 dark:text-white">Weekly schedule <span
                        class="font-normal text-gray-500 dark:text-gray-400">(reference only — availability is validated by
                        the system when you save)</span></p>
                <template x-if="doctorSchedules[doctorId] && doctorSchedules[doctorId].length > 0">
                    <ul class="mt-3 space-y-1.5">
                        <template x-for="entry in doctorSchedules[doctorId]" :key="entry.day">
                            <li class="flex items-center justify-between text-theme-sm text-gray-700 dark:text-gray-300">
                                <span x-text="entry.day"></span>
                                <span x-text="entry.start_time + ' – ' + entry.end_time"></span>
                            </li>
                        </template>
                    </ul>
                </template>
                <template x-if="!doctorSchedules[doctorId] || doctorSchedules[doctorId].length === 0">
                    <p class="mt-3 text-theme-sm text-gray-500 dark:text-gray-400">No weekly schedule registered for this doctor.</p>
                </template>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('appointments.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Cancel
                </a>
                <button type="submit" :disabled="submitting"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">
                    Create Appointment
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        window.appointmentForm = function () {
            return {
                doctorId: {{ old('doctor_id') ?: 'null' }},
                doctorSchedules: @json($doctorSchedules),
                submitting: false,
            };
        };
    </script>
@endpush