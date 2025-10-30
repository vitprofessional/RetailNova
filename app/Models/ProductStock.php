<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    protected $fillable = [
        'purchaseId', 'productId', 'currentStock'
    ];

    protected $casts = [
        'currentStock' => 'integer',
    ];

    /**
     * Get the product that owns the stock.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'productId', 'id');
    }
}
