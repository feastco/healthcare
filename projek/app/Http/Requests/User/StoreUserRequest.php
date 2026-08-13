<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class StoreUserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
