<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'quote_number', 'customer_id', 'date', 'validity_days', 'status',
        'notes', 'terms_text', 'subtotal', 'discount_total', 'grand_total'
    ];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'float',
        'discount_total' => 'float',
        'grand_total' => 'float',
    ];

    public function customer(){ return $this->belongsTo(Customer::class, 'customer_id'); }
    public function items(){ return $this->hasMany(QuotationItem::class, 'quotation_id'); }
}
