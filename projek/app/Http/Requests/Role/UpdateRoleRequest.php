<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $roleId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($roleId)],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', 'integer', 'exists:permissions,id'],
        ];
    }
}
