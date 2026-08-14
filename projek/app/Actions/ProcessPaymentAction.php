<?php

namespace App\Actions;

use App\Enums\InvoiceState;
use App\Enums\PaymentMethod;
use App\Exceptions\OverpaymentException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class ProcessPaymentAction
{
    public function handle(int $invoiceId, User $cashier, string $amount, PaymentMethod $method): Payment
    {
        return DB::transaction(function () use ($invoiceId, $cashier, $amount, $method) {
            $invoice = Invoice::whereKey($invoiceId)->lockForUpdate()->firstOrFail();

            if ($invoice->status === InvoiceState::PAID) {
                throw new OverpaymentException(
                    'Invoice has already been fully paid.'
                );
            }

            $outstanding = $this->outstandingAmount($invoice);

            if (bccomp($amount, $outstanding, 2) > 0) {
                throw new OverpaymentException(
                    sprintf(
                        'Payment amount (%s) exceeds the outstanding amount (%s).',
                        $amount,
                        $outstanding
                    )
                );
            }

            $payment = $invoice->payments()->create([
                'cashier_id' => $cashier->id,
                'amount' => $amount,
                'paid_at' => now(),
                'payment_method' => $method,
            ]);

            $newState = $this->resultingState($invoice, $payment);

            $invoice->update(['status' => $newState]);

            app(AuditService::class)->created($payment, actor: $cashier);

            return $payment;
        });
    }

    public function outstandingAmount(Invoice $invoice): string
    {
        $paid = $invoice->payments()->sum('amount');

        return bcsub((string) $invoice->total_amount, (string) $paid, 2);
    }

    private function resultingState(Invoice $invoice, Payment $payment): InvoiceState
    {
        $remaining = bcsub(
            (string) $invoice->total_amount,
            (string) $invoice->payments()->sum('amount'),
            2
        );

        return bccomp($remaining, '0', 2) === 0
            ? InvoiceState::PAID
            : InvoiceState::PARTIALLY_PAID;
    }
}
