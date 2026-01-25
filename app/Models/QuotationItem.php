<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id','product_id','description','qty','unit_price','discount_percent','discount_amount','line_total'
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'float',
        'discount_percent' => 'float',
        'discount_amount' => 'float',
        'line_total' => 'float',
    ];

    public function quotation(){ return $this->belongsTo(Quotation::class, 'quotation_id'); }
    public function product(){ return $this->belongsTo(Product::class, 'product_id'); }
}
