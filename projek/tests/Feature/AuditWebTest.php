<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create()->assignRole($role);
    }

    private function itAdmin(): User
    {
        return $this->userWithRole('IT/Admin');
    }

    private function createLog(array $attributes = []): AuditLog
    {
        return AuditLog::factory()->create($attributes);
    }

    public function test_guest_is_redirected_to_login_when_accessing_audit_logs(): void
    {
        $this->get('/monitoring/audit-logs')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_viewing_audit_log(): void
    {
        $this->get('/monitoring/audit-logs/1')->assertRedirect(route('login'));
    }

    public function test_registration_staff_cannot_access_audit_logs(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/monitoring/audit-logs')
            ->assertStatus(403);
    }

    public function test_doctor_cannot_access_audit_logs(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->get('/monitoring/audit-logs')
            ->assertStatus(403);
    }

    public function test_cashier_cannot_access_audit_logs(): void
    {
        $this->actingAs($this->userWithRole('Cashier'))
            ->get('/monitoring/audit-logs')
            ->assertStatus(403);
    }

    public function test_super_admin_cannot_access_audit_logs(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'))
            ->get('/monitoring/audit-logs')
            ->assertStatus(403);
    }

    public function test_user_without_role_cannot_access_audit_logs(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/monitoring/audit-logs')
            ->assertStatus(403);
    }

    public function test_it_admin_can_access_audit_logs_index(): void
    {
        $this->withoutVite();

        $this->createLog();

        $response = $this->actingAs($this->itAdmin())->get('/monitoring/audit-logs');

        $response->assertStatus(200);
        $response->assertSee('Audit Logs');
        $response->assertSee('Timestamp');
        $response->assertSee('Actor');
        $response->assertSee('Action');
        $response->assertSee('Entity');
    }

    public function test_audit_log_index_shows_actor_action_entity_and_id(): void
    {
        $this->withoutVite();

        $actor = $this->itAdmin();
        $patient = Patient::factory()->create();
        $log = $this->createLog([
            'user_id' => $actor->id,
            'action' => AuditService::ACTION_CREATE,
            'entity_type' => $patient->getMorphClass(),
            'entity_id' => $patient->id,
        ]);

        $response = $this->actingAs($this->itAdmin())->get('/monitoring/audit-logs');

        $response->assertStatus(200);
        $response->assertSee($actor->name);
        $response->assertSee('CREATE');
        $response->assertSee('Patient');
        $response->assertSee((string) $patient->id);
        $response->assertSee("/monitoring/audit-logs/{$log->id}", false);
    }

    public function test_it_admin_can_view_audit_log_detail(): void
    {
        $this->withoutVite();

        $actor = $this->itAdmin();
        $log = $this->createLog([
            'user_id' => $actor->id,
            'action' => AuditService::ACTION_UPDATE,
            'entity_type' => Patient::class,
            'entity_id' => 7,
            'before_state' => ['name' => 'Old Name'],
            'after_state' => ['name' => 'New Name'],
        ]);

        $response = $this->actingAs($this->itAdmin())->get("/monitoring/audit-logs/{$log->id}");

        $response->assertStatus(200);
        $response->assertSee('Audit Log Detail');
        $response->assertSee($actor->name);
        $response->assertSee('UPDATE');
        $response->assertSee('Patient');
        $response->assertSee('7');
        $response->assertSee('Old Name');
        $response->assertSee('New Name');
    }

    public function test_audit_log_detail_renders_sanitized_payload_only(): void
    {
        $this->withoutVite();

        $log = $this->createLog([
            'entity_type' => Patient::class,
            'entity_id' => 1,
            'after_state' => ['identifier_pat' => 'PAT-000001', 'name' => 'Budi'],
        ]);

        $response = $this->actingAs($this->itAdmin())->get("/monitoring/audit-logs/{$log->id}");

        $response->assertStatus(200);
        $response->assertSee('PAT-000001');
        $response->assertSee('Budi');
    }

    public function test_audit_log_detail_does_not_expose_sensitive_material(): void
    {
        $this->withoutVite();

        $admin = $this->itAdmin();
        $log = $this->createLog(['user_id' => $admin->id]);

        $html = $this->actingAs($admin)->get("/monitoring/audit-logs/{$log->id}")->getContent();

        $this->assertStringNotContainsString($admin->password, $html);
        $this->assertStringNotContainsString('plain-text-token', $html);
    }

    public function test_audit_log_detail_on_missing_log_returns_404(): void
    {
        $this->actingAs($this->itAdmin())
            ->get('/monitoring/audit-logs/999999')
            ->assertStatus(404);
    }

    public function test_no_post_route_for_audit_logs(): void
    {
        $this->actingAs($this->itAdmin())
            ->post('/monitoring/audit-logs')
            ->assertStatus(405);
    }

    public function test_unauthorized_role_has_no_audit_logs_nav_href(): void
    {
        $this->withoutVite();

        $html = $this->actingAs($this->userWithRole('Doctor'))
            ->get('/home')
            ->getContent();

        $this->assertStringNotContainsString('/monitoring/audit-logs', $html);
    }

    public function test_it_admin_sees_audit_logs_nav_href(): void
    {
        $this->withoutVite();

        $html = $this->actingAs($this->itAdmin())->get('/home')->getContent();

        $this->assertStringContainsString('/monitoring/audit-logs', $html);
    }
}
