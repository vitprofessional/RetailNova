<?php

namespace App\Models;

use App\Models\Concerns\ScopesByBusiness;

use Illuminate\Database\Eloquent\Model;

class ServiceInvoiceItem extends Model
{
    use ScopesByBusiness;
    protected $fillable = ['businessId', 
        'invoice_id',
        'service_name',
        'rate',
        'qty',
        'line_total'
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'line_total' => 'decimal:2',
        'qty' => 'integer'
    ];

    public function invoice()
    {
        return $this->belongsTo(ServiceInvoice::class, 'invoice_id');
    }
}
