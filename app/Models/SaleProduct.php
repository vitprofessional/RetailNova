<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

use App\Models\Concerns\ScopesByBusiness;

/**
 * A cancelled sale is soft-deleted (see App\Services\SaleCancellation) and keeps
 * cancel_reason, cancelled_by and cancelled_at for history.
 */
class SaleProduct extends Model implements AuditableContract
{
    use Auditable, ScopesByBusiness, SoftDeletes;
    protected $fillable = [
        'customerId', 'invoiceNo', 'totalAmount', 'paidAmount', 'dueAmount', 'saleDate', 'businessId', 'salespersonId', 'totalVat'
    ];

    protected $casts = [
        'totalVat' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    protected $auditExclude = [
        'updated_at'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    public function salesperson()
    {
        return $this->belongsTo(\App\Models\AdminUser::class, 'salespersonId');
    }

    public function business()
    {
        return $this->belongsTo(\App\Models\BusinessSetup::class, 'businessId');
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
