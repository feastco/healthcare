<?php

namespace App\Console\Commands;

use App\Enums\InvoiceState;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedRaceFixtureCommand extends Command
{
    protected $signature = 'tests:seed-race-fixture {--cleanup : Remove committed race fixtures left by a previous run.}';

    protected $description = 'Create a committed invoice and cashier used to prove concurrent payment integrity (test database).';

    public function handle(): int
    {
        app('config')->set('cache.default', 'array');

        $this->resetRaceData();

        if ($this->option('cleanup')) {
            return self::SUCCESS;
        }

        $cashier = User::factory()->create();

        $invoice = Invoice::factory()->create([
            'total_amount' => '100000.00',
            'status' => InvoiceState::UNPAID,
        ]);

        $invoice->items()->create([
            'description' => 'Billing administratif untuk kunjungan (appointment).',
            'amount' => '100000.00',
        ]);

        $this->line(json_encode([
            'invoice_id' => $invoice->id,
            'cashier_id' => $cashier->id,
        ]));

        return self::SUCCESS;
    }

    /**
     * Remove committed rows created by race subprocesses (they are not
     * covered by RefreshDatabase, which only rolls back the parent process
     * transaction). Deletion order is FK-safe.
     */
    private function resetRaceData(): void
    {
        foreach ([
            'payments',
            'invoice_items',
            'invoices',
            'appointments',
            'doctor_schedules',
            'doctors',
            'patients',
            'departments',
            'sessions',
            'audit_logs',
            'users',
        ] as $table) {
            DB::table($table)->delete();
        }
    }
}
