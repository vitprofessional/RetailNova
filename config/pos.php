<?php

return [
    // Hide the acknowledgement block for invoices where customer is "Walking Customer"
    'hide_ack_walkin' => env('POS_HIDE_ACK_WALKIN', true),

    // Hide signature boxes for "Walking Customer" invoices
    'hide_signatures_walkin' => env('POS_HIDE_SIGNATURES_WALKIN', true),

    // Default printer profile for invoice auto-printing: a4, thermal80, thermal58
    'default_invoice_printer' => env('POS_DEFAULT_INVOICE_PRINTER', 'thermal80'),
];
