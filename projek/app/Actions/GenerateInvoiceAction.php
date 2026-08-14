<?php

namespace App\Actions;

use App\Enums\InvoiceState;
use App\Models\Appointment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class GenerateInvoiceAction
{
    public function handle(Appointment $appointment): Invoice
    {
        return DB::transaction(function () use ($appointment) {
            $amount = (string) config('billing.default_invoice_amount');

            $invoice = Invoice::create([
                'appointment_id' => $appointment->id,
                'total_amount' => 0,
                'status' => InvoiceState::UNPAID,
            ]);

            $invoice->items()->create([
                'description' => 'Billing administratif untuk kunjungan (appointment).',
                'amount' => $amount,
            ]);

            $invoice->update([
                'total_amount' => (string) $invoice->items()->sum('amount'),
            ]);

            $invoice->refresh();

            return $invoice;
        });
    }
}
