<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'string', 'min:8'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
