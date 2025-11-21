<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class PurchaseProduct extends Model implements AuditableContract
{
    use Auditable;
    protected $fillable = [
        'productName', 'supplier', 'invoice', 'qty', 'buyPrice', 'salePriceExVat', 
        'salePriceInVat', 'vatStatus', 'totalAmount', 'grandTotal', 'paidAmount', 
        'dueAmount', 'purchase_date', 'reference'
    ];

    protected $casts = [
        'qty' => 'integer',
        'buyPrice' => 'decimal:2',
        'salePriceExVat' => 'decimal:2',
        'salePriceInVat' => 'decimal:2',
        'profit' => 'decimal:2',
        'totalAmount' => 'decimal:2',
        'disAmount' => 'decimal:2',
        'grandTotal' => 'decimal:2',
        'paidAmount' => 'decimal:2',
        'dueAmount' => 'decimal:2',
    ];

    protected $auditExclude = [
        'updated_at'
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
