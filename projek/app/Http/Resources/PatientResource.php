<?php

namespace App\Http\Resources;

class PatientResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'identifier_pat' => $this->identifier_pat,
            'name' => $this->name,
            'dob' => $this->dob,
            'gender' => $this->gender,
            'created_at' => $this->created_at,
        ];
    }
}
