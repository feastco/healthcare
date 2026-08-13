<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppointmentStatusTransitionTest extends TestCase
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

    private function registrationStaff(): User
    {
        $role = Role::findByName('Registration Staff', 'web');

        return User::factory()->create()->assignRole($role);
    }

    private function doctorUser(): User
    {
        $role = Role::findByName('Doctor', 'web');

        return User::factory()->create()->assignRole($role);
    }

    private function itAdmin(): User
    {
        $role = Role::findByName('IT/Admin', 'web');

        return User::factory()->create()->assignRole($role);
    }

    private function createAppointment(AppointmentStatus $status): Appointment
    {
        return Appointment::factory()->create([
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'status' => $status,
        ]);
    }

    private function transition(User $user, int $id, string $status)
    {
        return $this->actingAs($user, 'sanctum')->patchJson(
            "/api/v1/appointments/{$id}/status",
            ['status' => $status]
        );
    }

    public function test_registration_staff_can_mark_scheduled_as_confirmed(): void
    {
        $user = $this->registrationStaff();
        $appointment = $this->createAppointment(AppointmentStatus::SCHEDULED);

        $response = $this->transition($user, $appointment->id, 'CONFIRMED');

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'CONFIRMED');
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CONFIRMED']);
    }

    public function test_registration_staff_can_cancel_scheduled_appointment(): void
    {
        $user = $this->registrationStaff();
        $appointment = $this->createAppointment(AppointmentStatus::SCHEDULED);

        $response = $this->transition($user, $appointment->id, 'CANCELLED');

        $response->assertStatus(200);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CANCELLED']);
    }

    public function test_registration_staff_can_mark_confirmed_as_waiting(): void
    {
        $user = $this->registrationStaff();
        $appointment = $this->createAppointment(AppointmentStatus::CONFIRMED);

        $response = $this->transition($user, $appointment->id, 'WAITING');

        $response->assertStatus(200);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'WAITING']);
    }

    public function test_registration_staff_can_mark_confirmed_as_cancelled(): void
    {
        $user = $this->registrationStaff();
        $appointment = $this->createAppointment(AppointmentStatus::CONFIRMED);

        $response = $this->transition($user, $appointment->id, 'CANCELLED');

        $response->assertStatus(200);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CANCELLED']);
    }

    public function test_registration_staff_can_mark_confirmed_as_no_show(): void
    {
        $user = $this->registrationStaff();
        $appointment = $this->createAppointment(AppointmentStatus::CONFIRMED);

        $response = $this->transition($user, $appointment->id, 'NO_SHOW');

        $response->assertStatus(200);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'NO_SHOW']);
    }

    public function test_registration_staff_can_cancel_waiting_appointment(): void
    {
        $user = $this->registrationStaff();
        $appointment = $this->createAppointment(AppointmentStatus::WAITING);

        $response = $this->transition($user, $appointment->id, 'CANCELLED');

        $response->assertStatus(200);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CANCELLED']);
    }

    public function test_registration_staff_cannot_start_service(): void
    {
        $user = $this->registrationStaff();
        $appointment = $this->createAppointment(AppointmentStatus::WAITING);

        $response = $this->transition($user, $appointment->id, 'IN_PROGRESS');

        $response->assertStatus(403);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'WAITING']);
    }

    public function test_registration_staff_cannot_complete_service(): void
    {
        $user = $this->registrationStaff();
        $appointment = $this->createAppointment(AppointmentStatus::IN_PROGRESS);

        $response = $this->transition($user, $appointment->id, 'COMPLETED');

        $response->assertStatus(403);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'IN_PROGRESS']);
    }

    public function test_doctor_can_start_service(): void
    {
        $user = $this->doctorUser();
        $appointment = $this->createAppointment(AppointmentStatus::WAITING);

        $response = $this->transition($user, $appointment->id, 'IN_PROGRESS');

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'IN_PROGRESS');
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'IN_PROGRESS']);
    }

    public function test_doctor_can_complete_service(): void
    {
        $user = $this->doctorUser();
        $appointment = $this->createAppointment(AppointmentStatus::IN_PROGRESS);

        $response = $this->transition($user, $appointment->id, 'COMPLETED');

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'COMPLETED');
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'COMPLETED']);
    }

    public function test_doctor_cannot_perform_registration_transitions(): void
    {
        $user = $this->doctorUser();
        $appointment = $this->createAppointment(AppointmentStatus::SCHEDULED);

        $response = $this->transition($user, $appointment->id, 'CONFIRMED');

        $response->assertStatus(403);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'SCHEDULED']);
    }

    public function test_doctor_cannot_cancel_confirmed_appointment(): void
    {
        $user = $this->doctorUser();
        $appointment = $this->createAppointment(AppointmentStatus::CONFIRMED);

        $response = $this->transition($user, $appointment->id, 'CANCELLED');

        $response->assertStatus(403);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CONFIRMED']);
    }

    public function test_it_admin_cannot_perform_any_transition(): void
    {
        $user = $this->itAdmin();
        $appointment = $this->createAppointment(AppointmentStatus::CONFIRMED);

        $response = $this->transition($user, $appointment->id, 'WAITING');

        $response->assertStatus(403);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CONFIRMED']);
    }

    public function test_super_admin_cannot_perform_doctor_only_transition(): void
    {
        $role = Role::findByName('Super Admin');
        $user = User::factory()->create()->assignRole($role);
        $appointment = $this->createAppointment(AppointmentStatus::WAITING);

        $response = $this->transition($user, $appointment->id, 'IN_PROGRESS');

        $response->assertStatus(403);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'WAITING']);
    }

    public function test_terminal_state_rejects_all_mutations(): void
    {
        foreach ([AppointmentStatus::COMPLETED, AppointmentStatus::CANCELLED, AppointmentStatus::NO_SHOW] as $terminal) {
            $user = $this->registrationStaff();
            $appointment = $this->createAppointment($terminal);

            foreach (AppointmentStatus::cases() as $target) {
                $response = $this->transition($user, $appointment->id, $target->value);
                $response->assertStatus(403, "{$terminal->value} -> {$target->value}");
                $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => $terminal->value]);
            }
        }
    }

    public function test_all_invalid_transitions_are_rejected(): void
    {
        $valid = [
            'SCHEDULED' => ['CONFIRMED', 'CANCELLED'],
            'CONFIRMED' => ['WAITING', 'CANCELLED', 'NO_SHOW'],
            'WAITING' => ['IN_PROGRESS', 'CANCELLED'],
            'IN_PROGRESS' => ['COMPLETED'],
        ];

        $user = $this->registrationStaff();

        foreach (AppointmentStatus::cases() as $from) {
            $appointment = $this->createAppointment($from);

            foreach (AppointmentStatus::cases() as $to) {
                $allowed = in_array($to->value, $valid[$from->value] ?? [], true);

                if (! $allowed) {
                    $response = $this->transition($user, $appointment->id, $to->value);
                    $response->assertStatus(403, "{$from->value} -> {$to->value}");
                }
            }
        }
    }

    public function test_invalid_target_status_is_rejected_with_422(): void
    {
        $user = $this->registrationStaff();
        $appointment = $this->createAppointment(AppointmentStatus::SCHEDULED);

        $response = $this->transition($user, $appointment->id, 'INVALID_STATUS');

        $response->assertStatus(422);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'SCHEDULED']);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $appointment = $this->createAppointment(AppointmentStatus::SCHEDULED);

        $response = $this->patchJson("/api/v1/appointments/{$appointment->id}/status", ['status' => 'CONFIRMED']);

        $response->assertStatus(401);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'SCHEDULED']);
    }

    public function test_missing_appointment_returns_404(): void
    {
        $user = $this->registrationStaff();

        $response = $this->transition($user, 999999, 'CONFIRMED');

        $response->assertStatus(404);
    }

    public function test_response_contract_includes_patient_and_doctor(): void
    {
        $user = $this->registrationStaff();
        $appointment = $this->createAppointment(AppointmentStatus::SCHEDULED);

        $response = $this->transition($user, $appointment->id, 'CONFIRMED');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id', 'patient_id', 'doctor_id', 'starts_at', 'ends_at', 'status',
                'patient' => ['id', 'name'], 'doctor' => ['id', 'name'],
            ],
        ]);
    }

    public function test_no_generic_appointment_update_endpoint_exists(): void
    {
        $user = $this->registrationStaff();
        $appointment = $this->createAppointment(AppointmentStatus::SCHEDULED);

        $response = $this->actingAs($user, 'sanctum')->patchJson(
            "/api/v1/appointments/{$appointment->id}",
            ['status' => 'CONFIRMED']
        );

        $response->assertStatus(405);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'SCHEDULED']);
    }
}
