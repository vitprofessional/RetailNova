<?php

namespace App\Validation;

use App\Support\BusinessContext;
use Illuminate\Validation\DatabasePresenceVerifier;

/**
 * Makes every `exists:` and `unique:` rule on a business-owned table look only
 * inside the signed-in user's business, so a form can neither point at another
 * shop's record nor be blocked by another shop's email, barcode or code.
 * Platform owners (superadmin) keep the global check.
 */
class TenantPresenceVerifier extends DatabasePresenceVerifier
{
    protected function table($table)
    {
        $query = parent::table($table);
        $businessId = BusinessContext::scopeId();
        if ($businessId === null || !BusinessContext::isTenantTable($table)) {
            return $query;
        }

        if ($table === 'customers') {
            // The legacy shared "Walking Customer" (no business) stays selectable everywhere.
            return $query->where(function ($q) use ($businessId) {
                $q->where('businessId', $businessId)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('businessId')->where('name', 'Walking Customer'));
            });
        }

        return $query->where('businessId', $businessId);
    }
}
