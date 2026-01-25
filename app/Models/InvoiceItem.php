<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class InvoiceItem extends Model implements AuditableContract
{
    use Auditable;
    protected $fillable = [
        'saleId', 'purchaseId', 'qty', 'warranty_days', 'salePrice', 'buyPrice', 
        'totalSale', 'totalPurchase', 'profitTotal', 'profitMargin', 'isBackorder'
    ];

    protected $casts = [
        'qty' => 'integer',
        'salePrice' => 'decimal:2',
        'buyPrice' => 'decimal:2',
        'totalSale' => 'decimal:2',
        'totalPurchase' => 'decimal:2',
        'profitTotal' => 'decimal:2',
        'profitMargin' => 'decimal:2',
        'isBackorder' => 'boolean',
    ];

    protected $auditExclude = [
        'updated_at'
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
