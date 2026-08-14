<?php

namespace Database\Factories;

use App\Enums\InvoiceState;
use App\Models\Appointment;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'total_amount' => 100000.00,
            'status' => InvoiceState::UNPAID,
        ];
    }
}
