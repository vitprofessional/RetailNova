<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Product extends Model implements AuditableContract
{
    use Auditable;
    protected $fillable = [
        'name', 'brand', 'category', 'unitName', 'quantity', 'details', 'barCode'
    ];

    protected $auditExclude = [
        'updated_at'
    ];

    /**
     * Get the stock records for the product.
     */
    public function stocks()
    {
        return $this->hasMany(ProductStock::class, 'productId', 'id');
    }

    // Eager-loadable relationships for brand, category, and unit
    public function brandModel()
    {
        return $this->belongsTo(Brand::class, 'brand');
    }

    public function categoryModel()
    {
        return $this->belongsTo(Category::class, 'category');
    }

    public function unitModel()
    {
        return $this->belongsTo(ProductUnit::class, 'unitName');
    }

    /**
     * Get the total current stock for the product.
     */
    public function getTotalStockAttribute()
    {
        return $this->stocks()->sum('currentStock') ?? 0;
    }

    /**
     * Check if the product is out of stock.
     */
    public function getIsOutOfStockAttribute()
    {
        return $this->total_stock <= 0;
    }

    /**
     * Check if the product is low in stock.
     */
    public function getIsLowStockAttribute()
    {
        $alertQuantity = $this->quantity ?? 0;
        return $this->total_stock < $alertQuantity && $this->total_stock > 0;
    }

    /**
     * Get stock status string.
     */
    public function getStockStatusAttribute()
    {
        if ($this->is_out_of_stock) {
            return 'Out of Stock';
        } elseif ($this->is_low_stock) {
            return 'Low Stock';
        } else {
            return 'In Stock';
        }
    }
}
