<?php

namespace App\Models;

use App\Models\Concerns\ScopesByBusiness;

use Illuminate\Database\Eloquent\Model;

class ServiceInvoice extends Model
{
    use ScopesByBusiness;
    protected $fillable = ['businessId', 
        'invoice_number',
        'customer_id',
        'is_walkin',
        'total_amount',
        'note'
    ];

    protected $casts = [
        'is_walkin' => 'boolean',
        'total_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(ServiceInvoiceItem::class, 'invoice_id');
    }
}
