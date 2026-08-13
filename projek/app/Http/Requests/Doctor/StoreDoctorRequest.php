<?php

namespace App\Http\Requests\Doctor;

use App\Http\Requests\BaseFormRequest;

class StoreDoctorRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
