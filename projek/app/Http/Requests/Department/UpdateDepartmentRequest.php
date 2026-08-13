<?php

namespace App\Http\Requests\Department;

use App\Http\Requests\BaseFormRequest;

class UpdateDepartmentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
