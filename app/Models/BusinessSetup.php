<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetup extends Model
{
    protected $fillable = [
        'businessName','businessLocation','mobile','email','tinCert','invoiceFooter','website','facebook','twitter','youtube','linkedin','businessLogo',
        'currencySymbol','currencyPosition','currencyNegParentheses'
    ];

    protected $casts = [
        'currencyNegParentheses' => 'boolean',
    ];
}
