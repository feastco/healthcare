<?php

namespace Tests\Feature;

use App\Enums\InvoiceState;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentProcessingTest extends TestCase
{
    use RefreshDatabase;

    private bool $raceFixtureSeeded = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    private function cashierUser(): User
    {
        $role = Role::findByName('Cashier', 'web');

        return User::factory()->create()->assignRole($role);
    }

    private function nonCashierUser(): User
    {
        $role = Role::findByName('Doctor', 'web');

        return User::factory()->create()->assignRole($role);
    }

    private function invoice(int $total = 100000): Invoice
    {
        return Invoice::factory()->create([
            'total_amount' => $total,
            'status' => InvoiceState::UNPAID,
        ]);
    }

    private function pay(User $cashier, int $invoiceId, array $payload)
    {
        return $this->actingAs($cashier, 'sanctum')->postJson(
            "/api/v1/invoices/{$invoiceId}/payments",
            $payload
        );
    }

    public function test_cashier_can_create_payment(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice();

        $this->pay($cashier, $invoice->id, [
            'amount' => 40000,
            'payment_method' => 'CASH',
        ])->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'cashier_id' => $cashier->id,
            'amount' => '40000.00',
            'payment_method' => 'CASH',
        ]);
    }

    public function test_unauthenticated_request_receives_401(): void
    {
        $invoice = $this->invoice();

        $this->postJson("/api/v1/invoices/{$invoice->id}/payments", [
            'amount' => 40000,
            'payment_method' => 'CASH',
        ])->assertStatus(401);
    }

    public function test_non_cashier_receives_403(): void
    {
        $user = $this->nonCashierUser();
        $invoice = $this->invoice();

        $this->pay($user, $invoice->id, [
            'amount' => 40000,
            'payment_method' => 'CASH',
        ])->assertStatus(403);
    }

    public function test_super_admin_is_not_granted_payment_authorization(): void
    {
        $role = Role::findByName('Super Admin', 'web');
        $superAdmin = User::factory()->create()->assignRole($role);
        $invoice = $this->invoice();

        $this->pay($superAdmin, $invoice->id, [
            'amount' => 40000,
            'payment_method' => 'CASH',
        ])->assertStatus(403);
    }

    public function test_invalid_amount_is_rejected(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice();

        $this->pay($cashier, $invoice->id, [
            'amount' => 'not-a-number',
            'payment_method' => 'CASH',
        ])->assertStatus(422);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_zero_amount_is_rejected(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice();

        $this->pay($cashier, $invoice->id, [
            'amount' => 0,
            'payment_method' => 'CASH',
        ])->assertStatus(422);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_negative_amount_is_rejected(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice();

        $this->pay($cashier, $invoice->id, [
            'amount' => -100,
            'payment_method' => 'CASH',
        ])->assertStatus(422);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_invalid_payment_method_is_rejected(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice();

        $this->pay($cashier, $invoice->id, [
            'amount' => 40000,
            'payment_method' => 'QRIS',
        ])->assertStatus(422);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_missing_payment_method_is_rejected(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice();

        $this->pay($cashier, $invoice->id, [
            'amount' => 40000,
        ])->assertStatus(422);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_partial_payment_creates_payment_and_sets_partially_paid(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice(100000);

        $this->pay($cashier, $invoice->id, [
            'amount' => 40000,
            'payment_method' => 'CASH',
        ])->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => '40000.00',
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceState::PARTIALLY_PAID->value,
        ]);
    }

    public function test_exact_remaining_payment_sets_paid(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice(100000);

        $this->pay($cashier, $invoice->id, ['amount' => 40000, 'payment_method' => 'CASH'])->assertStatus(201);
        $this->pay($cashier, $invoice->id, ['amount' => 60000, 'payment_method' => 'TRANSFER'])->assertStatus(201);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceState::PAID->value,
        ]);
    }

    public function test_multiple_partial_payments_calculate_outstanding_correctly(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice(100000);

        $this->pay($cashier, $invoice->id, ['amount' => 30000, 'payment_method' => 'CASH'])->assertStatus(201);
        $this->pay($cashier, $invoice->id, ['amount' => 20000, 'payment_method' => 'CARD'])->assertStatus(201);

        $invoice->refresh();

        $this->assertSame(InvoiceState::PARTIALLY_PAID, $invoice->status);
        $this->assertSame('50000.00', (string) $invoice->payments()->sum('amount'));

        $this->pay($cashier, $invoice->id, ['amount' => 50000, 'payment_method' => 'CASH'])->assertStatus(201);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceState::PAID->value,
        ]);
    }

    public function test_overpayment_is_rejected(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice(100000);

        $this->pay($cashier, $invoice->id, ['amount' => 30000, 'payment_method' => 'CASH'])->assertStatus(201);

        $this->pay($cashier, $invoice->id, ['amount' => 70000.01, 'payment_method' => 'CASH'])
            ->assertStatus(422);
    }

    public function test_overpayment_creates_no_payment_row_and_leaves_state_unchanged(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice(100000);

        $this->pay($cashier, $invoice->id, ['amount' => 30000, 'payment_method' => 'CASH'])->assertStatus(201);

        $this->pay($cashier, $invoice->id, ['amount' => 70000.01, 'payment_method' => 'CASH'])
            ->assertStatus(422);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceState::PARTIALLY_PAID->value,
        ]);
    }

    public function test_payment_and_invoice_update_are_atomic_on_failure(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice(100000);

        DB::listen(function ($query) {
            if (str_contains($query->sql, 'insert into "payments"')) {
                throw new \RuntimeException('Simulated payment failure');
            }
        });

        $this->pay($cashier, $invoice->id, ['amount' => 40000, 'payment_method' => 'CASH'])
            ->assertStatus(500);

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceState::UNPAID->value,
        ]);
    }

    public function test_payment_on_paid_invoice_is_rejected(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice(100000);

        $this->pay($cashier, $invoice->id, ['amount' => 100000, 'payment_method' => 'CASH'])->assertStatus(201);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => InvoiceState::PAID->value]);

        $this->pay($cashier, $invoice->id, ['amount' => 1, 'payment_method' => 'CASH'])->assertStatus(422);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => InvoiceState::PAID->value]);
    }

    public function test_payment_endpoint_response_follows_api_contract(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice(100000);

        $response = $this->pay($cashier, $invoice->id, [
            'amount' => 40000,
            'payment_method' => 'CASH',
        ])->assertStatus(201);

        $response->assertJsonPath('data.amount', '40000.00');
        $response->assertJsonPath('data.invoice_id', $invoice->id);
        $response->assertJsonPath('data.cashier_id', $cashier->id);
        $response->assertJsonPath('data.payment_method', 'CASH');
    }

    public function test_get_payment_list_authorization_follows_api_contract(): void
    {
        $cashier = $this->cashierUser();
        $invoice = $this->invoice();

        $this->pay($cashier, $invoice->id, ['amount' => 40000, 'payment_method' => 'CASH'])->assertStatus(201);

        $this->actingAs($cashier, 'sanctum')
            ->getJson("/api/v1/invoices/{$invoice->id}/payments")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $doctor = $this->nonCashierUser();
        $this->actingAs($doctor, 'sanctum')
            ->getJson("/api/v1/invoices/{$invoice->id}/payments")
            ->assertStatus(403);
    }

    public function test_get_payment_list_for_missing_invoice_returns_404(): void
    {
        $cashier = $this->cashierUser();

        $this->actingAs($cashier, 'sanctum')
            ->getJson('/api/v1/invoices/999999/payments')
            ->assertStatus(404);
    }

    public function test_payment_for_missing_invoice_returns_404(): void
    {
        $cashier = $this->cashierUser();

        $this->pay($cashier, 999999, ['amount' => 40000, 'payment_method' => 'CASH'])
            ->assertStatus(404);
    }

    public function test_concurrent_payments_cannot_exceed_outstanding_amount(): void
    {
        $fixture = $this->seedRaceFixture();
        $env = $this->subprocessEnv();

        $attempts = [
            ['amount' => '60000.00', 'sleep' => 50],
            ['amount' => '50000.00', 'sleep' => 0],
        ];

        $procs = [];

        foreach ($attempts as $i => $attempt) {
            $cmd = sprintf(
                '%s artisan tests:concurrent-payment %d %s %d --sleep=%d',
                escapeshellarg(PHP_BINARY),
                $fixture['invoice_id'],
                $attempt['amount'],
                $fixture['cashier_id'],
                $attempt['sleep']
            );

            $outFile = tempnam(sys_get_temp_dir(), 'race_payment.out');

            $procs[$i] = [
                'process' => proc_open(
                    $cmd,
                    [1 => ['file', $outFile, 'a'], 2 => ['file', $outFile, 'a']],
                    $pipes,
                    base_path(),
                    $env
                ),
                'file' => $outFile,
                'amount' => $attempt['amount'],
            ];
        }

        $results = [];

        foreach ($procs as $i => $proc) {
            $exitCode = proc_close($proc['process']);
            $output = file_exists($proc['file']) ? trim((string) file_get_contents($proc['file'])) : '';
            @unlink($proc['file']);

            $results[] = [
                'amount' => $proc['amount'],
                'success' => $exitCode === 0,
                'output' => $output,
            ];
        }

        $successCount = collect($results)->filter(fn ($r) => $r['success'])->count();

        $this->assertSame(1, $successCount, 'Exactly one concurrent payment may succeed. Results: '.json_encode($results));

        $this->assertDatabaseCount('payments', 1);
        $this->assertLessThanOrEqual(
            100000,
            (int) Payment::where('invoice_id', $fixture['invoice_id'])->sum('amount'),
            'Combined successful payments must not exceed the invoice total.'
        );

        $this->assertDatabaseHas('invoices', [
            'id' => $fixture['invoice_id'],
            'status' => InvoiceState::PARTIALLY_PAID->value,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->raceFixtureSeeded) {
            $this->cleanupRaceFixture();
        }

        parent::tearDown();
    }

    private function seedRaceFixture(): array
    {
        $env = $this->subprocessEnv();

        $cmd = sprintf(
            '%s artisan tests:seed-race-fixture',
            escapeshellarg(PHP_BINARY)
        );

        $outFile = tempnam(sys_get_temp_dir(), 'race_fixture.out');

        $process = proc_open(
            $cmd,
            [1 => ['file', $outFile, 'a'], 2 => ['file', $outFile, 'a']],
            $pipes,
            base_path(),
            $env
        );

        $exitCode = proc_close($process);

        $output = file_exists($outFile) ? trim((string) file_get_contents($outFile)) : '';
        @unlink($outFile);

        $lastJson = null;

        foreach (preg_split('/\r?\n/', $output) as $line) {
            if ($line !== '' && str_starts_with($line, '{')) {
                $lastJson = $line;
            }
        }

        if ($lastJson === null) {
            $this->fail('Race fixture command produced no result (exit '.$exitCode.'). output: '.$output);
        }

        $fixture = json_decode($lastJson, true);

        if (! is_array($fixture) || ! isset($fixture['invoice_id'], $fixture['cashier_id'])) {
            $this->fail('Race fixture command returned an unexpected payload. output: '.$output);
        }

        $this->raceFixtureSeeded = true;

        return $fixture;
    }

    /**
     * Remove the rows committed by race subprocesses so later tests in the
     * same PHPUnit process are not polluted. Must run after the parent
     * transaction is no longer holding locks on these tables.
     */
    private function cleanupRaceFixture(): void
    {
        $env = $this->subprocessEnv();

        $cmd = sprintf(
            '%s artisan tests:seed-race-fixture --cleanup',
            escapeshellarg(PHP_BINARY)
        );

        $outFile = tempnam(sys_get_temp_dir(), 'race_cleanup.out');

        $process = proc_open(
            $cmd,
            [1 => ['file', $outFile, 'a'], 2 => ['file', $outFile, 'a']],
            $pipes,
            base_path(),
            $env
        );

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $output = file_exists($outFile) ? trim((string) file_get_contents($outFile)) : '';

            fwrite(STDERR, "Race fixture cleanup failed (exit {$exitCode}). output: {$output}".PHP_EOL);
        }

        if (file_exists($outFile)) {
            @unlink($outFile);
        }
    }

    private function subprocessEnv(): array
    {
        $env = getenv();

        if ($env === false || ! is_array($env)) {
            $env = [];
        }

        $pgsql = config('database.connections.pgsql');

        $env['APP_ENV'] = 'testing';
        $env['APP_KEY'] = config('app.key');
        $env['DB_CONNECTION'] = 'pgsql';
        $env['DB_HOST'] = $pgsql['host'];
        $env['DB_PORT'] = (string) $pgsql['port'];
        $env['DB_DATABASE'] = $pgsql['database'];
        $env['DB_USERNAME'] = $pgsql['username'];
        $env['DB_PASSWORD'] = $pgsql['password'];
        $env['CACHE_STORE'] = 'array';
        $env['SESSION_DRIVER'] = 'array';
        $env['QUEUE_CONNECTION'] = 'sync';

        return $env;
    }
}
