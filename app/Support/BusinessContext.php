<?php

namespace App\Support;

use App\Models\AdminUser;
use App\Models\BusinessSetup;

/**
 * Single source of truth for "which business is this request working in".
 *
 * Every tenant-owned model reads its row filter and its default businessId on
 * create from here, so isolation rules live in one place instead of in each
 * controller.
 */
class BusinessContext
{
    /** Sentinel that matches no row: used when a non-platform user has no valid business. */
    public const NO_BUSINESS = 0;

    /** True while a web request is being handled (set by EnforceTenantWrites). */
    public static bool $enforceWrites = false;

    /** Every table whose rows belong to exactly one business (column `businessId`). */
    public const TENANT_TABLES = [
        'products', 'customers', 'suppliers', 'product_stocks', 'purchase_products', 'purchase_invoices',
        'sale_products', 'invoice_items', 'business_locations', 'brands', 'categories', 'product_units',
        'services', 'provide_services', 'service_invoices', 'service_invoice_items', 'quotations',
        'quotation_items', 'damage_products', 'sale_returns', 'return_sale_items', 'purchase_returns',
        'return_purchase_items', 'product_serials', 'rmas', 'expense_categories', 'expense_entries',
        'accounts', 'account_transactions',
    ];

    public static function isTenantTable(string $table): bool
    {
        return in_array($table, self::TENANT_TABLES, true);
    }

    public static function actor(): ?AdminUser
    {
        try {
            $user = auth('admin')->user();
        } catch (\Throwable $e) {
            return null;
        }

        return $user instanceof AdminUser ? $user : null;
    }

    /** Platform owners (superadmin) work across every business. */
    public static function isPlatform(?AdminUser $actor = null): bool
    {
        $actor ??= self::actor();

        return $actor !== null && $actor->hasSuperAdminPrivileges();
    }

    /**
     * The businessId every tenant query must be limited to, or null when no
     * limit applies (platform owner, console, queue, or no signed-in admin).
     *
     * A signed-in non-platform user without a valid business gets NO_BUSINESS,
     * so they see nothing instead of everything (fail closed).
     */
    public static function scopeId(): ?int
    {
        $actor = self::actor();
        if ($actor === null || self::isPlatform($actor)) {
            return null;
        }

        return self::validId($actor->businessId) ?? self::NO_BUSINESS;
    }

    /** businessId to stamp on a new tenant row when the caller did not set one. */
    public static function defaultForCreate(): ?int
    {
        $actor = self::actor();
        if ($actor === null) {
            return self::soleBusinessId();
        }
        if (!self::isPlatform($actor)) {
            return self::validId($actor->businessId);
        }

        return self::validId($actor->businessId) ?? self::soleBusinessId();
    }

    /** The only business on this installation, if there is exactly one. */
    public static function soleBusinessId(): ?int
    {
        try {
            $ids = BusinessSetup::query()->orderBy('id')->limit(2)->pluck('id');
        } catch (\Throwable $e) {
            return null;
        }

        return $ids->count() === 1 ? (int) $ids->first() : null;
    }

    public static function validId(mixed $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? (int) $id : null;
    }
}
