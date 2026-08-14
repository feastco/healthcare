<?php

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentStatusTransitionException;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class TransitionQueueAction
{
    private const TRANSITION_ROLES = [
        AppointmentStatus::SCHEDULED->value => [
            AppointmentStatus::CONFIRMED->value => ['Registration Staff'],
            AppointmentStatus::CANCELLED->value => ['Registration Staff'],
        ],
        AppointmentStatus::CONFIRMED->value => [
            AppointmentStatus::WAITING->value => ['Registration Staff'],
            AppointmentStatus::CANCELLED->value => ['Registration Staff'],
            AppointmentStatus::NO_SHOW->value => ['Registration Staff'],
        ],
        AppointmentStatus::WAITING->value => [
            AppointmentStatus::IN_PROGRESS->value => ['Doctor'],
            AppointmentStatus::CANCELLED->value => ['Registration Staff'],
        ],
        AppointmentStatus::IN_PROGRESS->value => [
            AppointmentStatus::COMPLETED->value => ['Doctor'],
        ],
    ];

    public static function rolesFor(AppointmentStatus $from, AppointmentStatus $to): array
    {
        return self::TRANSITION_ROLES[$from->value][$to->value] ?? [];
    }

    public function handle(Appointment $appointment, AppointmentStatus $target): Appointment
    {
        if (! $appointment->status->canTransitionTo($target)) {
            throw new AppointmentStatusTransitionException(
                sprintf(
                    'Appointment status cannot transition from %s to %s.',
                    $appointment->status->value,
                    $target->value
                )
            );
        }

        DB::transaction(function () use ($appointment, $target) {
            $appointment->update(['status' => $target]);

            if ($target === AppointmentStatus::COMPLETED) {
                app(GenerateInvoiceAction::class)->handle($appointment);
            }
        });

        return $appointment->refresh();
    }
}
