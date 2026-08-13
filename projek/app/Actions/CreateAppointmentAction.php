<?php

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentConflictException;
use App\Exceptions\AppointmentUnavailableException;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateAppointmentAction
{
    public function handle(
        int $patientId,
        int $doctorId,
        Carbon $startsAt,
        Carbon $endsAt,
    ): Appointment {
        return DB::transaction(function () use ($patientId, $doctorId, $startsAt, $endsAt) {
            $this->assertDoctorHasSchedule($doctorId, $startsAt, $endsAt);
            $this->assertNoOverlap($doctorId, $startsAt, $endsAt);

            return Appointment::create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AppointmentStatus::SCHEDULED,
            ]);
        });
    }

    private function assertDoctorHasSchedule(int $doctorId, Carbon $startsAt, Carbon $endsAt): void
    {
        $hasSchedule = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $startsAt->dayOfWeekIso)
            ->whereTime('start_time', '<=', $startsAt->format('H:i:s'))
            ->whereTime('end_time', '>=', $endsAt->format('H:i:s'))
            ->exists();

        if (! $hasSchedule) {
            throw new AppointmentUnavailableException('Doctor has no schedule covering the requested time.');
        }
    }

    private function assertNoOverlap(int $doctorId, Carbon $startsAt, Carbon $endsAt): void
    {
        $overlaps = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($overlaps) {
            throw new AppointmentConflictException('Doctor already has an overlapping appointment.');
        }
    }
}
