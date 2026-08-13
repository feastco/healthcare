<?php

namespace App\Http\Requests\Schedule;

use App\Http\Requests\BaseFormRequest;

class UpdateScheduleRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'day_of_week' => ['sometimes', 'integer', 'between:1,7'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
