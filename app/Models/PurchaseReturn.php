<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = [
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
