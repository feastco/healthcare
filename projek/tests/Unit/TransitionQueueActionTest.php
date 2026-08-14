<?php

namespace Tests\Unit;

use App\Actions\TransitionQueueAction;
use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentStatusTransitionException;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransitionQueueActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_enum_contains_exactly_the_seven_approved_states(): void
    {
        $states = array_map(fn (AppointmentStatus $status) => $status->value, AppointmentStatus::cases());

        $this->assertSame([
            'SCHEDULED',
            'CONFIRMED',
            'WAITING',
            'IN_PROGRESS',
            'COMPLETED',
            'CANCELLED',
            'NO_SHOW',
        ], $states);
    }

    public function test_all_valid_transitions_are_permitted(): void
    {
        $valid = [
            [AppointmentStatus::SCHEDULED, AppointmentStatus::CONFIRMED],
            [AppointmentStatus::SCHEDULED, AppointmentStatus::CANCELLED],
            [AppointmentStatus::CONFIRMED, AppointmentStatus::WAITING],
            [AppointmentStatus::CONFIRMED, AppointmentStatus::CANCELLED],
            [AppointmentStatus::CONFIRMED, AppointmentStatus::NO_SHOW],
            [AppointmentStatus::WAITING, AppointmentStatus::IN_PROGRESS],
            [AppointmentStatus::WAITING, AppointmentStatus::CANCELLED],
            [AppointmentStatus::IN_PROGRESS, AppointmentStatus::COMPLETED],
        ];

        foreach ($valid as [$from, $to]) {
            $this->assertTrue($from->canTransitionTo($to), "{$from->value} -> {$to->value}");
        }
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $validPairs = [
            'SCHEDULED' => ['CONFIRMED', 'CANCELLED'],
            'CONFIRMED' => ['WAITING', 'CANCELLED', 'NO_SHOW'],
            'WAITING' => ['IN_PROGRESS', 'CANCELLED'],
            'IN_PROGRESS' => ['COMPLETED'],
            'COMPLETED' => [],
            'CANCELLED' => [],
            'NO_SHOW' => [],
        ];

        foreach (AppointmentStatus::cases() as $from) {
            foreach (AppointmentStatus::cases() as $to) {
                $allowed = in_array($to->value, $validPairs[$from->value] ?? [], true);

                $this->assertSame(
                    $allowed,
                    $from->canTransitionTo($to),
                    "{$from->value} -> {$to->value}"
                );
            }
        }
    }

    public function test_same_status_transition_is_rejected(): void
    {
        foreach (AppointmentStatus::cases() as $status) {
            $this->assertFalse($status->canTransitionTo($status), $status->value);
        }
    }

    public function test_action_persists_valid_transition(): void
    {
        $appointment = $this->createAppointment(AppointmentStatus::SCHEDULED);

        $updated = app(TransitionQueueAction::class)->handle($appointment, AppointmentStatus::CONFIRMED);

        $this->assertSame(AppointmentStatus::CONFIRMED, $updated->status);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'CONFIRMED',
        ]);
    }

    public function test_action_rejects_invalid_transition(): void
    {
        $appointment = $this->createAppointment(AppointmentStatus::COMPLETED);

        $this->expectException(AppointmentStatusTransitionException::class);

        app(TransitionQueueAction::class)->handle($appointment, AppointmentStatus::CANCELLED);
    }

    private function createAppointment(AppointmentStatus $status): Appointment
    {
        return Appointment::factory()->create([
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'status' => $status,
        ]);
    }
}
