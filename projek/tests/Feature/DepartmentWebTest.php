<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function superAdmin(): User
    {
        return User::where('email', 'superadmin@example.com')->firstOrFail();
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create()->assignRole($role);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            '_token' => csrf_token(),
            'name' => 'Radiology',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login_when_accessing_department_list(): void
    {
        $this->get('/master-data/departments')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_department_create_form(): void
    {
        $this->get('/master-data/departments/create')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_storing_department(): void
    {
        $this->post('/master-data/departments', $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_department_detail(): void
    {
        $this->get('/master-data/departments/1')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_department_edit_form(): void
    {
        $this->get('/master-data/departments/1/edit')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_updating_department(): void
    {
        $this->put('/master-data/departments/1', $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_deleting_department(): void
    {
        $this->delete('/master-data/departments/1', ['_token' => csrf_token()])->assertRedirect(route('login'));
    }

    public function test_doctor_cannot_access_department_list(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->get('/master-data/departments')
            ->assertStatus(403);
    }

    public function test_cashier_cannot_access_department_list(): void
    {
        $this->actingAs($this->userWithRole('Cashier'))
            ->get('/master-data/departments')
            ->assertStatus(403);
    }

    public function test_it_admin_cannot_access_department_list(): void
    {
        $this->actingAs($this->userWithRole('IT/Admin'))
            ->get('/master-data/departments')
            ->assertStatus(403);
    }

    public function test_user_without_role_cannot_access_department_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/master-data/departments')
            ->assertStatus(403);
    }

    public function test_unauthorized_role_cannot_create_department(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->post('/master-data/departments', $this->validPayload())
            ->assertStatus(403);

        $this->assertDatabaseMissing('departments', ['name' => 'Radiology']);
    }

    public function test_unauthorized_role_cannot_update_department(): void
    {
        $department = Department::factory()->create();

        $this->actingAs($this->userWithRole('Cashier'))
            ->put("/master-data/departments/{$department->id}", $this->validPayload([
                'name' => 'Hacked Name',
            ]))
            ->assertStatus(403);

        $this->assertDatabaseMissing('departments', ['name' => 'Hacked Name']);
    }

    public function test_unauthorized_role_cannot_delete_department(): void
    {
        $department = Department::factory()->create();

        $this->actingAs($this->userWithRole('Doctor'))
            ->delete("/master-data/departments/{$department->id}", ['_token' => csrf_token()])
            ->assertStatus(403);

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_super_admin_can_access_department_list(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->superAdmin())->get('/master-data/departments');

        $response->assertStatus(200);
        $response->assertSee('Departments');
        $response->assertSee('Add Department');
    }

    public function test_registration_staff_can_access_department_list(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/master-data/departments');

        $response->assertStatus(200);
        $response->assertSee('Departments');
        $response->assertDontSee('Add Department');
    }

    public function test_super_admin_can_access_department_create_form(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->superAdmin())->get('/master-data/departments/create');

        $response->assertStatus(200);
        $response->assertSee('Add Department');
        $response->assertSee('name="name"', false);
    }

    public function test_registration_staff_cannot_access_department_create_form(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/master-data/departments/create')
            ->assertStatus(403);
    }

    public function test_super_admin_can_view_department_detail(): void
    {
        $this->withoutVite();

        $department = Department::factory()->create(['name' => 'Detail Test Department']);

        $response = $this->actingAs($this->superAdmin())
            ->get("/master-data/departments/{$department->id}");

        $response->assertStatus(200);
        $response->assertSee('Detail Test Department');
        $response->assertSee('Edit Department');
    }

    public function test_registration_staff_can_view_department_detail_without_mutation_controls(): void
    {
        $this->withoutVite();

        $department = Department::factory()->create(['name' => 'Staff Detail Department']);

        $response = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get("/master-data/departments/{$department->id}");

        $response->assertStatus(200);
        $response->assertSee('Staff Detail Department');
        $response->assertDontSee('Edit Department');
        $response->assertDontSee('Delete Confirmation');
    }

    public function test_super_admin_can_access_department_edit_form(): void
    {
        $this->withoutVite();

        $department = Department::factory()->create(['name' => 'Edit Form Department']);

        $response = $this->actingAs($this->superAdmin())
            ->get("/master-data/departments/{$department->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Edit Department');
        $response->assertSee('Edit Form Department');
    }

    public function test_registration_staff_cannot_access_department_edit_form(): void
    {
        $department = Department::factory()->create();

        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get("/master-data/departments/{$department->id}/edit")
            ->assertStatus(403);
    }

    public function test_super_admin_can_create_department(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post('/master-data/departments', $this->validPayload());

        $department = Department::latest('id')->firstOrFail();

        $response->assertRedirect(route('departments.show', $department));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('departments', ['name' => 'Radiology']);
    }

    public function test_registration_staff_cannot_create_department(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->post('/master-data/departments', $this->validPayload())
            ->assertStatus(403);

        $this->assertDatabaseMissing('departments', ['name' => 'Radiology']);
    }

    public function test_super_admin_can_update_department(): void
    {
        $department = Department::factory()->create(['name' => 'Before Update']);

        $this->actingAs($this->superAdmin())
            ->put("/master-data/departments/{$department->id}", $this->validPayload([
                'name' => 'After Update',
            ]))
            ->assertRedirect(route('departments.show', $department));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'After Update',
        ]);
    }

    public function test_registration_staff_cannot_update_department(): void
    {
        $department = Department::factory()->create(['name' => 'Staff Before']);

        $this->actingAs($this->userWithRole('Registration Staff'))
            ->put("/master-data/departments/{$department->id}", $this->validPayload([
                'name' => 'Staff After',
            ]))
            ->assertStatus(403);

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Staff Before',
        ]);
    }

    public function test_super_admin_can_delete_department(): void
    {
        $department = Department::factory()->create(['name' => 'To Delete']);

        $this->actingAs($this->superAdmin())
            ->delete("/master-data/departments/{$department->id}", ['_token' => csrf_token()])
            ->assertRedirect(route('departments.index'));

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);

        $this->assertTrue(
            AuditLog::query()
                ->where('entity_id', $department->id)
                ->where('action', 'DELETE')
                ->exists()
        );
    }

    public function test_department_with_doctors_cannot_be_deleted(): void
    {
        $department = Department::factory()->hasDoctors(1)->create(['name' => 'Protected Department']);

        $this->actingAs($this->superAdmin())
            ->from("/master-data/departments/{$department->id}")
            ->delete("/master-data/departments/{$department->id}", ['_token' => csrf_token()])
            ->assertRedirect("/master-data/departments/{$department->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_registration_staff_cannot_delete_department(): void
    {
        $department = Department::factory()->create();

        $this->actingAs($this->userWithRole('Registration Staff'))
            ->delete("/master-data/departments/{$department->id}", ['_token' => csrf_token()])
            ->assertStatus(403);

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_create_department_shows_validation_errors(): void
    {
        $response = $this->from('/master-data/departments/create')
            ->actingAs($this->superAdmin())
            ->post('/master-data/departments', [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect('/master-data/departments/create');
        $response->assertSessionHasErrors('name');
    }

    public function test_update_department_shows_validation_errors(): void
    {
        $department = Department::factory()->create();

        $response = $this->from("/master-data/departments/{$department->id}/edit")
            ->actingAs($this->superAdmin())
            ->put("/master-data/departments/{$department->id}", [
                '_token' => csrf_token(),
                'name' => str_repeat('a', 256),
            ]);

        $response->assertRedirect("/master-data/departments/{$department->id}/edit");
        $response->assertSessionHasErrors('name');
    }

    public function test_audit_log_is_recorded_on_department_create(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/master-data/departments', $this->validPayload());

        $department = Department::latest('id')->firstOrFail();

        $this->assertTrue(
            AuditLog::query()
                ->where('entity_id', $department->id)
                ->where('action', 'CREATE')
                ->exists()
        );
    }

    public function test_department_pages_do_not_expose_sensitive_material(): void
    {
        $this->withoutVite();

        $user = $this->superAdmin();
        $department = Department::factory()->create();

        $html = $this->actingAs($user)->get("/master-data/departments/{$department->id}")->getContent();

        $this->assertStringNotContainsString($user->password, $html);
        $this->assertStringNotContainsString('plain-text-token', $html);
    }

    public function test_unauthorized_role_has_no_department_nav_href(): void
    {
        $this->withoutVite();

        $html = $this->actingAs($this->userWithRole('Doctor'))
            ->get('/home')
            ->getContent();

        $this->assertStringNotContainsString('/master-data/departments', $html);
    }

    public function test_department_not_found_returns_404(): void
    {
        $this->withoutVite();

        $this->actingAs($this->superAdmin())
            ->get('/master-data/departments/999999')
            ->assertStatus(404);
    }
}
