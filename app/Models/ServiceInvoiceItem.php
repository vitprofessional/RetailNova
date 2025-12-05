<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceInvoiceItem extends Model
{
    protected $fillable = [
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
