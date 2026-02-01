<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\ScopesByBusiness;

class Supplier extends Model
{
    use SoftDeletes, ScopesByBusiness;
    protected $fillable = [
        'name', 'mobile', 'mail', 'city', 'area', 'country', 'state', 'openingBalance', 'businessId'
    ];

    protected $casts = [
        'openingBalance' => 'integer',
    ];

    /**
     * Get the purchases for this supplier.
     */
    public function purchases()
    {
        return $this->hasMany(PurchaseProduct::class, 'supplier');
    }

    /**
     * Get the return items for this supplier.
     */
    public function returnItems()
    {
        return $this->hasMany(ReturnPurchaseItem::class, 'supplierId');
    }
}
