@php
    $action = $action ?? null;
    $method = $method ?? 'POST';
    $patient = $patient ?? null;
@endphp

<form action="{{ $action }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="name"
                class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
            <input type="text" id="name" name="name" value="{{ old('name', $patient?->name) }}" required
                class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            @error('name')
                <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="dob"
                class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Date of Birth</label>
            <input type="date" id="dob" name="dob"
                value="{{ old('dob', $patient?->dob?->format('Y-m-d')) }}" required
                class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            @error('dob')
                <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="gender"
                class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Gender</label>
            <select id="gender" name="gender"
                class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="Male" @selected(old('gender', $patient?->gender) === 'Male')>Male</option>
                <option value="Female" @selected(old('gender', $patient?->gender) === 'Female')>Female</option>
            </select>
            @error('gender')
                <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('patients.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            Cancel
        </a>
        <button type="submit" :disabled="submitting"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">
            {{ $submitLabel }}
        </button>
    </div>
</form>