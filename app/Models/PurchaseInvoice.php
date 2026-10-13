<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    protected $guarded = [];
    protected $casts = ['purchase_date'=>'date','total_amount'=>'decimal:2','discount_amount'=>'decimal:2',
        'grand_total'=>'decimal:2','paid_amount'=>'decimal:2','due_amount'=>'decimal:2'];

    public function items() { return $this->hasMany(PurchaseProduct::class,'purchase_invoice_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class,'supplier_id'); }
}
