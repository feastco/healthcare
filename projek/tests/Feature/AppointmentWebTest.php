<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppointmentWebTest extends TestCase
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

    private function doctorWithSchedule(Carbon $startsAt, string $startTime = '08:00', string $endTime = '17:00'): Doctor
    {
        $doctor = Doctor::factory()->create();

        DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => $startsAt->dayOfWeekIso,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        return $doctor;
    }

    private function validPayload(array $overrides = []): array
    {
        $startsAt = Carbon::createFromTime(9, 0)->addDays(5);

        return array_merge([
            '_token' => csrf_token(),
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $this->doctorWithSchedule($startsAt)->id,
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'ends_at' => $startsAt->copy()->addHour()->format('Y-m-d\TH:i'),
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login_when_accessing_appointment_list(): void
    {
        $this->get('/operations/appointments')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_appointment_create_form(): void
    {
        $this->get('/operations/appointments/create')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_storing_appointment(): void
    {
        $this->post('/operations/appointments', $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_appointment_detail(): void
    {
        $this->get('/operations/appointments/1')->assertRedirect(route('login'));
    }

    public function test_doctor_cannot_access_appointment_list(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->get('/operations/appointments')
            ->assertStatus(403);
    }

    public function test_cashier_cannot_access_appointment_list(): void
    {
        $this->actingAs($this->userWithRole('Cashier'))
            ->get('/operations/appointments')
            ->assertStatus(403);
    }

    public function test_it_admin_cannot_access_appointment_list(): void
    {
        $this->actingAs($this->userWithRole('IT/Admin'))
            ->get('/operations/appointments')
            ->assertStatus(403);
    }

    public function test_user_without_role_cannot_access_appointment_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/operations/appointments')
            ->assertStatus(403);
    }

    public function test_unauthorized_role_cannot_create_appointment(): void
    {
        $startsAt = Carbon::createFromTime(9, 0)->addDays(5);

        $this->actingAs($this->userWithRole('Doctor'))
            ->post('/operations/appointments', $this->validPayload([
                'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            ]))
            ->assertStatus(403);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_super_admin_can_access_appointment_list(): void
    {
        $this->withoutVite();

        Appointment::factory()->create();

        $response = $this->actingAs($this->superAdmin())->get('/operations/appointments');

        $response->assertStatus(200);
        $response->assertSee('Appointments');
        $response->assertSee('Add Appointment');
    }

    public function test_registration_staff_can_access_appointment_list(): void
    {
        $this->withoutVite();

        Appointment::factory()->create();

        $response = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/operations/appointments');

        $response->assertStatus(200);
        $response->assertSee('Appointments');
        $response->assertSee('Add Appointment');
    }

    public function test_appointment_list_shows_created_appointment(): void
    {
        $this->withoutVite();

        $appointment = Appointment::factory()->create();

        $response = $this->actingAs($this->superAdmin())->get('/operations/appointments');

        $response->assertStatus(200);
        $response->assertSee($appointment->patient->name);
        $response->assertSee($appointment->doctor->name);
    }

    public function test_appointment_list_shows_empty_state(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->superAdmin())->get('/operations/appointments');

        $response->assertStatus(200);
        $response->assertSee('No appointments yet.');
    }

    public function test_super_admin_can_access_appointment_create_form(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->superAdmin())->get('/operations/appointments/create');

        $response->assertStatus(200);
        $response->assertSee('Create Appointment');
        $response->assertSee('name="patient_id"', false);
        $response->assertSee('name="doctor_id"', false);
        $response->assertSee('name="starts_at"', false);
        $response->assertSee('name="ends_at"', false);
    }

    public function test_registration_staff_can_access_appointment_create_form(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/operations/appointments/create');

        $response->assertStatus(200);
        $response->assertSee('Create Appointment');
    }

    public function test_create_form_shows_weekly_schedule_guide(): void
    {
        $this->withoutVite();

        $startsAt = Carbon::createFromTime(9, 0)->addDays(5);
        $doctor = $this->doctorWithSchedule($startsAt);

        $response = $this->actingAs($this->superAdmin())->get('/operations/appointments/create');

        $response->assertStatus(200);
        $response->assertSee('Weekly schedule');
        $response->assertSee('reference only');
        $response->assertSee('No weekly schedule registered for this doctor.', false);
    }

    public function test_super_admin_can_create_appointment(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/operations/appointments', $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseCount('appointments', 1);

        $appointment = Appointment::latest('id')->firstOrFail();
        $this->assertSame('SCHEDULED', $appointment->status->value);
    }

    public function test_registration_staff_can_create_appointment(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->post('/operations/appointments', $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_create_appointment_shows_validation_errors(): void
    {
        $response = $this->from('/operations/appointments/create')
            ->actingAs($this->superAdmin())
            ->post('/operations/appointments', [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect('/operations/appointments/create');
        $response->assertSessionHasErrors(['patient_id', 'doctor_id', 'starts_at', 'ends_at']);
    }

    public function test_create_appointment_rejects_invalid_patient(): void
    {
        $startsAt = Carbon::createFromTime(9, 0)->addDays(5);

        $response = $this->from('/operations/appointments/create')
            ->actingAs($this->superAdmin())
            ->post('/operations/appointments', $this->validPayload([
                'patient_id' => 999999,
                'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            ]));

        $response->assertRedirect('/operations/appointments/create');
        $response->assertSessionHasErrors('patient_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_create_appointment_rejects_invalid_doctor(): void
    {
        $startsAt = Carbon::createFromTime(9, 0)->addDays(5);

        $response = $this->from('/operations/appointments/create')
            ->actingAs($this->superAdmin())
            ->post('/operations/appointments', $this->validPayload([
                'doctor_id' => 999999,
                'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            ]));

        $response->assertRedirect('/operations/appointments/create');
        $response->assertSessionHasErrors('doctor_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_create_appointment_rejects_ends_before_starts(): void
    {
        $startsAt = Carbon::createFromTime(9, 0)->addDays(5);

        $response = $this->from('/operations/appointments/create')
            ->actingAs($this->superAdmin())
            ->post('/operations/appointments', $this->validPayload([
                'ends_at' => $startsAt->subHour()->format('Y-m-d\TH:i'),
            ]));

        $response->assertRedirect('/operations/appointments/create');
        $response->assertSessionHasErrors('ends_at');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_create_appointment_redirects_back_when_doctor_not_scheduled(): void
    {
        $startsAt = Carbon::createFromTime(9, 0)->addDays(5);

        $response = $this->from('/operations/appointments/create')
            ->actingAs($this->superAdmin())
            ->post('/operations/appointments', $this->validPayload([
                'doctor_id' => Doctor::factory()->create()->id,
                'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            ]));

        $response->assertRedirect('/operations/appointments/create');
        $response->assertSessionHasErrors('starts_at');
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_create_appointment_redirects_back_on_overlapping_appointment(): void
    {
        $startsAt = Carbon::createFromTime(9, 0)->addDays(5);
        $doctor = $this->doctorWithSchedule($startsAt);

        Appointment::create([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'status' => 'SCHEDULED',
        ]);

        $response = $this->from('/operations/appointments/create')
            ->actingAs($this->superAdmin())
            ->post('/operations/appointments', $this->validPayload([
                'doctor_id' => $doctor->id,
                'starts_at' => $startsAt->addMinutes(30)->format('Y-m-d\TH:i'),
                'ends_at' => $startsAt->addMinutes(30)->addHour()->format('Y-m-d\TH:i'),
            ]));

        $response->assertRedirect('/operations/appointments/create');
        $response->assertSessionHasErrors('starts_at');
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_super_admin_can_view_appointment_detail(): void
    {
        $this->withoutVite();

        $appointment = Appointment::factory()->create();

        $response = $this->actingAs($this->superAdmin())
            ->get("/operations/appointments/{$appointment->id}");

        $response->assertStatus(200);
        $response->assertSee('Appointment Detail');
        $response->assertSee($appointment->patient->name);
        $response->assertSee($appointment->doctor->name);
        $response->assertSee('Scheduled', false);
    }

    public function test_registration_staff_can_view_appointment_detail(): void
    {
        $this->withoutVite();

        $appointment = Appointment::factory()->create();

        $response = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get("/operations/appointments/{$appointment->id}");

        $response->assertStatus(200);
        $response->assertSee($appointment->patient->name);
    }

    public function test_unauthorized_role_cannot_view_appointment_detail(): void
    {
        $appointment = Appointment::factory()->create();

        $this->actingAs($this->userWithRole('Cashier'))
            ->get("/operations/appointments/{$appointment->id}")
            ->assertStatus(403);
    }

    public function test_appointment_not_found_returns_404(): void
    {
        $this->withoutVite();

        $this->actingAs($this->superAdmin())
            ->get('/operations/appointments/999999')
            ->assertStatus(404);
    }

    public function test_audit_log_is_recorded_on_appointment_create(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/operations/appointments', $this->validPayload());

        $appointment = Appointment::latest('id')->firstOrFail();

        $this->assertTrue(
            AuditLog::query()
                ->where('entity_id', $appointment->id)
                ->where('action', 'CREATE')
                ->exists()
        );
    }

    public function test_appointment_pages_do_not_expose_sensitive_material(): void
    {
        $this->withoutVite();

        $user = $this->superAdmin();
        $appointment = Appointment::factory()->create();

        $html = $this->actingAs($user)->get("/operations/appointments/{$appointment->id}")->getContent();

        $this->assertStringNotContainsString($user->password, $html);
        $this->assertStringNotContainsString('plain-text-token', $html);
    }

    public function test_unauthorized_role_has_no_appointment_nav_href(): void
    {
        $this->withoutVite();

        $html = $this->actingAs($this->userWithRole('Doctor'))
            ->get('/home')
            ->getContent();

        $this->assertStringNotContainsString('/operations/appointments', $html);
    }

    public function test_no_edit_route_for_appointments(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/operations/appointments/1/edit')
            ->assertStatus(404);
    }

    public function test_no_update_route_for_appointments(): void
    {
        $this->actingAs($this->superAdmin())
            ->put('/operations/appointments/1', $this->validPayload())
            ->assertStatus(405);
    }

    public function test_no_delete_route_for_appointments(): void
    {
        $this->actingAs($this->superAdmin())
            ->delete('/operations/appointments/1', ['_token' => csrf_token()])
            ->assertStatus(405);
    }
}
