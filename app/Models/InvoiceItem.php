<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'saleId', 'purchaseId', 'qty', 'salePrice', 'buyPrice', 
        'totalSale', 'totalPurchase', 'profitTotal', 'profitMargin'
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    /**
     * Get the sale that owns the invoice item.
     */
    public function sale()
    {
        return $this->belongsTo(SaleProduct::class, 'saleId');
    }

    /**
     * Get the purchase for the invoice item.
     */
    public function purchase()
    {
        return $this->belongsTo(PurchaseProduct::class, 'purchaseId');
    }
}
