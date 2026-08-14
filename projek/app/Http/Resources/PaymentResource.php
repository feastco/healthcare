<?php

namespace App\Http\Resources;

class PaymentResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'cashier_id' => $this->cashier_id,
            'amount' => $this->amount,
            'paid_at' => $this->paid_at,
            'payment_method' => $this->payment_method->value,
            'created_at' => $this->created_at,
        ];
    }
}
