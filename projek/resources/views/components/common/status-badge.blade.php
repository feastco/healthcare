@props(['status'])

@php
    $value = $status instanceof \App\Enums\AppointmentStatus || $status instanceof \App\Enums\InvoiceState
        ? $status->value
        : $status;

    $styles = [
        'SCHEDULED' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400',
        'CONFIRMED' => 'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-400',
        'WAITING' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'IN_PROGRESS' => 'bg-teal-100 text-teal-900 dark:bg-teal-500/20 dark:text-teal-300',
        'COMPLETED' => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400',
        'CANCELLED' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        'NO_SHOW' => 'bg-red-50/60 text-red-400 dark:bg-red-500/10 dark:text-red-400/70',
        'UNPAID' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'PARTIALLY_PAID' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'PAID' => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400',
    ];

    $labels = [
        'SCHEDULED' => 'Scheduled',
        'CONFIRMED' => 'Confirmed',
        'WAITING' => 'Waiting',
        'IN_PROGRESS' => 'In Progress',
        'COMPLETED' => 'Completed',
        'CANCELLED' => 'Cancelled',
        'NO_SHOW' => 'No Show',
        'UNPAID' => 'Unpaid',
        'PARTIALLY_PAID' => 'Partially Paid',
        'PAID' => 'Paid',
    ];
@endphp

<span
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $styles[$value] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $labels[$value] ?? $value }}
</span>