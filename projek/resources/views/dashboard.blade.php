@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Dashboard" />

    @php
        $patientCount = number_format($totalPatients, 0, ',', '.');
        $revenue = number_format($totalRevenue, 2, ',', '.');
    @endphp

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div
            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-theme-sm font-medium text-gray-500 dark:text-gray-400">Total Patients</h2>
            <p class="mt-2 text-title-lg font-semibold text-gray-800 dark:text-white">{{ $patientCount }}</p>
        </div>
        <div
            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-theme-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue (Simulated)</h2>
            <p class="mt-2 text-title-lg font-semibold text-gray-800 dark:text-white">{{ $revenue }}</p>
        </div>
    </div>

    {{-- Today's Queue --}}
    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <h2 class="text-title-sm font-semibold text-gray-800 dark:text-white">Today's Queue</h2>

        @if ($todayAppointments->isEmpty())
            <p class="mt-4 text-theme-sm text-gray-500 dark:text-gray-400">No appointments today.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-200 text-theme-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th scope="col" class="px-3 py-3">Patient</th>
                            <th scope="col" class="px-3 py-3">Doctor</th>
                            <th scope="col" class="px-3 py-3">Time</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($todayAppointments as $appointment)
                            <tr>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $appointment->patient?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $appointment->doctor?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $appointment->starts_at?->format('H:i') }}
                                </td>
                                <td class="px-3 py-3">
                                    <span
                                        class="inline-flex rounded-full bg-gray-50 px-2.5 py-0.5 text-theme-xs font-medium text-gray-700 dark:bg-gray-500/10 dark:text-gray-400">{{ $appointment->status->value }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Invoices awaiting payment --}}
    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <h2 class="text-title-sm font-semibold text-gray-800 dark:text-white">Last 5 Invoices Awaiting Payment</h2>

        @if ($invoicesAwaitingPayment->isEmpty())
            <p class="mt-4 text-theme-sm text-gray-500 dark:text-gray-400">No invoices awaiting payment.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-200 text-theme-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th scope="col" class="px-3 py-3">Invoice</th>
                            <th scope="col" class="px-3 py-3">Patient</th>
                            <th scope="col" class="px-3 py-3">Total</th>
                            <th scope="col" class="px-3 py-3">Paid</th>
                            <th scope="col" class="px-3 py-3">Outstanding</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($invoicesAwaitingPayment as $invoice)
                            @php
                                $isUnpaid = $invoice['status'] === \App\Enums\InvoiceState::UNPAID;
                                $badgeClass = $isUnpaid
                                    ? 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'
                                    : 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400';
                            @endphp
                            <tr>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">#{{ $invoice['id'] }}</td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $invoice['patient_name'] ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ number_format($invoice['total_amount'], 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ number_format($invoice['paid_amount'], 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm font-medium text-gray-800 dark:text-white">
                                    {{ number_format($invoice['outstanding'], 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-theme-xs font-medium {{ $badgeClass }}">{{ $invoice['status']->value }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection