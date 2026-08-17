<?php

namespace Tests\Feature;

use App\Actions\GenerateInvoiceAction;
use App\Enums\AppointmentStatus;
use App\Enums\InvoiceState;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceGenerationTest extends TestCase
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

    private function doctorUser(): User
    {
        $role = Role::findByName('Doctor', 'web');

        return User::factory()->create()->assignRole($role);
    }

    private function createAppointment(AppointmentStatus $status, ?User $owner = null): Appointment
    {
        $doctor = $owner !== null
            ? Doctor::factory()->create(['user_id' => $owner->id])
            : Doctor::factory();

        return Appointment::factory()->create([
            'patient_id' => Patient::factory(),
            'doctor_id' => $doctor,
            'status' => $status,
        ]);
    }

    private function complete(User $user, int $id)
    {
        return $this->actingAs($user, 'sanctum')->patchJson(
            "/api/v1/appointments/{$id}/status",
            ['status' => 'COMPLETED']
        );
    }

    public function test_completed_appointment_generates_an_invoice(): void
    {
        config(['billing.default_invoice_amount' => 100000.00]);

        $user = $this->doctorUser();
        $appointment = $this->createAppointment(AppointmentStatus::IN_PROGRESS, $user);

        $this->complete($user, $appointment->id)->assertStatus(200);

        $this->assertDatabaseHas('invoices', [
            'appointment_id' => $appointment->id,
            'total_amount' => '100000.00',
            'status' => InvoiceState::UNPAID->value,
        ]);
    }

    public function test_generated_invoice_status_is_unpaid(): void
    {
        config(['billing.default_invoice_amount' => 100000.00]);

        $user = $this->doctorUser();
        $appointment = $this->createAppointment(AppointmentStatus::IN_PROGRESS, $user);

        $this->complete($user, $appointment->id)->assertStatus(200);

        $invoice = Invoice::where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertSame(InvoiceState::UNPAID, $invoice->status);
    }

    public function test_exactly_one_invoice_item_is_created(): void
    {
        config(['billing.default_invoice_amount' => 100000.00]);

        $user = $this->doctorUser();
        $appointment = $this->createAppointment(AppointmentStatus::IN_PROGRESS, $user);

        $this->complete($user, $appointment->id)->assertStatus(200);

        $invoice = Invoice::where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertCount(1, $invoice->items);
    }

    public function test_invoice_item_amount_equals_configured_billing_amount(): void
    {
        config(['billing.default_invoice_amount' => 50000.00]);

        $user = $this->doctorUser();
        $appointment = $this->createAppointment(AppointmentStatus::IN_PROGRESS, $user);

        $this->complete($user, $appointment->id)->assertStatus(200);

        $invoice = Invoice::where('appointment_id', $appointment->id)->firstOrFail();
        $item = $invoice->items->first();

        $this->assertSame('50000.00', $item->amount);
    }

    public function test_invoice_total_equals_sum_of_invoice_items(): void
    {
        config(['billing.default_invoice_amount' => 100000.00]);

        $user = $this->doctorUser();
        $appointment = $this->createAppointment(AppointmentStatus::IN_PROGRESS, $user);

        $this->complete($user, $appointment->id)->assertStatus(200);

        $invoice = Invoice::where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertSame(
            (string) $invoice->items()->sum('amount'),
            $invoice->total_amount
        );
    }

    public function test_non_completed_appointment_does_not_generate_invoice(): void
    {
        config(['billing.default_invoice_amount' => 100000.00]);

        $role = Role::findByName('Registration Staff', 'web');
        $user = User::factory()->create()->assignRole($role);
        $appointment = $this->createAppointment(AppointmentStatus::SCHEDULED);

        $this->actingAs($user, 'sanctum')->patchJson(
            "/api/v1/appointments/{$appointment->id}/status",
            ['status' => 'CONFIRMED']
        )->assertStatus(200);

        $this->assertDatabaseMissing('invoices', ['appointment_id' => $appointment->id]);
    }

    public function test_generation_is_configuration_driven_not_hardcoded(): void
    {
        config(['billing.default_invoice_amount' => 250000.00]);

        $user = $this->doctorUser();
        $appointment = $this->createAppointment(AppointmentStatus::IN_PROGRESS, $user);

        $this->complete($user, $appointment->id)->assertStatus(200);

        $invoice = Invoice::where('appointment_id', $appointment->id)->firstOrFail();
        $item = $invoice->items->first();

        $this->assertSame('250000.00', $item->amount);
        $this->assertSame('250000.00', $invoice->total_amount);
    }

    public function test_existing_invoice_cannot_produce_a_duplicate_invoice(): void
    {
        config(['billing.default_invoice_amount' => 100000.00]);

        $appointment = $this->createAppointment(AppointmentStatus::COMPLETED);

        app(GenerateInvoiceAction::class)->handle($appointment);

        $this->expectException(QueryException::class);

        app(GenerateInvoiceAction::class)->handle($appointment);
    }

    public function test_transaction_rolls_back_when_invoice_generation_fails(): void
    {
        config(['billing.default_invoice_amount' => 100000.00]);

        $user = $this->doctorUser();
        $appointment = $this->createAppointment(AppointmentStatus::IN_PROGRESS, $user);

        DB::listen(function ($query) {
            if (str_contains($query->sql, 'insert into "invoices"')) {
                throw new \RuntimeException('Simulated invoice failure');
            }
        });

        $this->complete($user, $appointment->id)->assertStatus(500);

        $this->assertDatabaseMissing('invoices', ['appointment_id' => $appointment->id]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::IN_PROGRESS->value,
        ]);
    }
}
