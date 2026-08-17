<?php

namespace App\Http\Resources;

class InvoiceResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'total_amount' => $this->total_amount,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
        ];
    }
}
