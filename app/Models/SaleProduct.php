<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class SaleProduct extends Model implements AuditableContract
{
    use Auditable;
    protected $fillable = [
        'customerId', 'invoiceNo', 'totalAmount', 'paidAmount', 'dueAmount', 'saleDate'
    ];

    protected $auditExclude = [
        'updated_at'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'saleId');
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class, 'saleId');
    }
}
