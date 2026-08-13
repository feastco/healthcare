<?php

namespace App\Http\Resources;

class DoctorResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'department_id' => $this->department_id,
            'name' => $this->name,
            'department' => $this->whenLoaded('department', fn () => new DepartmentResource($this->department)),
            'created_at' => $this->created_at,
        ];
    }
}
