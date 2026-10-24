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

    /** The walk-in customer is stored (and looked up) under this name. */
    public const WALK_IN_NAME = 'Walking Customer';

    /** People see the walk-in customer as "সাধারণ কাস্টমার" / "Walk-in Customer"; the stored name never changes. */
    public function getNameAttribute($value)
    {
        return self::displayName($value);
    }

    public function isWalkIn(): bool
    {
        return self::isWalkInName($this->attributes['name'] ?? null);
    }

    /** A customer name as people see it, also for names read through joins (customers.name AS …). */
    public static function displayName(?string $name): ?string
    {
        return self::isWalkInName($name) ? self::walkInLabel() : $name;
    }

    public static function walkInLabel(): string
    {
        return __('common.walking_customer');
    }

    /**
     * Resolve a safe label when a relation is absent. A null customer ID means
     * walk-in; an unresolved ID must not be mislabeled as a walk-in or expose a
     * customer belonging to another business.
     */
    public static function displayLabel(?self $customer, mixed $customerId = null, mixed $businessId = null): string
    {
        $businessKey = filter_var($businessId, FILTER_VALIDATE_INT);
        $belongsToBusiness = $customer !== null
            && $businessKey !== false
            && (int) $businessKey > 0
            && (int) $customer->businessId === (int) $businessKey;
        $isSharedWalkIn = $customer !== null
            && $customer->businessId === null
            && $customer->isWalkIn();

        if ($customer !== null && ($belongsToBusiness || $isSharedWalkIn)) {
            return (string) $customer->name;
        }

        if ($customerId === null || $customerId === '') {
            return self::walkInLabel();
        }

        $id = filter_var($customerId, FILTER_VALIDATE_INT);
        if ($id === false || (int) $id < 1) {
            return __('common.customer').' #'.(string) $customerId;
        }

        $businessId = $businessKey;
        $candidate = static::withoutGlobalScope('business')
            ->whereKey((int) $id)
            ->where(function ($query) use ($businessId) {
                if ($businessId !== false && (int) $businessId > 0) {
                    $query->where('businessId', (int) $businessId)
                        ->orWhere(function ($legacy) {
                            $legacy->whereNull('businessId')->where('name', self::WALK_IN_NAME);
                        });
                } else {
                    $query->whereNull('businessId')->where('name', self::WALK_IN_NAME);
                }
            })
            ->first();

        return $candidate?->name ?? __('common.customer').' #'.(int) $id;
    }

    /** Check a referenced walk-in row without accepting an arbitrary foreign customer. */
    public static function isWalkInIdForBusiness(mixed $customerId, mixed $businessId): bool
    {
        $id = filter_var($customerId, FILTER_VALIDATE_INT);
        $businessId = filter_var($businessId, FILTER_VALIDATE_INT);
        if ($id === false || (int) $id < 1) {
            return false;
        }

        return static::withoutGlobalScope('business')
            ->whereKey((int) $id)
            ->where('name', self::WALK_IN_NAME)
            ->where(function ($query) use ($businessId) {
                if ($businessId !== false && (int) $businessId > 0) {
                    $query->where('businessId', (int) $businessId)->orWhereNull('businessId');
                } else {
                    $query->whereNull('businessId');
                }
            })
            ->exists();
    }

    private static function isWalkInName(?string $name): bool
    {
        return $name !== null && strcasecmp(trim($name), self::WALK_IN_NAME) === 0;
    }

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
        $name = self::WALK_IN_NAME;
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
