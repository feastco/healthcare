<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationTest extends TestCase
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

    public function test_super_admin_can_list_users(): void
    {
        $user = $this->superAdmin();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/users');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_regular_user_gets_403_when_managing_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/users');

        $response->assertStatus(403);
    }

    public function test_regular_user_gets_403_when_managing_roles(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/roles', [
            'name' => 'New Role',
        ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_role(): void
    {
        $user = $this->superAdmin();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/roles', [
            'name' => 'Nurse',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('roles', ['name' => 'Nurse']);
    }

    public function test_role_with_assignments_cannot_be_deleted(): void
    {
        $superAdmin = $this->superAdmin();

        $role = Role::findByName('Cashier');
        $assignedUser = User::factory()->create()->assignRole($role);

        $response = $this->actingAs($superAdmin, 'sanctum')->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('roles', ['name' => 'Cashier']);
    }

    public function test_role_without_assignments_can_be_deleted(): void
    {
        $superAdmin = $this->superAdmin();

        $role = Role::create(['name' => 'Nurse']);

        $response = $this->actingAs($superAdmin, 'sanctum')->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('roles', ['name' => 'Nurse']);
    }

    public function test_super_admin_can_grant_and_revoke_permissions(): void
    {
        $superAdmin = $this->superAdmin();

        $role = Role::create(['name' => 'Nurse']);
        $permission = Permission::where('name', 'users.view')->first();

        $grant = $this->actingAs($superAdmin, 'sanctum')->postJson("/api/v1/roles/{$role->id}/permissions", [
            'permissions' => [$permission->id],
        ]);

        $grant->assertStatus(200);
        $this->assertTrue($role->fresh()->hasPermissionTo('users.view'));

        $revoke = $this->actingAs($superAdmin, 'sanctum')
            ->deleteJson("/api/v1/roles/{$role->id}/permissions/{$permission->id}");

        $revoke->assertStatus(200);
        $this->assertFalse($role->fresh()->hasPermissionTo('users.view'));
    }

    public function test_regular_user_gets_403_when_granting_permissions(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Nurse']);
        $permission = Permission::where('name', 'users.view')->first();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/roles/{$role->id}/permissions", [
            'permissions' => [$permission->id],
        ]);

        $response->assertStatus(403);
    }
}
