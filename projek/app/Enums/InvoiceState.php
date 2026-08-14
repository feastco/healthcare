<?php

namespace App\Enums;

enum InvoiceState: string
{
    case UNPAID = 'UNPAID';
    case PARTIALLY_PAID = 'PARTIALLY_PAID';
    case PAID = 'PAID';
}
