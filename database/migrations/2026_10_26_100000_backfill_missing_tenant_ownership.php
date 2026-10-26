<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Child table, owning parent table, child foreign key. */
    private array $parents = [
        ['invoice_items', 'sale_products', 'saleId'],
        ['invoice_items', 'purchase_products', 'purchaseId'],
        ['product_stocks', 'purchase_products', 'purchaseId'],
        ['product_stocks', 'products', 'productId'],
        ['sale_products', 'customers', 'customerId'],
        ['purchase_products', 'suppliers', 'supplier'],
        ['product_serials', 'purchase_products', 'purchaseId'],
        ['product_serials', 'products', 'productId'],
        ['sale_returns', 'sale_products', 'saleId'],
        ['return_sale_items', 'sale_products', 'saleId'],
        ['return_sale_items', 'sale_returns', 'returnId'],
        ['purchase_returns', 'purchase_products', 'purchaseId'],
        ['return_purchase_items', 'purchase_products', 'purchaseId'],
        ['damage_products', 'products', 'product_id'],
        ['quotations', 'customers', 'customer_id'],
        ['quotation_items', 'quotations', 'quotation_id'],
        ['service_invoices', 'customers', 'customer_id'],
        ['service_invoice_items', 'service_invoices', 'invoice_id'],
        ['rmas', 'customers', 'customer_id'],
        ['rmas', 'product_serials', 'product_serial_id'],
        ['accounts', 'business_locations', 'business_location_id'],
        ['account_transactions', 'business_locations', 'business_location_id'],
        ['account_transactions', 'accounts', 'debit_account_id'],
        ['expense_entries', 'business_locations', 'business_location_id'],
        ['expense_entries', 'account_transactions', 'account_transaction_id'],
    ];

    public function up(): void
    {
        foreach ($this->parents as [$child, $parent, $foreignKey]) {
            $this->inherit($child, $parent, $foreignKey);
        }

        foreach (Schema::getTableListing(null, false) as $table) {
            if (!Schema::hasColumn($table, 'businessId')) {
                continue;
            }
            $remaining = DB::table($table)->whereNull('businessId')->count();
            if ($remaining > 0) {
                Log::warning('Tenant ownership backfill left unresolved rows.', ['table' => $table, 'count' => $remaining]);
            }
        }
    }

    public function down(): void
    {
        // Ownership recovery is intentionally irreversible; removing populated tenant IDs is unsafe.
    }

    private function inherit(string $child, string $parent, string $foreignKey): void
    {
        if (!Schema::hasTable($child) || !Schema::hasTable($parent)
            || !Schema::hasColumn($child, 'businessId') || !Schema::hasColumn($child, $foreignKey)
            || !Schema::hasColumn($parent, 'businessId')) {
            return;
        }

        $prefix = DB::getTablePrefix();
        $childTable = $prefix.$child;
        $parentTable = $prefix.$parent;
        DB::statement("UPDATE {$childTable} SET businessId = (SELECT parent.businessId FROM {$parentTable} AS parent WHERE parent.id = {$childTable}.{$foreignKey}) "
            ."WHERE businessId IS NULL AND {$foreignKey} IS NOT NULL "
            ."AND EXISTS (SELECT 1 FROM {$parentTable} AS parent WHERE parent.id = {$childTable}.{$foreignKey} AND parent.businessId IS NOT NULL)");
    }
};
