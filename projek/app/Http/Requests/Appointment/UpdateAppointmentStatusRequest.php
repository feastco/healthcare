<?php

namespace App\Http\Requests\Appointment;

use App\Enums\AppointmentStatus;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentStatusRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(AppointmentStatus::class)],
        ];
    }
}