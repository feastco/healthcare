@php
    $action = $action ?? null;
    $method = $method ?? 'POST';
    $user = $user ?? null;
    $roles = $roles ?? collect();
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
            <input type="text" id="name" name="name" value="{{ old('name', $user?->name) }}" required
                class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            @error('name')
                <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email"
                class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user?->email) }}" required
                class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            @error('email')
                <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password"
                class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
            <input type="password" id="password" name="password" value="{{ old('password') }}"
                @if ($user === null) required @endif
                placeholder="{{ $user !== null ? 'Leave blank to keep current password' : '' }}"
                class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            @error('password')
                <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-6">
        <p class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Roles</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($roles as $role)
                <label
                    class="flex items-center gap-2 rounded-lg border border-gray-200 px-3.5 py-2.5 dark:border-gray-700">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                        @checked(in_array($role->id, old('roles', $user?->roles->pluck('id')->all() ?? []), true))
                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-300 dark:border-gray-700">
                    <span class="text-theme-sm text-gray-700 dark:text-gray-300">{{ $role->name }}</span>
                </label>
            @empty
                <p class="text-theme-sm text-gray-500 dark:text-gray-400">No roles available.</p>
            @endforelse
        </div>
        @error('roles')
            <p class="mt-1.5 text-theme-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('users.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            Cancel
        </a>
        <button type="submit" :disabled="submitting"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">
            {{ $submitLabel }}
        </button>
    </div>
</form>