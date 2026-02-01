<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopesByBusiness
{
    /**
     * Apply a global scope based on the authenticated admin user's businessId
     * for GM and Store Manager roles. Also auto-set businessId on create.
     */
    public static function bootScopesByBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $builder) {
            $user = auth('admin')->user();
            if ($user && in_array(strtolower($user->role), ['gm', 'storemanager'])) {
                $table = $builder->getModel()->getTable();
                $builder->where($table . '.businessId', $user->businessId);
            }
        });

        static::creating(function ($model) {
            $user = auth('admin')->user();
            if ($user && in_array(strtolower($user->role), ['gm', 'storemanager'])) {
                if (empty($model->businessId)) {
                    $model->businessId = $user->businessId;
                }
            }
        });
    }
}
