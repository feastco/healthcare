<?php

namespace App\Http\Requests\Doctor;

use App\Http\Requests\BaseFormRequest;

class UpdateDoctorRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
