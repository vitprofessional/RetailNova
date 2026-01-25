<?php

return [
    // Hide the acknowledgement block for invoices where customer is "Walking Customer"
    'hide_ack_walkin' => env('POS_HIDE_ACK_WALKIN', true),

    // Hide signature boxes for "Walking Customer" invoices
    'hide_signatures_walkin' => env('POS_HIDE_SIGNATURES_WALKIN', true),
];
