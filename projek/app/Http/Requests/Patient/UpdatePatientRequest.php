<?php

namespace App\Http\Requests\Patient;

use App\Http\Requests\BaseFormRequest;

class UpdatePatientRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'dob' => ['sometimes', 'date', 'before:today'],
            'gender' => ['sometimes', 'string', 'max:20'],
        ];
    }
}
