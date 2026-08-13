<?php

namespace App\Http\Requests\Patient;

use App\Http\Requests\BaseFormRequest;

class StorePatientRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'dob' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'string', 'max:20'],
        ];
    }
}
