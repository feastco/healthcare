@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Invoices" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
            Manage patient invoices and payments.
        </p>
    </div>

    @if (session('success'))
        <div
            class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-theme-sm text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div
            class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-theme-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @if ($invoices->isEmpty())
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">No invoices yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-200 text-theme-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th scope="col" class="px-3 py-3">Invoice</th>
                            <th scope="col" class="px-3 py-3">Patient</th>
                            <th scope="col" class="px-3 py-3">Total</th>
                            <th scope="col" class="px-3 py-3">Paid</th>
                            <th scope="col" class="px-3 py-3">Outstanding</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                            <th scope="col" class="px-3 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($invoices as $invoice)
                            @php
                                $paid = (string) $invoice->payments->sum('amount');
                                $outstanding = bcsub((string) $invoice->total_amount, $paid, 2);
                            @endphp
                            <tr>
                                <td class="px-3 py-3 text-theme-sm font-medium text-gray-800 dark:text-white">
                                    #{{ $invoice->id }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm font-medium text-gray-800 dark:text-white">
                                    {{ $invoice->appointment?->patient?->name }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ number_format((float) $invoice->total_amount, 2) }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ number_format((float) $paid, 2) }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ number_format((float) $outstanding, 2) }}
                                </td>
                                <td class="px-3 py-3">
                                    <x-common.status-badge :status="$invoice->status" />
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        @can('view', $invoice)
                                            <a href="{{ route('invoices.show', $invoice) }}"
                                                class="text-theme-sm font-medium text-brand-500 hover:text-brand-600">View</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
@endsection