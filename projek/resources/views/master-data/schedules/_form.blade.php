@php
    $action = $action ?? null;
    $method = $method ?? 'POST';
    $schedule = $schedule ?? null;
@endphp

<form action="{{ $action }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="doctor_id"
                class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Doctor</label>
            <select id="doctor_id" name="doctor_id" required
                class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="" disabled @selected(old('doctor_id', $schedule?->doctor_id) === null)>Select doctor</option>
                @foreach ($doctors as $doctor)
                    <option value="{{ $doctor->id }}"
                        @selected(old('doctor_id', $schedule?->doctor_id) == $doctor->id)>
                        {{ $doctor->name }}
                    </option>
                @endforeach
            </select>
            @error('doctor_id')
                <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="day_of_week"
                class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Day of Week</label>
            <select id="day_of_week" name="day_of_week" required
                class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                @foreach ($days as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('day_of_week', $schedule?->day_of_week) == $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('day_of_week')
                <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="start_time"
                class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Start Time</label>
            <input type="time" id="start_time" name="start_time"
                value="{{ old('start_time', $schedule?->start_time ? \Illuminate\Support\Str::substr($schedule->start_time, 0, 5) : null) }}"
                required
                class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            @error('start_time')
                <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="end_time"
                class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">End Time</label>
            <input type="time" id="end_time" name="end_time"
                value="{{ old('end_time', $schedule?->end_time ? \Illuminate\Support\Str::substr($schedule->end_time, 0, 5) : null) }}"
                required
                class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            @error('end_time')
                <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('schedules.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            Cancel
        </a>
        <button type="submit" :disabled="submitting"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">
            {{ $submitLabel }}
        </button>
    </div>
</form>