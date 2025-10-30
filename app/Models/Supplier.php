<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name', 'mobile', 'mail', 'city', 'area', 'country', 'state', 'accReceivable', 'accPayable'
    ];

    /**
     * Get the purchases for this supplier.
     */
    public function purchases()
    {
        return $this->hasMany(PurchaseProduct::class, 'supplier');
    }

    /**
     * Get the return items for this supplier.
     */
    public function returnItems()
    {
        return $this->hasMany(ReturnPurchaseItem::class, 'supplierId');
    }
}
