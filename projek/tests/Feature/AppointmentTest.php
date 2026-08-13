<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppointmentTest extends TestCase
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

    private function superAdmin(): User
    {
        $role = Role::findByName('Super Admin');

        return User::factory()->create()->assignRole($role);
    }

    private function registrationStaff(): User
    {
        $role = Role::findByName('Registration Staff');

        return User::factory()->create()->assignRole($role);
    }

    private function doctorWithSchedule(string $startsAt): Doctor
    {
        $doctor = Doctor::factory()->create();

        DoctorSchedule::factory()->create([
            'doctor_id' => $doctor->id,
            'day_of_week' => Carbon::parse($startsAt)->dayOfWeekIso,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        return $doctor;
    }

    public function test_super_admin_can_create_appointment(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();
        $doctor = $this->doctorWithSchedule('2026-09-01 09:00:00');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'SCHEDULED');
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'SCHEDULED',
        ]);
    }

    public function test_invalid_time_interval_is_rejected(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();
        $doctor = $this->doctorWithSchedule('2026-09-01 09:00:00');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 10:00:00',
            'ends_at' => '2026-09-01 09:00:00',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_appointment_rejected_when_doctor_has_no_schedule(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_appointment_rejected_when_partially_outside_schedule(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();
        $doctor = $this->doctorWithSchedule('2026-09-01 09:00:00');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 18:00:00',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_overlapping_appointment_is_rejected_gracefully(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();
        $doctor = $this->doctorWithSchedule('2026-09-01 09:00:00');

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ])->assertStatus(201);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:30:00',
            'ends_at' => '2026-09-01 10:30:00',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('appointments', 1);
        $this->assertStringNotContainsStringIgnoringCase('insert into', $response->getContent());
    }

    public function test_adjacent_appointments_are_allowed(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();
        $doctor = $this->doctorWithSchedule('2026-09-01 09:00:00');

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ])->assertStatus(201);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 10:00:00',
            'ends_at' => '2026-09-01 11:00:00',
        ])->assertStatus(201);

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_appointments_for_different_doctors_can_overlap(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();
        $doctorA = $this->doctorWithSchedule('2026-09-01 09:00:00');
        $doctorB = $this->doctorWithSchedule('2026-09-01 09:00:00');

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctorA->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ])->assertStatus(201);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctorB->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ])->assertStatus(201);

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_validation_fails_for_invalid_patient_or_doctor(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();
        $doctor = $this->doctorWithSchedule('2026-09-01 09:00:00');

        $missingPatient = $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => 999999,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ]);
        $missingPatient->assertStatus(422);

        $missingDoctor = $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => 999999,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ]);
        $missingDoctor->assertStatus(422);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_registration_staff_can_create_and_list_appointments(): void
    {
        $user = $this->registrationStaff();
        $patient = Patient::factory()->create();
        $doctor = $this->doctorWithSchedule('2026-09-01 09:00:00');

        $create = $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ]);
        $create->assertStatus(201);

        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/appointments');
        $list->assertStatus(200);
        $list->assertJsonStructure(['data']);

        $show = $this->actingAs($user, 'sanctum')->getJson("/api/v1/appointments/{$create->json('data.id')}");
        $show->assertStatus(200);
        $show->assertJsonPath('data.status', 'SCHEDULED');
        $show->assertJsonStructure([
            'data' => [
                'id', 'patient_id', 'doctor_id', 'starts_at', 'ends_at', 'status',
                'patient' => ['id', 'name'], 'doctor' => ['id', 'name'],
            ],
        ]);
    }

    public function test_regular_user_gets_403_for_appointment_operations(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $doctor = $this->doctorWithSchedule('2026-09-01 09:00:00');
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ]);

        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/appointments');
        $list->assertStatus(403);

        $create = $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 11:00:00',
            'ends_at' => '2026-09-01 12:00:00',
        ]);
        $create->assertStatus(403);

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_super_admin_can_list_and_show_appointments(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();
        $doctor = $this->doctorWithSchedule('2026-09-01 09:00:00');
        $first = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 09:00:00',
            'ends_at' => '2026-09-01 10:00:00',
        ]);
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => '2026-09-01 10:00:00',
            'ends_at' => '2026-09-01 11:00:00',
        ]);

        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/appointments');
        $list->assertStatus(200);
        $list->assertJsonStructure(['data']);
        $this->assertCount(2, $list->json('data'));

        $show = $this->actingAs($user, 'sanctum')->getJson("/api/v1/appointments/{$first->id}");
        $show->assertStatus(200);
        $show->assertJsonPath('data.id', $first->id);
        $show->assertJsonPath('data.patient.id', $patient->id);
        $show->assertJsonPath('data.doctor.id', $doctor->id);
    }
}
