<?php

namespace Tests\Unit;

use App\Enums\InvoiceState;
use PHPUnit\Framework\TestCase;

class InvoiceStateTest extends TestCase
{
    public function test_enum_defines_all_invoice_states(): void
    {
        $this->assertSame(
            ['UNPAID', 'PARTIALLY_PAID', 'PAID'],
            array_column(InvoiceState::cases(), 'value')
        );
    }

    public function test_enum_is_string_backed(): void
    {
        $this->assertSame('UNPAID', InvoiceState::UNPAID->value);
        $this->assertSame('PARTIALLY_PAID', InvoiceState::PARTIALLY_PAID->value);
        $this->assertSame('PAID', InvoiceState::PAID->value);
    }
}
