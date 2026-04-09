<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetup extends Model
{
    protected $fillable = [
        'businessName','businessLocation','mobile','email','tinCert','invoiceFooter','website','facebook','twitter','youtube','linkedin','businessLogo',
        'businessType',
        'currencySymbol','currencyPosition','currencyNegParentheses',
        'hide_invoice_acknowledgement',
        'invoice_terms_enabled',
        'invoice_terms_text'
    ];

    protected $casts = [
        'currencyNegParentheses' => 'boolean',
        'hide_invoice_acknowledgement' => 'boolean',
        'invoice_terms_enabled' => 'boolean',
    ];
}
