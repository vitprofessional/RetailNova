<?php

namespace App\Models\Concerns;

use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tenant isolation for business-owned tables.
 *
 * - Every signed-in user except the platform owner (superadmin) only reads and
 *   writes rows of their own business. A user without a valid business sees
 *   nothing (fail closed).
 * - New rows get the current businessId automatically when none was set.
 *
 * Code that must cross businesses on purpose (platform tools, legacy shared
 * rows) opts out explicitly with withoutGlobalScope('business').
 */
trait ScopesByBusiness
{
    public static function bootScopesByBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $builder) {
            $businessId = BusinessContext::scopeId();
            if ($businessId === null) {
                return;
            }
            $builder->where($builder->getModel()->qualifyColumn('businessId'), $businessId);
        });

        static::creating(function ($model) {
            if (empty($model->businessId)) {
                $default = BusinessContext::defaultForCreate();
                if ($default !== null) {
                    $model->businessId = $default;
                }
                return;
            }
            // While a web request is handled, a business user may never write into
            // another business. (Console jobs, seeders and test fixtures are outside it.)
            $scope = BusinessContext::scopeId();
            if ($scope !== null && BusinessContext::$enforceWrites && (int) $model->businessId !== $scope) {
                abort(403, 'The requested business is not authorized.');
            }
        });
    }
}
