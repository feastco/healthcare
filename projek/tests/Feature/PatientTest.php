<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientTest extends TestCase
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

    public function test_super_admin_can_create_patient_and_identifier_is_generated(): void
    {
        $user = $this->superAdmin();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/patients', [
            'name' => 'Budi Santoso',
            'dob' => '1990-05-15',
            'gender' => 'Male',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.identifier_pat', 'PAT-'.now()->format('Y').'-000001');
        $this->assertDatabaseHas('patients', ['name' => 'Budi Santoso']);
    }

    public function test_validation_fails_for_missing_required_fields(): void
    {
        $user = $this->superAdmin();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/patients', []);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    public function test_super_admin_can_list_and_show_patient(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();

        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/patients');
        $list->assertStatus(200)->assertJsonStructure(['data', 'meta']);

        $show = $this->actingAs($user, 'sanctum')->getJson("/api/v1/patients/{$patient->id}");
        $show->assertStatus(200)->assertJsonPath('data.id', $patient->id);
    }

    public function test_super_admin_can_update_patient(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/patients/{$patient->id}", [
            'name' => 'Budi Updated',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'name' => 'Budi Updated']);
    }

    public function test_patient_has_no_destroy_route(): void
    {
        $user = $this->superAdmin();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/patients/{$patient->id}");

        $response->assertStatus(405);
        $this->assertDatabaseHas('patients', ['id' => $patient->id]);
    }

    public function test_registration_staff_can_create_list_update_patients(): void
    {
        $user = $this->registrationStaff();

        $create = $this->actingAs($user, 'sanctum')->postJson('/api/v1/patients', [
            'name' => 'Siti Aminah',
            'dob' => '1988-03-20',
            'gender' => 'Female',
        ]);
        $create->assertStatus(201);

        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/patients');
        $list->assertStatus(200);

        $patient = Patient::first();
        $update = $this->actingAs($user, 'sanctum')->putJson("/api/v1/patients/{$patient->id}", [
            'gender' => 'Female',
        ]);
        $update->assertStatus(200);
    }

    public function test_regular_user_gets_403_for_patient_management(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/patients', [
            'name' => 'Unauthorized',
            'dob' => '1990-01-01',
            'gender' => 'Male',
        ]);

        $response->assertStatus(403);
    }

    public function test_doctor_role_cannot_create_patient(): void
    {
        $role = Role::findByName('Doctor');
        $user = User::factory()->create()->assignRole($role);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/patients', [
            'name' => 'Doctor Attempt',
            'dob' => '1990-01-01',
            'gender' => 'Male',
        ]);

        $response->assertStatus(403);
    }
}
