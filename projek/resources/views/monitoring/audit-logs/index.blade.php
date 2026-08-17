@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Audit Logs" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
            View audit trails of system mutations. Read-only.
        </p>
    </div>

    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @if ($logs->isEmpty())
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">No audit logs yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-200 text-theme-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th scope="col" class="px-3 py-3">Timestamp</th>
                            <th scope="col" class="px-3 py-3">Actor</th>
                            <th scope="col" class="px-3 py-3">Action</th>
                            <th scope="col" class="px-3 py-3">Entity</th>
                            <th scope="col" class="px-3 py-3">Entity ID</th>
                            <th scope="col" class="px-3 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($logs as $log)
                            <tr>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $log->created_at?->format('d M Y H:i:s') }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm font-medium text-gray-800 dark:text-white">
                                    {{ $log->user?->name ?? 'System' }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $log->action }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ class_basename($log->entity_type) }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $log->entity_id }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('audit-logs.show', $log) }}"
                                            class="text-theme-sm font-medium text-brand-500 hover:text-brand-600">View</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection