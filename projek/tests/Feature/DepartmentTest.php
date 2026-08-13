<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentTest extends TestCase
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

    public function test_super_admin_can_create_department(): void
    {
        $user = $this->superAdmin();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/departments', [
            'name' => 'Internal Medicine',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('departments', ['name' => 'Internal Medicine']);
    }

    public function test_validation_fails_for_missing_name(): void
    {
        $user = $this->superAdmin();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/departments', []);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    public function test_super_admin_can_list_show_update_department(): void
    {
        $user = $this->superAdmin();
        $department = Department::factory()->create();

        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/departments');
        $list->assertStatus(200)->assertJsonStructure(['data', 'meta']);

        $show = $this->actingAs($user, 'sanctum')->getJson("/api/v1/departments/{$department->id}");
        $show->assertStatus(200)->assertJsonPath('data.id', $department->id);

        $update = $this->actingAs($user, 'sanctum')->putJson("/api/v1/departments/{$department->id}", [
            'name' => 'Pediatrics',
        ]);
        $update->assertStatus(200);
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Pediatrics']);
    }

    public function test_super_admin_can_delete_department(): void
    {
        $user = $this->superAdmin();
        $department = Department::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/departments/{$department->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_department_with_doctors_cannot_be_deleted(): void
    {
        $user = $this->superAdmin();
        $department = Department::factory()->hasDoctors(1)->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/departments/{$department->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_registration_staff_can_read_but_not_modify_departments(): void
    {
        $user = $this->registrationStaff();
        $department = Department::factory()->create();

        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/departments');
        $list->assertStatus(200);

        $create = $this->actingAs($user, 'sanctum')->postJson('/api/v1/departments', [
            'name' => 'Forbidden Department',
        ]);
        $create->assertStatus(403);

        $update = $this->actingAs($user, 'sanctum')->putJson("/api/v1/departments/{$department->id}", [
            'name' => 'Forbidden Update',
        ]);
        $update->assertStatus(403);
    }

    public function test_regular_user_gets_403_for_department_management(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/departments', [
            'name' => 'Unauthorized Department',
        ]);

        $response->assertStatus(403);
    }
}
