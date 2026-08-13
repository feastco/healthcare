<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', 'integer', 'exists:permissions,id'],
        ];
    }
}
