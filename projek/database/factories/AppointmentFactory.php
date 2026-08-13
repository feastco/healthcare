<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = Carbon::createFromTime(8, 0)->addDays(fake()->numberBetween(1, 30));

        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'status' => AppointmentStatus::SCHEDULED,
        ];
    }
}
