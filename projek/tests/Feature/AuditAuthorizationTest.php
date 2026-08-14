<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditAuthorizationTest extends TestCase
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

    public function test_it_admin_can_list_audit_logs(): void
    {
        AuditLog::factory()->count(3)->create();

        $admin = $this->userWithRole('IT/Admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/audits')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_unauthenticated_request_receives_401(): void
    {
        $this->getJson('/api/v1/audits')->assertStatus(401);
    }

    public function test_non_it_admin_receives_403(): void
    {
        AuditLog::factory()->count(2)->create();

        $user = $this->userWithRole('Doctor');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/audits')
            ->assertStatus(403);
    }

    public function test_super_admin_is_not_granted_audit_access(): void
    {
        $superAdmin = $this->userWithRole('Super Admin');

        $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/v1/audits')
            ->assertStatus(403);
    }

    public function test_post_method_is_not_allowed_on_audit_endpoint(): void
    {
        $admin = $this->userWithRole('IT/Admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/audits', [])
            ->assertStatus(405);
    }

    public function test_audit_list_response_follows_api_contract(): void
    {
        $actor = $this->userWithRole('IT/Admin');
        $log = AuditLog::factory()->create([
            'user_id' => $actor->id,
            'action' => 'CREATE',
            'entity_type' => 'App\\Models\\Patient',
            'entity_id' => 1,
            'before_state' => [],
            'after_state' => ['name' => 'Budi'],
        ]);

        $this->actingAs($actor, 'sanctum')
            ->getJson('/api/v1/audits')
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $log->id)
            ->assertJsonPath('data.0.user_id', $actor->id)
            ->assertJsonPath('data.0.user_name', $actor->name)
            ->assertJsonPath('data.0.action', 'CREATE')
            ->assertJsonPath('data.0.entity_type', 'App\\Models\\Patient')
            ->assertJsonPath('data.0.entity_id', 1)
            ->assertJsonPath('data.0.before_state', [])
            ->assertJsonPath('data.0.after_state.name', 'Budi');
    }
}
