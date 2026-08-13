<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorTest extends TestCase
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

    public function test_super_admin_can_create_doctor(): void
    {
        $user = $this->superAdmin();
        $department = Department::factory()->create();
        $doctorUser = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/doctors', [
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'name' => 'Dr. Ahmad',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('doctors', ['name' => 'Dr. Ahmad']);
    }

    public function test_super_admin_can_list_show_update_doctor(): void
    {
        $user = $this->superAdmin();
        $doctor = Doctor::factory()->create();

        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/doctors');
        $list->assertStatus(200)->assertJsonStructure(['data', 'meta']);

        $show = $this->actingAs($user, 'sanctum')->getJson("/api/v1/doctors/{$doctor->id}");
        $show->assertStatus(200)->assertJsonPath('data.id', $doctor->id);

        $update = $this->actingAs($user, 'sanctum')->putJson("/api/v1/doctors/{$doctor->id}", [
            'name' => 'Dr. Ahmad Updated',
        ]);
        $update->assertStatus(200);
        $this->assertDatabaseHas('doctors', ['id' => $doctor->id, 'name' => 'Dr. Ahmad Updated']);
    }

    public function test_validation_fails_for_unknown_user_or_department(): void
    {
        $user = $this->superAdmin();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/doctors', [
            'user_id' => 999,
            'department_id' => 999,
            'name' => 'Dr. Invalid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    public function test_super_admin_can_delete_doctor(): void
    {
        $user = $this->superAdmin();
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/doctors/{$doctor->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('doctors', ['id' => $doctor->id]);
    }

    public function test_doctor_with_schedule_cannot_be_deleted(): void
    {
        $user = $this->superAdmin();
        $doctor = Doctor::factory()->hasSchedules(1)->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/doctors/{$doctor->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('doctors', ['id' => $doctor->id]);
    }

    public function test_registration_staff_can_read_but_not_modify_doctors(): void
    {
        $user = $this->registrationStaff();
        $department = Department::factory()->create();
        $doctorUser = User::factory()->create();
        $doctor = Doctor::factory()->create();

        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/doctors');
        $list->assertStatus(200);

        $create = $this->actingAs($user, 'sanctum')->postJson('/api/v1/doctors', [
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'name' => 'Dr. Forbidden',
        ]);
        $create->assertStatus(403);

        $update = $this->actingAs($user, 'sanctum')->putJson("/api/v1/doctors/{$doctor->id}", [
            'name' => 'Dr. Forbidden Update',
        ]);
        $update->assertStatus(403);

        $delete = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/doctors/{$doctor->id}");
        $delete->assertStatus(403);
    }

    public function test_regular_user_gets_403_for_doctor_management(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/doctors', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'name' => 'Dr. Unauthorized',
        ]);

        $response->assertStatus(403);
    }
}
