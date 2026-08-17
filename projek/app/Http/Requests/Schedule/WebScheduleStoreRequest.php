<?php

namespace App\Http\Requests\Schedule;

class WebScheduleStoreRequest extends StoreScheduleRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
        ]);
    }
}
