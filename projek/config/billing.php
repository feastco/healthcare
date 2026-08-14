<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Synthetic Billing Amount
    |--------------------------------------------------------------------------
    |
    | Baseline synthetic portfolio value used as the amount of the single
    | InvoiceItem generated when an Appointment becomes COMPLETED
    | (CR-001, R-BIL-001a).
    |
    | This is SYNTHETIC PORTFOLIO VALUE only. It is NOT a real clinical
    | tariff, hospital pricing, doctor fee, or service tariff.
    |
    | The authoritative amount for the invoice generation workflow must
    | always be read from this configuration, never hardcoded in business
    | logic.
    |
    */
    'default_invoice_amount' => 100000.00,
];
