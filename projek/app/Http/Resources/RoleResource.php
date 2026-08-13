<?php

namespace App\Http\Resources;

class RoleResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => PermissionResource::collection($this->permissions),
        ];
    }
}
