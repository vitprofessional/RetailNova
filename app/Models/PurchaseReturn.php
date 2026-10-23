<?php

namespace App\Models;

use App\Models\Concerns\ScopesByBusiness;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use ScopesByBusiness;
    protected $fillable = ['businessId', 
        'purchaseId', 'totalReturnAmount', 'adjustAmount'
    ];

    /**
     * Get the return items for the purchase return.
     */
    public function returnItems()
    {
        return $this->hasMany(ReturnPurchaseItem::class, 'returnId');
    }

    /**
     * Get the purchase that was returned.
     */
    public function purchase()
    {
        return $this->belongsTo(PurchaseProduct::class, 'purchaseId');
    }
}
