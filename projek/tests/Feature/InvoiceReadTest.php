<?php

namespace Tests\Feature;

use App\Enums\InvoiceState;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceReadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::findByName($roleName, 'web');

        return User::factory()->create()->assignRole($role);
    }

    public function test_cashier_can_list_invoices(): void
    {
        Invoice::factory()->count(3)->create();

        $response = $this->actingAs($this->userWithRole('Cashier'), 'sanctum')
            ->getJson('/api/v1/invoices');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonCount(3, 'data');
    }

    public function test_super_admin_can_list_invoices(): void
    {
        Invoice::factory()->count(2)->create();

        $response = $this->actingAs($this->userWithRole('Super Admin'), 'sanctum')
            ->getJson('/api/v1/invoices');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_cashier_can_view_single_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => '100000.00',
            'status' => InvoiceState::UNPAID,
        ]);

        $response = $this->actingAs($this->userWithRole('Cashier'), 'sanctum')
            ->getJson("/api/v1/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $invoice->id,
                    'appointment_id' => $invoice->appointment_id,
                    'total_amount' => '100000.00',
                    'status' => 'UNPAID',
                ],
            ]);
    }

    public function test_non_invoice_role_receives_403(): void
    {
        Invoice::factory()->create();

        $response = $this->actingAs($this->userWithRole('Doctor'), 'sanctum')
            ->getJson('/api/v1/invoices');

        $response->assertForbidden();
    }

    public function test_unauthenticated_request_receives_401(): void
    {
        $response = $this->getJson('/api/v1/invoices');

        $response->assertUnauthorized();
    }

    public function test_missing_invoice_receives_404(): void
    {
        $response = $this->actingAs($this->userWithRole('Cashier'), 'sanctum')
            ->getJson('/api/v1/invoices/999999');

        $response->assertNotFound();
    }

    public function test_read_only_enforced_no_create_route(): void
    {
        $response = $this->actingAs($this->userWithRole('Cashier'), 'sanctum')
            ->postJson('/api/v1/invoices');

        $response->assertStatus(405);
    }
}
