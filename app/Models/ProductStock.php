<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class ProductStock extends Model implements AuditableContract
{
    use Auditable;
    protected $fillable = [
        'purchaseId', 'productId', 'currentStock'
    ];

    protected $casts = [
        'currentStock' => 'integer',
    ];

    protected $auditExclude = [
        'updated_at'
    ];

    /**
     * Get the product that owns the stock.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'productId', 'id');
    }
}
