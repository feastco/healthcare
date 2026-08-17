<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceState;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditIntegrityTest extends TestCase
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

    public function test_patient_creation_records_a_create_audit_entry(): void
    {
        $staff = $this->userWithRole('Registration Staff');

        $this->actingAs($staff, 'sanctum')->postJson('/api/v1/patients', [
            'name' => 'Budi Santoso',
            'dob' => '1990-05-15',
            'gender' => 'MALE',
        ])->assertStatus(201);

        $patient = Patient::firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $staff->id,
            'action' => AuditService::ACTION_CREATE,
            'entity_type' => $patient->getMorphClass(),
            'entity_id' => $patient->id,
        ]);

        $log = AuditLog::where('entity_type', $patient->getMorphClass())
            ->where('entity_id', $patient->id)
            ->firstOrFail();

        $this->assertSame('Budi Santoso', $log->after_state['name']);
        $this->assertSame([], $log->before_state);
    }

    public function test_patient_update_records_before_and_after_state(): void
    {
        $staff = $this->userWithRole('Registration Staff');
        $patient = Patient::factory()->create(['name' => 'Old Name']);

        $this->actingAs($staff, 'sanctum')->putJson("/api/v1/patients/{$patient->id}", [
            'name' => 'New Name',
            'dob' => $patient->dob,
            'gender' => $patient->gender,
        ])->assertStatus(200);

        $log = AuditLog::where('entity_type', $patient->getMorphClass())
            ->where('entity_id', $patient->id)
            ->where('action', AuditService::ACTION_UPDATE)
            ->firstOrFail();

        $this->assertSame($staff->id, $log->user_id);
        $this->assertSame('Old Name', $log->before_state['name']);
        $this->assertSame('New Name', $log->after_state['name']);
    }

    public function test_doctor_creation_records_a_create_audit_entry(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $departmentId = Department::factory()->create()->id;
        $user = User::factory()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/doctors', [
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'name' => 'Dr. Andi',
        ])->assertStatus(201);

        $doctor = Doctor::firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditService::ACTION_CREATE,
            'entity_type' => $doctor->getMorphClass(),
            'entity_id' => $doctor->id,
        ]);
    }

    public function test_doctor_deletion_records_a_delete_audit_entry(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $doctor = Doctor::factory()->create(['name' => 'Dr. Andi']);

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/doctors/{$doctor->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditService::ACTION_DELETE,
            'entity_type' => $doctor->getMorphClass(),
            'entity_id' => $doctor->id,
        ]);

        $log = AuditLog::where('entity_type', $doctor->getMorphClass())
            ->where('entity_id', $doctor->id)
            ->where('action', AuditService::ACTION_DELETE)
            ->firstOrFail();

        $this->assertSame('Dr. Andi', $log->before_state['name']);
        $this->assertSame([], $log->after_state);
    }

    public function test_payment_creation_records_audit_with_cashier_as_actor(): void
    {
        $cashier = $this->userWithRole('Cashier');
        $invoice = Invoice::factory()->create([
            'total_amount' => 100000,
            'status' => InvoiceState::UNPAID,
        ]);

        $this->actingAs($cashier, 'sanctum')->postJson(
            "/api/v1/invoices/{$invoice->id}/payments",
            ['amount' => 40000, 'payment_method' => 'CASH']
        )->assertStatus(201);

        $payment = $invoice->payments()->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $cashier->id,
            'action' => AuditService::ACTION_CREATE,
            'entity_type' => $payment->getMorphClass(),
            'entity_id' => $payment->id,
        ]);
    }

    public function test_invoice_generation_records_audit_entry(): void
    {
        config(['billing.default_invoice_amount' => 100000.00]);

        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create([
            'doctor_id' => Doctor::factory()->create(['user_id' => $doctor->id]),
            'status' => AppointmentStatus::IN_PROGRESS,
        ]);

        $this->actingAs($doctor, 'sanctum')->patchJson(
            "/api/v1/appointments/{$appointment->id}/status",
            ['status' => 'COMPLETED']
        )->assertStatus(200);

        $invoice = Invoice::where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditService::ACTION_CREATE,
            'entity_type' => $invoice->getMorphClass(),
            'entity_id' => $invoice->id,
        ]);
    }

    public function test_appointment_status_update_records_before_and_after_state(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create([
            'doctor_id' => Doctor::factory()->create(['user_id' => $doctor->id]),
            'status' => AppointmentStatus::IN_PROGRESS,
        ]);

        $this->actingAs($doctor, 'sanctum')->patchJson(
            "/api/v1/appointments/{$appointment->id}/status",
            ['status' => 'COMPLETED']
        )->assertStatus(200);

        $log = AuditLog::where('entity_type', $appointment->getMorphClass())
            ->where('entity_id', $appointment->id)
            ->where('action', AuditService::ACTION_UPDATE)
            ->firstOrFail();

        $this->assertSame($doctor->id, $log->user_id);
        $this->assertSame(AppointmentStatus::IN_PROGRESS->value, $log->before_state['status']);
        $this->assertSame(AppointmentStatus::COMPLETED->value, $log->after_state['status']);
    }

    public function test_failed_audit_write_rolls_back_the_whole_mutation(): void
    {
        $staff = $this->userWithRole('Registration Staff');

        DB::listen(function ($query) {
            if (str_contains($query->sql, 'insert into "audit_logs"')) {
                throw new \RuntimeException('Simulated audit failure');
            }
        });

        $this->actingAs($staff, 'sanctum')->postJson('/api/v1/patients', [
            'name' => 'Budi Santoso',
            'dob' => '1990-05-15',
            'gender' => 'MALE',
        ])->assertStatus(500);

        $this->assertDatabaseCount('patients', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_failed_audit_write_rolls_back_payment_creation(): void
    {
        $cashier = $this->userWithRole('Cashier');
        $invoice = Invoice::factory()->create([
            'total_amount' => 100000,
            'status' => InvoiceState::UNPAID,
        ]);

        DB::listen(function ($query) {
            if (str_contains($query->sql, 'insert into "audit_logs"')) {
                throw new \RuntimeException('Simulated audit failure');
            }
        });

        $this->actingAs($cashier, 'sanctum')->postJson(
            "/api/v1/invoices/{$invoice->id}/payments",
            ['amount' => 40000, 'payment_method' => 'CASH']
        )->assertStatus(500);

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceState::UNPAID->value,
        ]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_appointment_creation_records_a_create_audit_entry(): void
    {
        $staff = $this->userWithRole('Registration Staff');
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();

        DoctorSchedule::factory()->create([
            'doctor_id' => $doctor->id,
            'day_of_week' => Carbon::parse('2026-09-01 09:00:00')->dayOfWeekIso,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $this->actingAs($staff, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ])->assertStatus(201);

        $appointment = Appointment::firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $staff->id,
            'action' => AuditService::ACTION_CREATE,
            'entity_type' => $appointment->getMorphClass(),
            'entity_id' => $appointment->id,
        ]);

        $log = AuditLog::where('entity_type', $appointment->getMorphClass())
            ->where('entity_id', $appointment->id)
            ->firstOrFail();

        $this->assertSame(
            '2026-09-01 09:00:00',
            $log->after_state['starts_at']
        );
    }

    public function test_failed_audit_write_rolls_back_appointment_creation(): void
    {
        $staff = $this->userWithRole('Registration Staff');
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();

        DoctorSchedule::factory()->create([
            'doctor_id' => $doctor->id,
            'day_of_week' => Carbon::parse('2026-09-01 09:00:00')->dayOfWeekIso,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        DB::listen(function ($query) {
            if (str_contains($query->sql, 'insert into "audit_logs"')) {
                throw new \RuntimeException('Simulated audit failure');
            }
        });

        $this->actingAs($staff, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ])->assertStatus(500);

        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }
}
