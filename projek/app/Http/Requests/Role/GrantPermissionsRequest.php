<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseFormRequest;

class GrantPermissionsRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'integer', 'exists:permissions,id'],
        ];
    }
}
