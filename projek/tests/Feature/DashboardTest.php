<?php

namespace Tests\Feature;

use App\Enums\InvoiceState;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->superAdmin())->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Total Patients');
        $response->assertSee('Total Revenue (Simulated)');
    }

    public function test_it_admin_can_access_dashboard(): void
    {
        $this->withoutVite();

        $this->actingAs($this->userWithRole('IT/Admin'))
            ->get('/dashboard')
            ->assertStatus(200);
    }

    public function test_registration_staff_cannot_access_dashboard(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/dashboard')
            ->assertStatus(403);
    }

    public function test_doctor_cannot_access_dashboard(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->get('/dashboard')
            ->assertStatus(403);
    }

    public function test_cashier_cannot_access_dashboard(): void
    {
        $this->actingAs($this->userWithRole('Cashier'))
            ->get('/dashboard')
            ->assertStatus(403);
    }

    public function test_user_without_role_cannot_access_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertStatus(403);
    }

    public function test_dashboard_renders_real_patient_and_revenue_data(): void
    {
        $this->withoutVite();

        $patients = Patient::factory()->count(3)->create();
        $appointmentA = Appointment::factory()->create(['patient_id' => $patients[0]->id]);
        $appointmentB = Appointment::factory()->create(['patient_id' => $patients[1]->id]);
        $invoiceA = Invoice::factory()->create([
            'appointment_id' => $appointmentA->id,
            'total_amount' => 100000.00,
            'status' => InvoiceState::PAID,
        ]);
        $invoiceB = Invoice::factory()->create([
            'appointment_id' => $appointmentB->id,
            'total_amount' => 150000.00,
            'status' => InvoiceState::PAID,
        ]);
        Payment::factory()->create(['invoice_id' => $invoiceA->id, 'amount' => 100000.00]);
        Payment::factory()->create(['invoice_id' => $invoiceB->id, 'amount' => 150000.00]);

        $response = $this->actingAs($this->superAdmin())->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('totalPatients', 3);
        $response->assertViewHas('totalRevenue', 250000.0);
        $response->assertSee('250.000,00', false);
    }

    public function test_dashboard_shows_empty_states_when_no_operational_data(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->superAdmin())->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('totalPatients', 0);
        $response->assertSee('No appointments today.');
        $response->assertSee('No invoices awaiting payment.');
    }

    public function test_dashboard_renders_todays_queue_from_real_data(): void
    {
        $this->withoutVite();

        $patient = Patient::factory()->create(['name' => 'Queue Test Patient']);
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
        ]);

        $response = $this->actingAs($this->superAdmin())->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('todayAppointments', fn ($appointments) => $appointments->count() === 1);
        $response->assertSee('Queue Test Patient');
    }

    public function test_dashboard_renders_invoices_awaiting_payment_from_real_data(): void
    {
        $this->withoutVite();

        $patient = Patient::factory()->create(['name' => 'Invoice Test Patient']);
        $appointmentUnpaid = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'starts_at' => now()->addDays(5)->addHour(),
            'ends_at' => now()->addDays(5)->addHours(2),
        ]);
        $appointmentPartial = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'starts_at' => now()->addDays(6)->addHour(),
            'ends_at' => now()->addDays(6)->addHours(2),
        ]);

        $unpaid = Invoice::factory()->create([
            'appointment_id' => $appointmentUnpaid->id,
            'total_amount' => 150000.00,
            'status' => InvoiceState::UNPAID,
        ]);

        $partial = Invoice::factory()->create([
            'appointment_id' => $appointmentPartial->id,
            'total_amount' => 200000.00,
            'status' => InvoiceState::PARTIALLY_PAID,
        ]);
        Payment::factory()->create([
            'invoice_id' => $partial->id,
            'amount' => 50000.00,
        ]);

        $response = $this->actingAs($this->superAdmin())->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas(
            'invoicesAwaitingPayment',
            fn ($invoices) => $invoices->count() === 2
        );
        $response->assertSee('Invoice Test Patient');
        $response->assertSee('UNPAID');
        $response->assertSee('PARTIALLY_PAID');
        $response->assertSee('150.000,00', false);
    }

    public function test_it_admin_dashboard_has_no_mutation_controls(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->userWithRole('IT/Admin'))->get('/dashboard');
        $html = $response->getContent();

        $response->assertStatus(200);
        $this->assertSame(1, substr_count($html, '<form'));
        $this->assertStringContainsString('/logout', $html);
        $this->assertStringNotContainsString('name="action"', $html);
        $this->assertStringNotContainsString('Create', $html);
        $this->assertStringNotContainsString('Edit', $html);
        $this->assertStringNotContainsString('Delete', $html);
    }

    public function test_dashboard_does_not_expose_sensitive_material(): void
    {
        $this->withoutVite();
        $user = $this->superAdmin();

        $html = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringNotContainsString($user->password, $html);
        $this->assertStringNotContainsString('plain-text-token', $html);
    }
}
