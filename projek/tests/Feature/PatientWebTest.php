<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientWebTest extends TestCase
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
            'name' => 'Budi Santoso',
            'dob' => '1990-05-15',
            'gender' => 'Male',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login_when_accessing_patient_list(): void
    {
        $this->get('/master-data/patients')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_patient_create_form(): void
    {
        $this->get('/master-data/patients/create')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_storing_patient(): void
    {
        $this->post('/master-data/patients', $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_patient_detail(): void
    {
        $this->get('/master-data/patients/1')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_patient_edit_form(): void
    {
        $this->get('/master-data/patients/1/edit')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_updating_patient(): void
    {
        $this->put('/master-data/patients/1', $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_doctor_cannot_access_patient_list(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->get('/master-data/patients')
            ->assertStatus(403);
    }

    public function test_cashier_cannot_access_patient_list(): void
    {
        $this->actingAs($this->userWithRole('Cashier'))
            ->get('/master-data/patients')
            ->assertStatus(403);
    }

    public function test_it_admin_cannot_access_patient_list(): void
    {
        $this->actingAs($this->userWithRole('IT/Admin'))
            ->get('/master-data/patients')
            ->assertStatus(403);
    }

    public function test_user_without_role_cannot_access_patient_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/master-data/patients')
            ->assertStatus(403);
    }

    public function test_unauthorized_role_cannot_create_patient(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->post('/master-data/patients', $this->validPayload())
            ->assertStatus(403);

        $this->assertDatabaseMissing('patients', ['name' => 'Budi Santoso']);
    }

    public function test_unauthorized_role_cannot_update_patient(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->userWithRole('Cashier'))
            ->put("/master-data/patients/{$patient->id}", $this->validPayload([
                'name' => 'Hacked Name',
            ]))
            ->assertStatus(403);

        $this->assertDatabaseMissing('patients', ['name' => 'Hacked Name']);
    }

    public function test_super_admin_can_access_patient_list(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->superAdmin())->get('/master-data/patients');

        $response->assertStatus(200);
        $response->assertSee('Patients');
        $response->assertSee('Add Patient');
    }

    public function test_registration_staff_can_access_patient_list(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->userWithRole('Registration Staff'))->get('/master-data/patients');

        $response->assertStatus(200);
        $response->assertSee('Patients');
        $response->assertSee('Add Patient');
    }

    public function test_super_admin_can_access_patient_create_form(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->superAdmin())->get('/master-data/patients/create');

        $response->assertStatus(200);
        $response->assertSee('Add Patient');
        $response->assertSee('name="name"', false);
        $response->assertSee('name="dob"', false);
        $response->assertSee('name="gender"', false);
    }

    public function test_registration_staff_can_access_patient_create_form(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/master-data/patients/create');

        $response->assertStatus(200);
        $response->assertSee('Add Patient');
    }

    public function test_super_admin_can_view_patient_detail(): void
    {
        $this->withoutVite();

        $patient = Patient::factory()->create(['name' => 'Detail Test Patient']);

        $response = $this->actingAs($this->superAdmin())
            ->get("/master-data/patients/{$patient->id}");

        $response->assertStatus(200);
        $response->assertSee('Detail Test Patient');
        $response->assertSee($patient->identifier_pat);
        $response->assertSee('Edit Patient');
    }

    public function test_registration_staff_can_view_patient_detail(): void
    {
        $this->withoutVite();

        $patient = Patient::factory()->create(['name' => 'Staff Detail Patient']);

        $response = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get("/master-data/patients/{$patient->id}");

        $response->assertStatus(200);
        $response->assertSee('Staff Detail Patient');
    }

    public function test_super_admin_can_access_patient_edit_form(): void
    {
        $this->withoutVite();

        $patient = Patient::factory()->create(['name' => 'Edit Form Patient']);

        $response = $this->actingAs($this->superAdmin())
            ->get("/master-data/patients/{$patient->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Edit Patient');
        $response->assertSee('Edit Form Patient');
    }

    public function test_registration_staff_can_access_patient_edit_form(): void
    {
        $this->withoutVite();

        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get("/master-data/patients/{$patient->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Edit Patient');
    }

    public function test_super_admin_can_create_patient_and_identifier_is_generated(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post('/master-data/patients', $this->validPayload());

        $patient = Patient::latest('id')->firstOrFail();

        $response->assertRedirect(route('patients.show', $patient));
        $response->assertSessionHas('success');
        $this->assertSame('PAT-'.now()->format('Y').'-000001', $patient->identifier_pat);
    }

    public function test_registration_staff_can_create_patient(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->post('/master-data/patients', $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('patients', ['name' => 'Budi Santoso']);
    }

    public function test_super_admin_can_update_patient(): void
    {
        $patient = Patient::factory()->create(['name' => 'Before Update']);

        $this->actingAs($this->superAdmin())
            ->put("/master-data/patients/{$patient->id}", $this->validPayload([
                'name' => 'After Update',
            ]))
            ->assertRedirect(route('patients.show', $patient));

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'name' => 'After Update',
        ]);
    }

    public function test_registration_staff_can_update_patient(): void
    {
        $patient = Patient::factory()->create(['name' => 'Staff Before']);

        $this->actingAs($this->userWithRole('Registration Staff'))
            ->put("/master-data/patients/{$patient->id}", $this->validPayload([
                'name' => 'Staff After',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'name' => 'Staff After',
        ]);
    }

    public function test_create_patient_shows_validation_errors(): void
    {
        $response = $this->from('/master-data/patients/create')
            ->actingAs($this->superAdmin())
            ->post('/master-data/patients', [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect('/master-data/patients/create');
        $response->assertSessionHasErrors(['name', 'dob', 'gender']);
    }

    public function test_update_patient_shows_validation_errors(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->from("/master-data/patients/{$patient->id}/edit")
            ->actingAs($this->superAdmin())
            ->put("/master-data/patients/{$patient->id}", [
                '_token' => csrf_token(),
                'dob' => now()->addDay()->format('Y-m-d'),
            ]);

        $response->assertRedirect("/master-data/patients/{$patient->id}/edit");
        $response->assertSessionHasErrors('dob');
    }

    public function test_audit_log_is_recorded_on_patient_create(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/master-data/patients', $this->validPayload());

        $patient = Patient::latest('id')->firstOrFail();

        $this->assertTrue(
            AuditLog::query()
                ->where('entity_id', $patient->id)
                ->where('action', 'CREATE')
                ->exists()
        );
    }

    public function test_patient_pages_do_not_expose_sensitive_material(): void
    {
        $this->withoutVite();

        $user = $this->superAdmin();
        $patient = Patient::factory()->create();

        $html = $this->actingAs($user)->get("/master-data/patients/{$patient->id}")->getContent();

        $this->assertStringNotContainsString($user->password, $html);
        $this->assertStringNotContainsString('plain-text-token', $html);
    }

    public function test_unauthorized_role_has_no_patient_nav_href(): void
    {
        $this->withoutVite();

        $html = $this->actingAs($this->userWithRole('Doctor'))
            ->get('/home')
            ->getContent();

        $this->assertStringNotContainsString('/master-data/patients', $html);
    }

    public function test_no_delete_route_for_patients(): void
    {
        $this->actingAs($this->superAdmin())
            ->delete('/master-data/patients/1', ['_token' => csrf_token()])
            ->assertStatus(405);
    }

    public function test_patient_not_found_returns_404(): void
    {
        $this->withoutVite();

        $this->actingAs($this->superAdmin())
            ->get('/master-data/patients/999999')
            ->assertStatus(404);
    }
}
