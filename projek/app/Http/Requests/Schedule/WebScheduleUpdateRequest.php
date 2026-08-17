<?php

namespace App\Http\Requests\Schedule;

class WebScheduleUpdateRequest extends UpdateScheduleRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
        ]);
    }
}
