<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleWebTest extends TestCase
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
            'doctor_id' => Doctor::factory()->create()->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login_when_accessing_schedule_list(): void
    {
        $this->get('/master-data/schedules')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_schedule_create_form(): void
    {
        $this->get('/master-data/schedules/create')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_storing_schedule(): void
    {
        $this->post('/master-data/schedules', $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_schedule_edit_form(): void
    {
        $this->get('/master-data/schedules/1/edit')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_updating_schedule(): void
    {
        $this->put('/master-data/schedules/1', $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_deleting_schedule(): void
    {
        $this->delete('/master-data/schedules/1', ['_token' => csrf_token()])->assertRedirect(route('login'));
    }

    public function test_doctor_cannot_access_schedule_list(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->get('/master-data/schedules')
            ->assertStatus(403);
    }

    public function test_cashier_cannot_access_schedule_list(): void
    {
        $this->actingAs($this->userWithRole('Cashier'))
            ->get('/master-data/schedules')
            ->assertStatus(403);
    }

    public function test_it_admin_cannot_access_schedule_list(): void
    {
        $this->actingAs($this->userWithRole('IT/Admin'))
            ->get('/master-data/schedules')
            ->assertStatus(403);
    }

    public function test_user_without_role_cannot_access_schedule_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/master-data/schedules')
            ->assertStatus(403);
    }

    public function test_unauthorized_role_cannot_create_schedule(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->post('/master-data/schedules', $this->validPayload())
            ->assertStatus(403);

        $this->assertDatabaseMissing('doctor_schedules', [
            'day_of_week' => 1,
            'start_time' => '08:00',
        ]);
    }

    public function test_unauthorized_role_cannot_update_schedule(): void
    {
        $schedule = DoctorSchedule::factory()->create(['day_of_week' => 1]);

        $this->actingAs($this->userWithRole('Cashier'))
            ->put("/master-data/schedules/{$schedule->id}", $this->validPayload([
                'day_of_week' => 3,
            ]))
            ->assertStatus(403);

        $this->assertDatabaseMissing('doctor_schedules', [
            'id' => $schedule->id,
            'day_of_week' => 3,
        ]);
    }

    public function test_unauthorized_role_cannot_delete_schedule(): void
    {
        $schedule = DoctorSchedule::factory()->create();

        $this->actingAs($this->userWithRole('Doctor'))
            ->delete("/master-data/schedules/{$schedule->id}", ['_token' => csrf_token()])
            ->assertStatus(403);

        $this->assertDatabaseHas('doctor_schedules', ['id' => $schedule->id]);
    }

    public function test_super_admin_can_access_schedule_list(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->superAdmin())->get('/master-data/schedules');

        $response->assertStatus(200);
        $response->assertSee('Schedules');
        $response->assertSee('Add Schedule');
    }

    public function test_registration_staff_can_access_schedule_list(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/master-data/schedules');

        $response->assertStatus(200);
        $response->assertSee('Schedules');
        $response->assertDontSee('Add Schedule');
    }

    public function test_super_admin_can_access_schedule_create_form(): void
    {
        $this->withoutVite();

        $doctor = Doctor::factory()->create(['name' => 'Schedule Form Doctor']);

        $response = $this->actingAs($this->superAdmin())->get('/master-data/schedules/create');

        $response->assertStatus(200);
        $response->assertSee('Add Schedule');
        $response->assertSee('name="doctor_id"', false);
        $response->assertSee('name="day_of_week"', false);
        $response->assertSee('name="start_time"', false);
        $response->assertSee('name="end_time"', false);
        $response->assertSee('Schedule Form Doctor');
    }

    public function test_registration_staff_cannot_access_schedule_create_form(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/master-data/schedules/create')
            ->assertStatus(403);
    }

    public function test_super_admin_can_access_schedule_edit_form(): void
    {
        $this->withoutVite();

        $doctor = Doctor::factory()->create(['name' => 'Edit Form Doctor']);
        $schedule = DoctorSchedule::factory()->create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 2,
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->get("/master-data/schedules/{$schedule->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Edit Schedule');
        $response->assertSee('Edit Form Doctor');
    }

    public function test_registration_staff_cannot_access_schedule_edit_form(): void
    {
        $schedule = DoctorSchedule::factory()->create();

        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get("/master-data/schedules/{$schedule->id}/edit")
            ->assertStatus(403);
    }

    public function test_super_admin_can_create_schedule(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post('/master-data/schedules', $this->validPayload());

        $response->assertRedirect(route('schedules.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('doctor_schedules', [
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);
    }

    public function test_registration_staff_cannot_create_schedule(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->post('/master-data/schedules', $this->validPayload())
            ->assertStatus(403);

        $this->assertDatabaseMissing('doctor_schedules', [
            'day_of_week' => 1,
            'start_time' => '08:00',
        ]);
    }

    public function test_super_admin_can_update_schedule(): void
    {
        $schedule = DoctorSchedule::factory()->create([
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($this->superAdmin())
            ->put("/master-data/schedules/{$schedule->id}", $this->validPayload([
                'doctor_id' => $schedule->doctor_id,
                'day_of_week' => 3,
                'start_time' => '13:00',
                'end_time' => '17:00',
            ]))
            ->assertRedirect(route('schedules.index'));

        $this->assertDatabaseHas('doctor_schedules', [
            'id' => $schedule->id,
            'day_of_week' => 3,
            'start_time' => '13:00',
            'end_time' => '17:00',
        ]);
    }

    public function test_registration_staff_cannot_update_schedule(): void
    {
        $schedule = DoctorSchedule::factory()->create(['day_of_week' => 1]);

        $this->actingAs($this->userWithRole('Registration Staff'))
            ->put("/master-data/schedules/{$schedule->id}", $this->validPayload([
                'doctor_id' => $schedule->doctor_id,
                'day_of_week' => 3,
            ]))
            ->assertStatus(403);

        $this->assertDatabaseHas('doctor_schedules', [
            'id' => $schedule->id,
            'day_of_week' => 1,
        ]);
    }

    public function test_super_admin_can_delete_schedule(): void
    {
        $schedule = DoctorSchedule::factory()->create();

        $this->actingAs($this->superAdmin())
            ->delete("/master-data/schedules/{$schedule->id}", ['_token' => csrf_token()])
            ->assertRedirect(route('schedules.index'));

        $this->assertDatabaseMissing('doctor_schedules', ['id' => $schedule->id]);

        $this->assertTrue(
            AuditLog::query()
                ->where('entity_id', $schedule->id)
                ->where('action', 'DELETE')
                ->exists()
        );
    }

    public function test_registration_staff_cannot_delete_schedule(): void
    {
        $schedule = DoctorSchedule::factory()->create();

        $this->actingAs($this->userWithRole('Registration Staff'))
            ->delete("/master-data/schedules/{$schedule->id}", ['_token' => csrf_token()])
            ->assertStatus(403);

        $this->assertDatabaseHas('doctor_schedules', ['id' => $schedule->id]);
    }

    public function test_create_schedule_shows_validation_errors(): void
    {
        $response = $this->from('/master-data/schedules/create')
            ->actingAs($this->superAdmin())
            ->post('/master-data/schedules', [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect('/master-data/schedules/create');
        $response->assertSessionHasErrors(['day_of_week', 'start_time', 'end_time']);
    }

    public function test_create_schedule_rejects_nonexistent_doctor(): void
    {
        $response = $this->from('/master-data/schedules/create')
            ->actingAs($this->superAdmin())
            ->post('/master-data/schedules', $this->validPayload([
                'doctor_id' => 999999,
            ]));

        $response->assertRedirect('/master-data/schedules/create');
        $response->assertSessionHasErrors('doctor_id');
    }

    public function test_create_schedule_rejects_invalid_day_of_week(): void
    {
        $response = $this->from('/master-data/schedules/create')
            ->actingAs($this->superAdmin())
            ->post('/master-data/schedules', $this->validPayload([
                'day_of_week' => 8,
            ]));

        $response->assertRedirect('/master-data/schedules/create');
        $response->assertSessionHasErrors('day_of_week');
    }

    public function test_create_schedule_rejects_end_before_start(): void
    {
        $response = $this->from('/master-data/schedules/create')
            ->actingAs($this->superAdmin())
            ->post('/master-data/schedules', $this->validPayload([
                'start_time' => '14:00',
                'end_time' => '12:00',
            ]));

        $response->assertRedirect('/master-data/schedules/create');
        $response->assertSessionHasErrors('end_time');
    }

    public function test_update_schedule_shows_validation_errors(): void
    {
        $schedule = DoctorSchedule::factory()->create();

        $response = $this->from("/master-data/schedules/{$schedule->id}/edit")
            ->actingAs($this->superAdmin())
            ->put("/master-data/schedules/{$schedule->id}", [
                '_token' => csrf_token(),
                'doctor_id' => $schedule->doctor_id,
                'day_of_week' => '',
            ]);

        $response->assertRedirect("/master-data/schedules/{$schedule->id}/edit");
        $response->assertSessionHasErrors('day_of_week');
    }

    public function test_audit_log_is_recorded_on_schedule_create(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/master-data/schedules', $this->validPayload());

        $schedule = DoctorSchedule::latest('id')->firstOrFail();

        $this->assertTrue(
            AuditLog::query()
                ->where('entity_id', $schedule->id)
                ->where('action', 'CREATE')
                ->exists()
        );
    }

    public function test_schedule_pages_do_not_expose_sensitive_material(): void
    {
        $this->withoutVite();

        $user = $this->superAdmin();

        $html = $this->actingAs($user)->get('/master-data/schedules')->getContent();

        $this->assertStringNotContainsString($user->password, $html);
        $this->assertStringNotContainsString('plain-text-token', $html);
    }

    public function test_unauthorized_role_has_no_schedule_nav_href(): void
    {
        $this->withoutVite();

        $html = $this->actingAs($this->userWithRole('Cashier'))
            ->get('/home')
            ->getContent();

        $this->assertStringNotContainsString('/master-data/schedules', $html);
    }

    public function test_schedule_not_found_returns_404(): void
    {
        $this->withoutVite();

        $this->actingAs($this->superAdmin())
            ->get('/master-data/schedules/999999/edit')
            ->assertStatus(404);
    }
}
