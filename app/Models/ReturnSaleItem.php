<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class ReturnSaleItem extends Model implements AuditableContract
{
    use Auditable;
    protected $fillable = [
        'returnId','saleId','productId','purchaseId','customerId','qty'
    ];

    protected $auditExclude = [
        'updated_at'
    ];

    public function sale()
    {
        return $this->belongsTo(SaleProduct::class, 'saleId');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function purchase()
    {
        return $this->belongsTo(PurchaseProduct::class, 'purchaseId');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class, 'returnId');
    }
}
