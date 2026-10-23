<?php

namespace App\Models;

use App\Models\Concerns\ScopesByBusiness;

use Illuminate\Database\Eloquent\Model;

class ReturnPurchaseItem extends Model
{
    use ScopesByBusiness;
    protected $fillable = ['businessId', 
        'returnId', 'purchaseId', 'supplierId', 'qty', 'productId'
    ];

    /**
     * Get the purchase return this item belongs to.
     */
    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'returnId');
    }

    /**
     * Get the product for this return item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    /**
     * Get the supplier for this return item.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }
}
