<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProvideService extends Model
{
    protected $fillable = [
        'customerName',
        'serviceName',
        'amount',
        'note',
        'qty',
        'rate'
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'qty' => 'integer',
    ];
}
