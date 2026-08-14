<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'The amount must be greater than zero.',
        ];
    }
}
