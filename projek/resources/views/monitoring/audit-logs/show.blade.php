@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Audit Log Detail" />

    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Actor</dt>
                <dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white">
                    {{ $log->user?->name ?? 'System' }}
                </dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Timestamp</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">
                    {{ $log->created_at?->format('d M Y H:i:s') }}
                </dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Action</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">{{ $log->action }}</dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Entity</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">
                    {{ class_basename($log->entity_type) }}
                </dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Entity ID</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">{{ $log->entity_id }}</dd>
            </div>
        </dl>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <h2 class="text-theme-base font-semibold text-gray-800 dark:text-white">Before State</h2>
                <pre
                    class="mt-2 overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-theme-xs leading-relaxed text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"><code>{{ json_encode($log->before_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
            </div>
            <div>
                <h2 class="text-theme-base font-semibold text-gray-800 dark:text-white">After State</h2>
                <pre
                    class="mt-2 overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-theme-xs leading-relaxed text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"><code>{{ json_encode($log->after_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
            </div>
        </div>
    </div>
@endsection