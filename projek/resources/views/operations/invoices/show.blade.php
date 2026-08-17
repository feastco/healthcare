@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Invoice Detail" />

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

    @php
        $paid = (string) $invoice->payments->sum('amount');
        $outstanding = bcsub((string) $invoice->total_amount, $paid, 2);
    @endphp

    <div
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Invoice</dt>
                <dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white">#{{ $invoice->id }}</dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="mt-1">
                    <x-common.status-badge :status="$invoice->status" />
                </dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Patient</dt>
                <dd class="mt-1 text-theme-sm text-gray-800 dark:text-white">{{ $invoice->appointment?->patient?->name }}</dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Appointment</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">
                    {{ $invoice->appointment?->starts_at?->format('d M Y H:i') }}
                </dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total Amount</dt>
                <dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white">
                    {{ number_format((float) $invoice->total_amount, 2) }}
                </dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Paid Amount</dt>
                <dd class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">
                    {{ number_format((float) $paid, 2) }}
                </dd>
            </div>
            <div>
                <dt class="text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">Outstanding Amount</dt>
                <dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white">
                    {{ number_format((float) $outstanding, 2) }}
                </dd>
            </div>
        </dl>

        <div class="mt-6">
            <h2 class="text-theme-base font-semibold text-gray-800 dark:text-white">Invoice Items</h2>
            @if ($invoice->items->isEmpty())
                <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">No items on this invoice.</p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="border-b border-gray-200 text-theme-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                <th scope="col" class="px-3 py-3">Description</th>
                                <th scope="col" class="px-3 py-3">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">{{ $item->description }}</td>
                                    <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                        {{ number_format((float) $item->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div x-data="{ open: false, amount: '', method: 'CASH', outstanding: {{ $outstanding }} }"
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-theme-base font-semibold text-gray-800 dark:text-white">Payment History</h2>
            @can('create', \App\Models\Payment::class)
                <button type="button" @click="open = true"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">
                    Record Payment
                </button>
            @endcan
        </div>

        @if ($invoice->payments->isEmpty())
            <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">No payments recorded yet.</p>
        @else
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-200 text-theme-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th scope="col" class="px-3 py-3">Amount</th>
                            <th scope="col" class="px-3 py-3">Method</th>
                            <th scope="col" class="px-3 py-3">Cashier</th>
                            <th scope="col" class="px-3 py-3">Paid At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($invoice->payments as $payment)
                            <tr>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ number_format((float) $payment->amount, 2) }}
                                </td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">{{ $payment->payment_method->value }}</td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">{{ $payment->cashier?->name }}</td>
                                <td class="px-3 py-3 text-theme-sm text-gray-700 dark:text-gray-300">
                                    {{ $payment->paid_at?->format('d M Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @can('create', \App\Models\Payment::class)
            <div x-show="open" x-cloak x-transition
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" @keydown.escape.window="open = false">
                <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <h2 class="text-theme-base font-semibold text-gray-800 dark:text-white">Record Payment</h2>
                        <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">x</button>
                    </div>

                    <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="payment-amount" class="block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                            <input type="number" id="payment-amount" name="amount" step="0.01" min="0.01" required x-model="amount"
                                :class="amount !== '' && parseFloat(amount) > outstanding ? 'border-red-500 focus:border-red-500' : ''"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <p x-show="amount !== '' && parseFloat(amount) > outstanding" x-cloak
                                class="mt-1 text-theme-xs text-red-600">
                                Amount exceeds the outstanding balance of {{ number_format((float) $outstanding, 2) }}.
                            </p>
                            @error('amount')
                                <p class="mt-1 text-theme-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payment-method" class="block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
                            <select id="payment-method" name="payment_method" required x-model="method"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                @foreach (\App\Enums\PaymentMethod::cases() as $paymentMethod)
                                    <option value="{{ $paymentMethod->value }}">{{ $paymentMethod->value }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <p class="mt-1 text-theme-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                            :disabled="amount === '' || parseFloat(amount) <= 0 || parseFloat(amount) > outstanding"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60">
                            Bayar
                        </button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
@endsection