@extends('layouts.app')

@section('content')
    <div
        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 sm:p-8">
        <h1 class="text-title-sm font-semibold text-gray-800 dark:text-white">
            Welcome, {{ auth()->user()->name }}
        </h1>
        <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">
            You are signed in as
            <span class="font-medium text-gray-700 dark:text-gray-200">{{ auth()->user()->email }}</span>.
            @if (auth()->user()->getRoleNames()->isNotEmpty())
                Current role:
                <span
                    class="ml-1 inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 text-theme-xs font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">{{ auth()->user()->getRoleNames()->implode(', ') }}</span>
            @endif
        </p>
        <p class="mt-4 text-theme-sm text-gray-500 dark:text-gray-400">
            Use the navigation menu to access the system modules.
        </p>
    </div>
@endsection