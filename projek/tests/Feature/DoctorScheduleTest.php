<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorScheduleTest extends TestCase
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

    public function test_super_admin_can_create_schedule(): void
    {
        $user = $this->superAdmin();
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/doctors/{$doctor->id}/schedules", [
            'day_of_week' => 2,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('doctor_schedules', [
            'doctor_id' => $doctor->id,
            'day_of_week' => 2,
        ]);
    }

    public function test_validation_fails_for_invalid_day_or_time_range(): void
    {
        $user = $this->superAdmin();
        $doctor = Doctor::factory()->create();

        $badDay = $this->actingAs($user, 'sanctum')->postJson("/api/v1/doctors/{$doctor->id}/schedules", [
            'day_of_week' => 8,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);
        $badDay->assertStatus(422);

        $badRange = $this->actingAs($user, 'sanctum')->postJson("/api/v1/doctors/{$doctor->id}/schedules", [
            'day_of_week' => 1,
            'start_time' => '12:00',
            'end_time' => '08:00',
        ]);
        $badRange->assertStatus(422);
    }

    public function test_super_admin_can_list_schedules_for_doctor(): void
    {
        $user = $this->superAdmin();
        $doctor = Doctor::factory()->hasSchedules(3)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/doctors/{$doctor->id}/schedules");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_list_schedules_only_returns_schedules_of_requested_doctor(): void
    {
        $user = $this->superAdmin();
        $doctor = Doctor::factory()->hasSchedules(2)->create();
        Doctor::factory()->hasSchedules(1)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/doctors/{$doctor->id}/schedules");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_super_admin_can_update_and_delete_schedule(): void
    {
        $user = $this->superAdmin();
        $schedule = DoctorSchedule::factory()->create();

        $update = $this->actingAs($user, 'sanctum')->putJson("/api/v1/schedules/{$schedule->id}", [
            'end_time' => '14:00',
        ]);
        $update->assertStatus(200);
        $this->assertDatabaseHas('doctor_schedules', ['id' => $schedule->id, 'end_time' => '14:00']);

        $delete = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/schedules/{$schedule->id}");
        $delete->assertStatus(200);
        $this->assertDatabaseMissing('doctor_schedules', ['id' => $schedule->id]);
    }

    public function test_registration_staff_can_read_but_not_modify_schedules(): void
    {
        $user = $this->registrationStaff();
        $doctor = Doctor::factory()->create();
        $schedule = DoctorSchedule::factory()->create(['doctor_id' => $doctor->id]);

        $list = $this->actingAs($user, 'sanctum')->getJson("/api/v1/doctors/{$doctor->id}/schedules");
        $list->assertStatus(200);

        $create = $this->actingAs($user, 'sanctum')->postJson("/api/v1/doctors/{$doctor->id}/schedules", [
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);
        $create->assertStatus(403);

        $update = $this->actingAs($user, 'sanctum')->putJson("/api/v1/schedules/{$schedule->id}", [
            'start_time' => '09:00',
        ]);
        $update->assertStatus(403);

        $delete = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/schedules/{$schedule->id}");
        $delete->assertStatus(403);
    }

    public function test_regular_user_gets_403_for_schedule_management(): void
    {
        $user = User::factory()->create();
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/doctors/{$doctor->id}/schedules", [
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $response->assertStatus(403);
    }
}
