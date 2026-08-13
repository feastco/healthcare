<?php

namespace App\Http\Resources;

class PermissionResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ];
    }
}
