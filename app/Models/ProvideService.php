<?php

namespace App\Models;

use App\Models\Concerns\ScopesByBusiness;

use Illuminate\Database\Eloquent\Model;

class ProvideService extends Model
{
    use ScopesByBusiness;
    protected $fillable = ['businessId', 
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
