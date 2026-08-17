<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyQueueWebTest extends TestCase
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

    private function doctorUser(): User
    {
        $user = User::factory()->create()->assignRole('Doctor');

        Doctor::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function doctorFor(User $user): Doctor
    {
        return Doctor::where('user_id', $user->id)->firstOrFail();
    }

    private function queueAppointment(Doctor $doctor, AppointmentStatus $status, int $hour = 9): Appointment
    {
        return Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'starts_at' => now()->today()->setHour($hour)->setMinute(0)->setSecond(0),
            'ends_at' => now()->today()->setHour($hour)->setMinute(0)->setSecond(0)->addHour(),
            'status' => $status,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_my_queue(): void
    {
        $this->get('/operations/my-queue')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_updating_queue_status(): void
    {
        $this->post('/operations/my-queue/1/status', [
            '_token' => csrf_token(),
            'status' => AppointmentStatus::IN_PROGRESS->value,
        ])->assertRedirect(route('login'));
    }

    public function test_registration_staff_cannot_access_my_queue(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/operations/my-queue')
            ->assertStatus(403);
    }

    public function test_cashier_cannot_access_my_queue(): void
    {
        $this->actingAs($this->userWithRole('Cashier'))
            ->get('/operations/my-queue')
            ->assertStatus(403);
    }

    public function test_it_admin_cannot_access_my_queue(): void
    {
        $this->actingAs($this->userWithRole('IT/Admin'))
            ->get('/operations/my-queue')
            ->assertStatus(403);
    }

    public function test_user_without_role_cannot_access_my_queue(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/operations/my-queue')
            ->assertStatus(403);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create()->assignRole($role);
    }

    public function test_super_admin_can_access_my_queue(): void
    {
        $this->withoutVite();

        $this->queueAppointment(Doctor::factory()->create(), AppointmentStatus::WAITING);

        $response = $this->actingAs($this->superAdmin())->get('/operations/my-queue');

        $response->assertStatus(200);
        $response->assertSee('My Queue');
    }

    public function test_doctor_can_access_my_queue(): void
    {
        $this->withoutVite();

        $doctorUser = $this->doctorUser();

        $this->queueAppointment($this->doctorFor($doctorUser), AppointmentStatus::WAITING);

        $response = $this->actingAs($doctorUser)->get('/operations/my-queue');

        $response->assertStatus(200);
        $response->assertSee('My Queue');
    }

    public function test_doctor_sees_only_own_appointments(): void
    {
        $this->withoutVite();

        $doctorUser = $this->doctorUser();
        $otherDoctor = Doctor::factory()->create();

        $own = $this->queueAppointment($this->doctorFor($doctorUser), AppointmentStatus::WAITING);
        $other = $this->queueAppointment($otherDoctor, AppointmentStatus::WAITING);

        $response = $this->actingAs($doctorUser)->get('/operations/my-queue');

        $response->assertStatus(200);
        $response->assertSee($own->patient->name);
        $response->assertDontSee($other->patient->name);
    }

    public function test_super_admin_sees_appointments_from_all_doctors(): void
    {
        $this->withoutVite();

        $first = $this->queueAppointment(Doctor::factory()->create(), AppointmentStatus::WAITING);
        $second = $this->queueAppointment(Doctor::factory()->create(), AppointmentStatus::IN_PROGRESS);

        $response = $this->actingAs($this->superAdmin())->get('/operations/my-queue');

        $response->assertStatus(200);
        $response->assertSee($first->patient->name);
        $response->assertSee($second->patient->name);
    }

    public function test_queue_only_shows_today_waiting_and_in_progress(): void
    {
        $this->withoutVite();

        $doctorUser = $this->doctorUser();
        $doctor = $this->doctorFor($doctorUser);

        $waiting = $this->queueAppointment($doctor, AppointmentStatus::WAITING, 9);
        $inProgress = $this->queueAppointment($doctor, AppointmentStatus::IN_PROGRESS, 10);
        $scheduled = $this->queueAppointment($doctor, AppointmentStatus::SCHEDULED, 11);
        $completed = $this->queueAppointment($doctor, AppointmentStatus::COMPLETED, 13);

        $tomorrow = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDay()->startOfDay()->setHour(9)->setMinute(0)->setSecond(0),
            'ends_at' => now()->addDay()->startOfDay()->setHour(9)->setMinute(0)->setSecond(0)->addHour(),
            'status' => AppointmentStatus::WAITING,
        ]);

        $response = $this->actingAs($doctorUser)->get('/operations/my-queue');

        $response->assertStatus(200);
        $response->assertSee($waiting->patient->name);
        $response->assertSee($inProgress->patient->name);
        $response->assertDontSee($scheduled->patient->name);
        $response->assertDontSee($completed->patient->name);
        $response->assertDontSee($tomorrow->patient->name);
    }

    public function test_my_queue_shows_empty_state(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->doctorUser())->get('/operations/my-queue');

        $response->assertStatus(200);
        $response->assertSee('No patients in your queue today.');
    }

    public function test_doctor_without_doctor_profile_sees_empty_queue(): void
    {
        $this->withoutVite();

        $user = $this->userWithRole('Doctor');

        $response = $this->actingAs($user)->get('/operations/my-queue');

        $response->assertStatus(200);
        $response->assertSee('No patients in your queue today.');
    }

    public function test_doctor_sees_start_button_for_waiting_appointment(): void
    {
        $this->withoutVite();

        $doctorUser = $this->doctorUser();

        $appointment = $this->queueAppointment($this->doctorFor($doctorUser), AppointmentStatus::WAITING);

        $response = $this->actingAs($doctorUser)->get('/operations/my-queue');

        $response->assertStatus(200);
        $response->assertSee("/operations/my-queue/{$appointment->id}/status", false);
    }

    public function test_doctor_sees_finish_button_for_in_progress_appointment(): void
    {
        $this->withoutVite();

        $doctorUser = $this->doctorUser();

        $appointment = $this->queueAppointment($this->doctorFor($doctorUser), AppointmentStatus::IN_PROGRESS);

        $response = $this->actingAs($doctorUser)->get('/operations/my-queue');

        $response->assertStatus(200);
        $response->assertSee("/operations/my-queue/{$appointment->id}/status", false);
    }

    public function test_super_admin_does_not_see_mutation_buttons(): void
    {
        $this->withoutVite();

        $appointment = $this->queueAppointment(Doctor::factory()->create(), AppointmentStatus::WAITING);

        $response = $this->actingAs($this->superAdmin())->get('/operations/my-queue');

        $response->assertStatus(200);
        $response->assertDontSee("/operations/my-queue/{$appointment->id}/status", false);
    }

    public function test_doctor_can_start_own_waiting_appointment(): void
    {
        $doctorUser = $this->doctorUser();
        $appointment = $this->queueAppointment($this->doctorFor($doctorUser), AppointmentStatus::WAITING);

        $response = $this->from('/operations/my-queue')
            ->actingAs($doctorUser)
            ->post("/operations/my-queue/{$appointment->id}/status", [
                '_token' => csrf_token(),
                'status' => AppointmentStatus::IN_PROGRESS->value,
            ]);

        $response->assertRedirect('/operations/my-queue');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::IN_PROGRESS->value,
        ]);

        $this->assertTrue(
            AuditLog::query()
                ->where('entity_id', $appointment->id)
                ->where('action', 'UPDATE')
                ->exists()
        );
    }

    public function test_doctor_can_finish_own_in_progress_appointment(): void
    {
        $doctorUser = $this->doctorUser();
        $appointment = $this->queueAppointment($this->doctorFor($doctorUser), AppointmentStatus::IN_PROGRESS);

        $response = $this->from('/operations/my-queue')
            ->actingAs($doctorUser)
            ->post("/operations/my-queue/{$appointment->id}/status", [
                '_token' => csrf_token(),
                'status' => AppointmentStatus::COMPLETED->value,
            ]);

        $response->assertRedirect('/operations/my-queue');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::COMPLETED->value,
        ]);

        $this->assertDatabaseHas('invoices', [
            'appointment_id' => $appointment->id,
        ]);

        $this->assertTrue(
            AuditLog::query()
                ->where('entity_id', $appointment->id)
                ->where('action', 'UPDATE')
                ->exists()
        );
    }

    public function test_doctor_cannot_update_other_doctors_appointment(): void
    {
        $doctorUser = $this->doctorUser();
        $otherDoctor = Doctor::factory()->create();

        $appointment = $this->queueAppointment($otherDoctor, AppointmentStatus::WAITING);

        $this->actingAs($doctorUser)
            ->post("/operations/my-queue/{$appointment->id}/status", [
                '_token' => csrf_token(),
                'status' => AppointmentStatus::IN_PROGRESS->value,
            ])
            ->assertStatus(403);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::WAITING->value,
        ]);
    }

    public function test_super_admin_cannot_mutate_queue_status(): void
    {
        $appointment = $this->queueAppointment(Doctor::factory()->create(), AppointmentStatus::WAITING);

        $this->actingAs($this->superAdmin())
            ->post("/operations/my-queue/{$appointment->id}/status", [
                '_token' => csrf_token(),
                'status' => AppointmentStatus::IN_PROGRESS->value,
            ])
            ->assertStatus(403);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::WAITING->value,
        ]);
    }

    public function test_doctor_cannot_skip_transition(): void
    {
        $doctorUser = $this->doctorUser();
        $appointment = $this->queueAppointment($this->doctorFor($doctorUser), AppointmentStatus::WAITING);

        $this->actingAs($doctorUser)
            ->post("/operations/my-queue/{$appointment->id}/status", [
                '_token' => csrf_token(),
                'status' => AppointmentStatus::COMPLETED->value,
            ])
            ->assertStatus(403);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::WAITING->value,
        ]);
    }

    public function test_invalid_status_value_is_rejected(): void
    {
        $doctorUser = $this->doctorUser();
        $appointment = $this->queueAppointment($this->doctorFor($doctorUser), AppointmentStatus::WAITING);

        $response = $this->from('/operations/my-queue')
            ->actingAs($doctorUser)
            ->post("/operations/my-queue/{$appointment->id}/status", [
                '_token' => csrf_token(),
                'status' => 'NOT_A_STATUS',
            ]);

        $response->assertRedirect('/operations/my-queue');
        $response->assertSessionHasErrors('status');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::WAITING->value,
        ]);
    }

    public function test_status_update_on_missing_appointment_returns_404(): void
    {
        $this->actingAs($this->doctorUser())
            ->post('/operations/my-queue/999999/status', [
                '_token' => csrf_token(),
                'status' => AppointmentStatus::IN_PROGRESS->value,
            ])
            ->assertStatus(404);
    }

    public function test_my_queue_pages_do_not_expose_sensitive_material(): void
    {
        $this->withoutVite();

        $doctorUser = $this->doctorUser();
        $this->queueAppointment($this->doctorFor($doctorUser), AppointmentStatus::WAITING);

        $html = $this->actingAs($doctorUser)->get('/operations/my-queue')->getContent();

        $this->assertStringNotContainsString($doctorUser->password, $html);
        $this->assertStringNotContainsString('plain-text-token', $html);
    }

    public function test_unauthorized_role_has_no_my_queue_nav_href(): void
    {
        $this->withoutVite();

        $html = $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/home')
            ->getContent();

        $this->assertStringNotContainsString('/operations/my-queue', $html);
    }

    public function test_no_get_status_route_for_my_queue(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/operations/my-queue/1/status')
            ->assertStatus(405);
    }
}
