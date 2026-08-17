<?php

namespace Tests\Feature;

use App\Enums\InvoiceState;
use App\Enums\PaymentMethod;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function superAdmin(): User
    {
        return User::where('email', 'superadmin@example.com')->firstOrFail();
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create()->assignRole($role);
    }

    private function cashierUser(): User
    {
        return $this->userWithRole('Cashier');
    }

    private function createInvoice(array $attributes = []): Invoice
    {
        return Invoice::factory()->create($attributes);
    }

    public function test_guest_is_redirected_to_login_when_accessing_invoices(): void
    {
        $this->get('/operations/invoices')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_viewing_invoice(): void
    {
        $this->get('/operations/invoices/1')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_recording_payment(): void
    {
        $this->post('/operations/invoices/1/payments', [
            '_token' => csrf_token(),
            'amount' => '50000',
            'payment_method' => PaymentMethod::CASH->value,
        ])->assertRedirect(route('login'));
    }

    public function test_registration_staff_cannot_access_invoices(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/operations/invoices')
            ->assertStatus(403);
    }

    public function test_doctor_cannot_access_invoices(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->get('/operations/invoices')
            ->assertStatus(403);
    }

    public function test_it_admin_cannot_access_invoices(): void
    {
        $this->actingAs($this->userWithRole('IT/Admin'))
            ->get('/operations/invoices')
            ->assertStatus(403);
    }

    public function test_user_without_role_cannot_access_invoices(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/operations/invoices')
            ->assertStatus(403);
    }

    public function test_cashier_can_access_invoices_index(): void
    {
        $this->withoutVite();

        $this->createInvoice();

        $response = $this->actingAs($this->cashierUser())->get('/operations/invoices');

        $response->assertStatus(200);
        $response->assertSee('Invoices');
    }

    public function test_super_admin_can_access_invoices_index(): void
    {
        $this->withoutVite();

        $this->createInvoice();

        $response = $this->actingAs($this->superAdmin())->get('/operations/invoices');

        $response->assertStatus(200);
        $response->assertSee('Invoices');
    }

    public function test_invoice_index_shows_patient_and_status(): void
    {
        $this->withoutVite();

        $invoice = $this->createInvoice();

        $response = $this->actingAs($this->cashierUser())->get('/operations/invoices');

        $response->assertStatus(200);
        $response->assertSee($invoice->appointment->patient->name);
        $response->assertSee('Unpaid');
        $response->assertSee("#{$invoice->id}");
    }

    public function test_cashier_can_view_invoice_detail(): void
    {
        $this->withoutVite();

        $invoice = $this->createInvoice();

        $response = $this->actingAs($this->cashierUser())->get("/operations/invoices/{$invoice->id}");

        $response->assertStatus(200);
        $response->assertSee("#{$invoice->id}");
        $response->assertSee($invoice->appointment->patient->name);
        $response->assertSee('Unpaid');
    }

    public function test_super_admin_can_view_invoice_detail(): void
    {
        $this->withoutVite();

        $invoice = $this->createInvoice();

        $response = $this->actingAs($this->superAdmin())->get("/operations/invoices/{$invoice->id}");

        $response->assertStatus(200);
        $response->assertSee("#{$invoice->id}");
        $response->assertSee($invoice->appointment->patient->name);
    }

    public function test_invoice_detail_shows_total_and_outstanding(): void
    {
        $this->withoutVite();

        $invoice = $this->createInvoice(['total_amount' => 200000.00]);

        $response = $this->actingAs($this->cashierUser())->get("/operations/invoices/{$invoice->id}");

        $response->assertStatus(200);
        $response->assertSee('200,000.00');
        $response->assertSee('200,000.00');
    }

    public function test_cashier_sees_record_payment_form(): void
    {
        $this->withoutVite();

        $invoice = $this->createInvoice();

        $response = $this->actingAs($this->cashierUser())->get("/operations/invoices/{$invoice->id}");

        $response->assertStatus(200);
        $response->assertSee("/operations/invoices/{$invoice->id}/payments", false);
        $response->assertSee('Payment Method', false);
    }

    public function test_super_admin_does_not_see_record_payment_form(): void
    {
        $this->withoutVite();

        $invoice = $this->createInvoice();

        $response = $this->actingAs($this->superAdmin())->get("/operations/invoices/{$invoice->id}");

        $response->assertStatus(200);
        $response->assertDontSee("/operations/invoices/{$invoice->id}/payments", false);
    }

    public function test_cashier_can_record_full_payment(): void
    {
        $invoice = $this->createInvoice(['total_amount' => 100000.00]);

        $response = $this->from("/operations/invoices/{$invoice->id}")
            ->actingAs($this->cashierUser())
            ->post("/operations/invoices/{$invoice->id}/payments", [
                '_token' => csrf_token(),
                'amount' => '100000',
                'payment_method' => PaymentMethod::CASH->value,
            ]);

        $response->assertRedirect("/operations/invoices/{$invoice->id}");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceState::PAID->value,
        ]);

        $payment = Payment::where('invoice_id', $invoice->id)->firstOrFail();

        $this->assertSame('100000.00', (string) $payment->amount);
        $this->assertSame(PaymentMethod::CASH, $payment->payment_method);

        $this->assertTrue(
            AuditLog::query()
                ->where('entity_id', $payment->id)
                ->where('action', 'CREATE')
                ->exists()
        );
    }

    public function test_cashier_can_record_partial_payment(): void
    {
        $invoice = $this->createInvoice(['total_amount' => 100000.00]);

        $this->from("/operations/invoices/{$invoice->id}")
            ->actingAs($this->cashierUser())
            ->post("/operations/invoices/{$invoice->id}/payments", [
                '_token' => csrf_token(),
                'amount' => '40000',
                'payment_method' => PaymentMethod::TRANSFER->value,
            ])
            ->assertRedirect("/operations/invoices/{$invoice->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceState::PARTIALLY_PAID->value,
        ]);

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_cashier_can_record_second_payment_until_paid(): void
    {
        $invoice = $this->createInvoice(['total_amount' => 100000.00]);

        $cashier = $this->cashierUser();

        $this->from("/operations/invoices/{$invoice->id}")
            ->actingAs($cashier)
            ->post("/operations/invoices/{$invoice->id}/payments", [
                '_token' => csrf_token(),
                'amount' => '40000',
                'payment_method' => PaymentMethod::CASH->value,
            ])
            ->assertSessionHas('success');

        $this->from("/operations/invoices/{$invoice->id}")
            ->actingAs($cashier)
            ->post("/operations/invoices/{$invoice->id}/payments", [
                '_token' => csrf_token(),
                'amount' => '60000',
                'payment_method' => PaymentMethod::CASH->value,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceState::PAID->value,
        ]);

        $this->assertSame(2, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_overpayment_is_rejected(): void
    {
        $invoice = $this->createInvoice(['total_amount' => 100000.00]);

        $response = $this->from("/operations/invoices/{$invoice->id}")
            ->actingAs($this->cashierUser())
            ->post("/operations/invoices/{$invoice->id}/payments", [
                '_token' => csrf_token(),
                'amount' => '150000',
                'payment_method' => PaymentMethod::CASH->value,
            ]);

        $response->assertRedirect("/operations/invoices/{$invoice->id}");
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceState::UNPAID->value,
        ]);

        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_payment_on_paid_invoice_is_rejected(): void
    {
        $invoice = $this->createInvoice(['total_amount' => 100000.00, 'status' => InvoiceState::PAID]);

        $response = $this->from("/operations/invoices/{$invoice->id}")
            ->actingAs($this->cashierUser())
            ->post("/operations/invoices/{$invoice->id}/payments", [
                '_token' => csrf_token(),
                'amount' => '50000',
                'payment_method' => PaymentMethod::CASH->value,
            ]);

        $response->assertRedirect("/operations/invoices/{$invoice->id}");
        $response->assertSessionHas('error');

        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_super_admin_cannot_record_payment(): void
    {
        $invoice = $this->createInvoice(['total_amount' => 100000.00]);

        $this->actingAs($this->superAdmin())
            ->post("/operations/invoices/{$invoice->id}/payments", [
                '_token' => csrf_token(),
                'amount' => '100000',
                'payment_method' => PaymentMethod::CASH->value,
            ])
            ->assertStatus(403);

        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_invalid_amount_is_rejected(): void
    {
        $invoice = $this->createInvoice();

        $response = $this->from("/operations/invoices/{$invoice->id}")
            ->actingAs($this->cashierUser())
            ->post("/operations/invoices/{$invoice->id}/payments", [
                '_token' => csrf_token(),
                'amount' => '0',
                'payment_method' => PaymentMethod::CASH->value,
            ]);

        $response->assertRedirect("/operations/invoices/{$invoice->id}");
        $response->assertSessionHasErrors('amount');

        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_invalid_payment_method_is_rejected(): void
    {
        $invoice = $this->createInvoice();

        $response = $this->from("/operations/invoices/{$invoice->id}")
            ->actingAs($this->cashierUser())
            ->post("/operations/invoices/{$invoice->id}/payments", [
                '_token' => csrf_token(),
                'amount' => '50000',
                'payment_method' => 'CRYPTO',
            ]);

        $response->assertRedirect("/operations/invoices/{$invoice->id}");
        $response->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_payment_on_missing_invoice_returns_404(): void
    {
        $this->actingAs($this->cashierUser())
            ->post('/operations/invoices/999999/payments', [
                '_token' => csrf_token(),
                'amount' => '50000',
                'payment_method' => PaymentMethod::CASH->value,
            ])
            ->assertStatus(404);
    }

    public function test_invoice_detail_on_missing_invoice_returns_404(): void
    {
        $this->actingAs($this->cashierUser())
            ->get('/operations/invoices/999999')
            ->assertStatus(404);
    }

    public function test_no_get_route_for_recording_payment(): void
    {
        $invoice = $this->createInvoice();

        $this->actingAs($this->cashierUser())
            ->get("/operations/invoices/{$invoice->id}/payments")
            ->assertStatus(405);
    }

    public function test_invoice_pages_do_not_expose_sensitive_material(): void
    {
        $this->withoutVite();

        $cashier = $this->cashierUser();
        $invoice = $this->createInvoice();

        $html = $this->actingAs($cashier)->get("/operations/invoices/{$invoice->id}")->getContent();

        $this->assertStringNotContainsString($cashier->password, $html);
        $this->assertStringNotContainsString('plain-text-token', $html);
    }

    public function test_unauthorized_role_has_no_invoices_nav_href(): void
    {
        $this->withoutVite();

        $html = $this->actingAs($this->userWithRole('Doctor'))
            ->get('/home')
            ->getContent();

        $this->assertStringNotContainsString('/operations/invoices', $html);
    }
}
