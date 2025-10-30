<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseProduct extends Model
{
    protected $fillable = [
        'productName', 'supplier', 'invoice', 'qty', 'buyPrice', 'salePriceExVat', 
        'salePriceInVat', 'vatStatus', 'totalAmount', 'grandTotal', 'paidAmount', 
        'dueAmount', 'purchase_date', 'reference'
    ];

    /**
     * Get the product for this purchase.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'productName');
    }

    /**
     * Get the supplier for this purchase.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier');
    }

    /**
     * Get the returns for this purchase.
     */
    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class, 'purchaseId');
    }

    /**
     * Get the stock for this purchase.
     */
    public function stock()
    {
        return $this->hasOne(ProductStock::class, 'purchaseId');
    }
}
