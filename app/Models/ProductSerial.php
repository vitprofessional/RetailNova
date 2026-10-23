<?php

namespace App\Models;

use App\Models\Concerns\ScopesByBusiness;

use Illuminate\Database\Eloquent\Model;

class ProductSerial extends Model
{
    use ScopesByBusiness;
    protected $fillable = ['businessId', 
        'productId',
        'purchaseId',
        'serialNumber',
        'barCode',
        'saleId',
        'sold_at',
        'status',
    ];

    // keep default table name 'product_serials'

    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function purchase()
    {
        return $this->belongsTo(PurchaseProduct::class, 'purchaseId');
    }
}
