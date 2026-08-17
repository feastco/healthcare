<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sign in | PKU Healthcare Operations Management</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-full items-center justify-center bg-gray-50 px-4 py-10 font-sans">
    <main class="w-full max-w-[440px]">
        <!-- Brand -->
        <div class="mb-8 text-center">
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-500 shadow-theme-md">
                <svg class="h-7 w-7 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M11 5.5C11 5.08579 11.3358 4.75 11.75 4.75H12.25C12.6642 4.75 13 5.08579 13 5.5V11H18.5C18.9142 11 19.25 11.3358 19.25 11.75V12.25C19.25 12.6642 18.9142 13 18.5 13H13V18.5C13 18.9142 12.6642 19.25 12.25 19.25H11.75C11.3358 19.25 11 18.9142 11 18.5V13H5.5C5.08579 13 4.75 12.6642 4.75 12.25V11.75C4.75 11.3358 5.08579 11 5.5 11H11V5.5Z"
                        fill="currentColor" />
                </svg>
            </div>
            <h1 class="text-title-sm font-semibold text-gray-800">PKU Healthcare</h1>
            <p class="mt-1 text-theme-sm text-gray-500">Operations Management System</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg sm:p-8"
            x-data="{ submitting: false }">
            @if ($errors->has('email') && $errors->first('email') === __('auth.failed'))
                <div role="alert"
                    class="mb-5 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-theme-sm text-error-700">
                    {{ __('auth.failed') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" @submit="submitting = true">
                @csrf

                <div class="mb-5">
                    <label for="email" class="mb-2.5 block text-theme-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                        autocomplete="email" placeholder="Enter your email"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error"
                        class="h-11 w-full rounded-lg border bg-transparent px-4 text-theme-sm text-gray-800 shadow-theme-xs transition placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 {{ $errors->has('email') ? 'border-error-500' : 'border-gray-300 focus:border-brand-500' }}">
                    @error('email')
                        <p id="email-error" class="mt-1.5 text-theme-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="password" class="mb-2.5 block text-theme-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        placeholder="Enter your password"
                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        aria-describedby="password-error"
                        class="h-11 w-full rounded-lg border bg-transparent px-4 text-theme-sm text-gray-800 shadow-theme-xs transition placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 {{ $errors->has('password') ? 'border-error-500' : 'border-gray-300 focus:border-brand-500' }}">
                    @error('password')
                        <p id="password-error" class="mt-1.5 text-theme-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-theme-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/30 disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="submitting">
                    <span x-show="!submitting">Sign in</span>
                    <span x-show="submitting">Signing in…</span>
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-theme-xs text-gray-500">&copy; {{ date('Y') }} PKU Healthcare Operations
            Management System</p>
    </main>
</body>

</html>