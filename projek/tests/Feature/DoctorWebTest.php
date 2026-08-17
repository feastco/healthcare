<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorWebTest extends TestCase
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
            'name' => 'Dr. Ahmad Fauzi',
            'department_id' => Department::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login_when_accessing_doctor_list(): void
    {
        $this->get('/master-data/doctors')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_doctor_create_form(): void
    {
        $this->get('/master-data/doctors/create')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_storing_doctor(): void
    {
        $this->post('/master-data/doctors', $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_doctor_detail(): void
    {
        $this->get('/master-data/doctors/1')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_doctor_edit_form(): void
    {
        $this->get('/master-data/doctors/1/edit')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_updating_doctor(): void
    {
        $this->put('/master-data/doctors/1', $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_deleting_doctor(): void
    {
        $this->delete('/master-data/doctors/1', ['_token' => csrf_token()])->assertRedirect(route('login'));
    }

    public function test_doctor_cannot_access_doctor_list(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->get('/master-data/doctors')
            ->assertStatus(403);
    }

    public function test_cashier_cannot_access_doctor_list(): void
    {
        $this->actingAs($this->userWithRole('Cashier'))
            ->get('/master-data/doctors')
            ->assertStatus(403);
    }

    public function test_it_admin_cannot_access_doctor_list(): void
    {
        $this->actingAs($this->userWithRole('IT/Admin'))
            ->get('/master-data/doctors')
            ->assertStatus(403);
    }

    public function test_user_without_role_cannot_access_doctor_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/master-data/doctors')
            ->assertStatus(403);
    }

    public function test_unauthorized_role_cannot_create_doctor(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->post('/master-data/doctors', $this->validPayload())
            ->assertStatus(403);

        $this->assertDatabaseMissing('doctors', ['name' => 'Dr. Ahmad Fauzi']);
    }

    public function test_unauthorized_role_cannot_update_doctor(): void
    {
        $doctor = Doctor::factory()->create();

        $this->actingAs($this->userWithRole('Cashier'))
            ->put("/master-data/doctors/{$doctor->id}", $this->validPayload([
                'name' => 'Hacked Name',
            ]))
            ->assertStatus(403);

        $this->assertDatabaseMissing('doctors', ['name' => 'Hacked Name']);
    }

    public function test_unauthorized_role_cannot_delete_doctor(): void
    {
        $doctor = Doctor::factory()->create();

        $this->actingAs($this->userWithRole('Doctor'))
            ->delete("/master-data/doctors/{$doctor->id}", ['_token' => csrf_token()])
            ->assertStatus(403);

        $this->assertDatabaseHas('doctors', ['id' => $doctor->id]);
    }

    public function test_super_admin_can_access_doctor_list(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->superAdmin())->get('/master-data/doctors');

        $response->assertStatus(200);
        $response->assertSee('Doctors');
        $response->assertSee('Add Doctor');
    }

    public function test_registration_staff_can_access_doctor_list(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/master-data/doctors');

        $response->assertStatus(200);
        $response->assertSee('Doctors');
        $response->assertDontSee('Add Doctor');
    }

    public function test_super_admin_can_access_doctor_create_form(): void
    {
        $this->withoutVite();

        $department = Department::factory()->create(['name' => 'Cardiology']);
        $user = User::factory()->create(['name' => 'Candidate User']);

        $response = $this->actingAs($this->superAdmin())->get('/master-data/doctors/create');

        $response->assertStatus(200);
        $response->assertSee('Add Doctor');
        $response->assertSee('name="name"', false);
        $response->assertSee('name="department_id"', false);
        $response->assertSee('name="user_id"', false);
        $response->assertSee('Cardiology');
        $response->assertSee('Candidate User');
    }

    public function test_registration_staff_cannot_access_doctor_create_form(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/master-data/doctors/create')
            ->assertStatus(403);
    }

    public function test_super_admin_can_view_doctor_detail(): void
    {
        $this->withoutVite();

        $doctor = Doctor::factory()->create(['name' => 'Detail Test Doctor']);

        $response = $this->actingAs($this->superAdmin())
            ->get("/master-data/doctors/{$doctor->id}");

        $response->assertStatus(200);
        $response->assertSee('Detail Test Doctor');
        $response->assertSee('Edit Doctor');
    }

    public function test_registration_staff_can_view_doctor_detail_without_mutation_controls(): void
    {
        $this->withoutVite();

        $doctor = Doctor::factory()->create(['name' => 'Staff Detail Doctor']);

        $response = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get("/master-data/doctors/{$doctor->id}");

        $response->assertStatus(200);
        $response->assertSee('Staff Detail Doctor');
        $response->assertDontSee('Edit Doctor');
        $response->assertDontSee('Delete Confirmation');
    }

    public function test_super_admin_can_access_doctor_edit_form(): void
    {
        $this->withoutVite();

        $doctor = Doctor::factory()->create(['name' => 'Edit Form Doctor']);

        $response = $this->actingAs($this->superAdmin())
            ->get("/master-data/doctors/{$doctor->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Edit Doctor');
        $response->assertSee('Edit Form Doctor');
    }

    public function test_registration_staff_cannot_access_doctor_edit_form(): void
    {
        $doctor = Doctor::factory()->create();

        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get("/master-data/doctors/{$doctor->id}/edit")
            ->assertStatus(403);
    }

    public function test_super_admin_can_create_doctor(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post('/master-data/doctors', $this->validPayload());

        $doctor = Doctor::latest('id')->firstOrFail();

        $response->assertRedirect(route('doctors.show', $doctor));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('doctors', ['name' => 'Dr. Ahmad Fauzi']);
    }

    public function test_registration_staff_cannot_create_doctor(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->post('/master-data/doctors', $this->validPayload())
            ->assertStatus(403);

        $this->assertDatabaseMissing('doctors', ['name' => 'Dr. Ahmad Fauzi']);
    }

    public function test_super_admin_can_update_doctor(): void
    {
        $doctor = Doctor::factory()->create(['name' => 'Before Update']);
        $department = Department::factory()->create();

        $this->actingAs($this->superAdmin())
            ->put("/master-data/doctors/{$doctor->id}", $this->validPayload([
                'name' => 'After Update',
                'department_id' => $department->id,
            ]))
            ->assertRedirect(route('doctors.show', $doctor));

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'name' => 'After Update',
            'department_id' => $department->id,
        ]);
    }

    public function test_registration_staff_cannot_update_doctor(): void
    {
        $doctor = Doctor::factory()->create(['name' => 'Staff Before']);

        $this->actingAs($this->userWithRole('Registration Staff'))
            ->put("/master-data/doctors/{$doctor->id}", $this->validPayload([
                'name' => 'Staff After',
            ]))
            ->assertStatus(403);

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'name' => 'Staff Before',
        ]);
    }

    public function test_super_admin_can_delete_doctor(): void
    {
        $doctor = Doctor::factory()->create(['name' => 'To Delete']);

        $this->actingAs($this->superAdmin())
            ->delete("/master-data/doctors/{$doctor->id}", ['_token' => csrf_token()])
            ->assertRedirect(route('doctors.index'));

        $this->assertDatabaseMissing('doctors', ['id' => $doctor->id]);

        $this->assertTrue(
            AuditLog::query()
                ->where('entity_id', $doctor->id)
                ->where('action', 'DELETE')
                ->exists()
        );
    }

    public function test_doctor_with_schedules_cannot_be_deleted(): void
    {
        $doctor = Doctor::factory()->hasSchedules(1)->create(['name' => 'Protected Doctor']);

        $this->actingAs($this->superAdmin())
            ->from("/master-data/doctors/{$doctor->id}")
            ->delete("/master-data/doctors/{$doctor->id}", ['_token' => csrf_token()])
            ->assertRedirect("/master-data/doctors/{$doctor->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('doctors', ['id' => $doctor->id]);
    }

    public function test_registration_staff_cannot_delete_doctor(): void
    {
        $doctor = Doctor::factory()->create();

        $this->actingAs($this->userWithRole('Registration Staff'))
            ->delete("/master-data/doctors/{$doctor->id}", ['_token' => csrf_token()])
            ->assertStatus(403);

        $this->assertDatabaseHas('doctors', ['id' => $doctor->id]);
    }

    public function test_create_doctor_shows_validation_errors(): void
    {
        $response = $this->from('/master-data/doctors/create')
            ->actingAs($this->superAdmin())
            ->post('/master-data/doctors', [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect('/master-data/doctors/create');
        $response->assertSessionHasErrors(['name', 'department_id', 'user_id']);
    }

    public function test_create_doctor_rejects_nonexistent_department(): void
    {
        $response = $this->from('/master-data/doctors/create')
            ->actingAs($this->superAdmin())
            ->post('/master-data/doctors', $this->validPayload([
                'department_id' => 999999,
            ]));

        $response->assertRedirect('/master-data/doctors/create');
        $response->assertSessionHasErrors('department_id');
    }

    public function test_create_doctor_rejects_nonexistent_user(): void
    {
        $response = $this->from('/master-data/doctors/create')
            ->actingAs($this->superAdmin())
            ->post('/master-data/doctors', $this->validPayload([
                'user_id' => 999999,
            ]));

        $response->assertRedirect('/master-data/doctors/create');
        $response->assertSessionHasErrors('user_id');
    }

    public function test_update_doctor_shows_validation_errors(): void
    {
        $doctor = Doctor::factory()->create();

        $response = $this->from("/master-data/doctors/{$doctor->id}/edit")
            ->actingAs($this->superAdmin())
            ->put("/master-data/doctors/{$doctor->id}", [
                '_token' => csrf_token(),
                'name' => '',
            ]);

        $response->assertRedirect("/master-data/doctors/{$doctor->id}/edit");
        $response->assertSessionHasErrors('name');
    }

    public function test_audit_log_is_recorded_on_doctor_create(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/master-data/doctors', $this->validPayload());

        $doctor = Doctor::latest('id')->firstOrFail();

        $this->assertTrue(
            AuditLog::query()
                ->where('entity_id', $doctor->id)
                ->where('action', 'CREATE')
                ->exists()
        );
    }

    public function test_doctor_pages_do_not_expose_sensitive_material(): void
    {
        $this->withoutVite();

        $user = $this->superAdmin();
        $doctor = Doctor::factory()->create();

        $html = $this->actingAs($user)->get("/master-data/doctors/{$doctor->id}")->getContent();

        $this->assertStringNotContainsString($user->password, $html);
        $this->assertStringNotContainsString('plain-text-token', $html);
    }

    public function test_unauthorized_role_has_no_doctor_nav_href(): void
    {
        $this->withoutVite();

        $html = $this->actingAs($this->userWithRole('Cashier'))
            ->get('/home')
            ->getContent();

        $this->assertStringNotContainsString('/master-data/doctors', $html);
    }

    public function test_doctor_not_found_returns_404(): void
    {
        $this->withoutVite();

        $this->actingAs($this->superAdmin())
            ->get('/master-data/doctors/999999')
            ->assertStatus(404);
    }
}
