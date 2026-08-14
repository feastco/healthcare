<?php

namespace App\Http\Resources;

class AuditLogResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'before_state' => $this->before_state,
            'after_state' => $this->after_state,
            'created_at' => $this->created_at,
        ];
    }
}
