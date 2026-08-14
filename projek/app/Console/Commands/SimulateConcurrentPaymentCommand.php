<?php

namespace App\Console\Commands;

use App\Actions\ProcessPaymentAction;
use App\Enums\PaymentMethod;
use App\Models\User;
use Illuminate\Console\Command;

class SimulateConcurrentPaymentCommand extends Command
{
    protected $signature = 'tests:concurrent-payment {invoiceId} {amount} {cashierId} {--sleep=0}';

    protected $description = 'Process a single payment (used to prove concurrent payment integrity against the test database).';

    public function handle(ProcessPaymentAction $processPayment): int
    {
        $sleep = (int) $this->option('sleep');

        if ($sleep > 0) {
            usleep($sleep * 1000);
        }

        $invoiceId = (int) $this->argument('invoiceId');
        $amount = $this->argument('amount');
        $cashier = User::findOrFail((int) $this->argument('cashierId'));

        try {
            $processPayment->handle(
                invoiceId: $invoiceId,
                cashier: $cashier,
                amount: $amount,
                method: PaymentMethod::CASH,
            );

            $this->line('OK');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
