<?php

namespace App\Http\Requests\Schedule;

use App\Http\Requests\BaseFormRequest;

class StoreScheduleRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
