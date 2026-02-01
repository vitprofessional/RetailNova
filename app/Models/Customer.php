<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\ScopesByBusiness;

class Customer extends Model
{
    use SoftDeletes, ScopesByBusiness;
    protected $fillable = [
        'name', 'businessId',
        'openingBalance',
        'mail',
        'mobile',
        'country',
        'state',
        'city',
        'area',
    ];

    protected $casts = [
        'openingBalance'=> 'integer',
    ];

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([$this->area, $this->city, $this->state, $this->country]);
        return implode(', ', $parts);
    }

    /**
     * Ensure a default "Walking Customer" exists and return it.
     * If a soft-deleted record exists, restore it.
     */
    public static function ensureWalkingCustomer(): Customer
    {
        $name = 'Walking Customer';
        $existing = static::withTrashed()->where('name', $name)->first();
        if ($existing) {
            if (method_exists($existing, 'trashed') && $existing->trashed()) {
                $existing->restore();
            }
            return $existing;
        }
        $cust = new static();
        $cust->name = $name;
        $cust->mobile = '';
        $cust->mail = '';
        $cust->country = '';
        $cust->state = '';
        $cust->city = '';
        $cust->area = '';
        $cust->openingBalance = 0;
        $cust->save();
        return $cust;
    }
}
