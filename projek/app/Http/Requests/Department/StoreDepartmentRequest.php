<?php

namespace App\Http\Requests\Department;

use App\Http\Requests\BaseFormRequest;

class StoreDepartmentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
