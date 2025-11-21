<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class SaleReturn extends Model implements AuditableContract
{
    use Auditable;
    protected $fillable = [
        'saleId','totalReturnAmount','adjustAmount'
    ];

    protected $auditExclude = [
        'updated_at'
    ];

    public function sale()
    {
        return $this->belongsTo(SaleProduct::class, 'saleId');
    }

    public function items()
    {
        return $this->hasMany(ReturnSaleItem::class, 'returnId');
    }
}
