<?php

namespace Tests\Unit;

use App\Enums\PaymentMethod;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_enum_defines_all_payment_methods(): void
    {
        $this->assertSame(
            ['CASH', 'TRANSFER', 'CARD'],
            array_column(PaymentMethod::cases(), 'value')
        );
    }

    public function test_enum_is_string_backed(): void
    {
        $this->assertSame('CASH', PaymentMethod::CASH->value);
        $this->assertSame('TRANSFER', PaymentMethod::TRANSFER->value);
        $this->assertSame('CARD', PaymentMethod::CARD->value);
    }
}
