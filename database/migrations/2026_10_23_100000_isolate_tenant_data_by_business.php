<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-business (SaaS) isolation.
 *
 * 1. Adds an indexed businessId to every business-owned table that lacked one.
 * 2. Fills it for existing rows: from the parent row where one exists; on a
 *    single-business installation every remaining NULL goes to that business;
 *    shared master lists (brands, categories, units, services, expense
 *    categories) are copied so each business that uses them owns its own rows.
 * 3. Makes "must be unique" rules per business instead of global (barcode,
 *    customer/supplier email + mobile, account code, transaction reference,
 *    expense category name).
 *
 * Rows whose owner cannot be determined on a multi-business installation stay
 * NULL: only the platform owner (superadmin) sees them until assigned.
 */
return new class extends Migration {
    /** Tables that get a businessId column in this migration. */
    private array $newTables = [
        'brands', 'categories', 'product_units', 'services', 'provide_services',
        'service_invoices', 'service_invoice_items', 'quotations', 'quotation_items',
        'damage_products', 'sale_returns', 'return_sale_items', 'purchase_returns',
        'return_purchase_items', 'product_serials', 'rmas', 'expense_categories',
        'expense_entries', 'accounts', 'account_transactions',
    ];

    /** Tables that already had businessId (older migrations). */
    private array $existingTables = [
        'products', 'customers', 'suppliers', 'product_stocks', 'purchase_products',
        'sale_products', 'invoice_items', 'purchase_invoices', 'business_locations',
    ];

    /** [table, old unique columns, new unique name] */
    private array $uniques = [
        ['products', ['barCode'], 'products_business_barcode_unique'],
        ['customers', ['mail', 'deleted_at'], 'customers_business_mail_deleted_unique'],
        ['customers', ['mobile', 'deleted_at'], 'customers_business_mobile_deleted_unique'],
        ['suppliers', ['mail', 'deleted_at'], 'suppliers_business_mail_deleted_unique'],
        ['suppliers', ['mobile', 'deleted_at'], 'suppliers_business_mobile_deleted_unique'],
        ['accounts', ['account_code'], 'accounts_business_code_unique'],
        ['account_transactions', ['reference_no'], 'account_transactions_business_reference_unique'],
        ['expense_categories', ['name'], 'expense_categories_business_name_unique'],
        // Daily counters for these numbers now run per business.
        ['quotations', ['quote_number'], 'quotations_business_number_unique'],
        ['service_invoices', ['invoice_number'], 'service_invoices_business_number_unique'],
    ];

    public function up(): void
    {
        foreach ($this->newTables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'businessId')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('businessId')->nullable()->index();
                });
            }
        }

        // Old global uniques go first so per-business copies can be inserted.
        foreach ($this->uniques as [$table, $columns]) {
            $this->dropUniqueOn($table, $columns);
        }

        $this->backfill();

        foreach ($this->uniques as [$table, $columns, $name]) {
            if (!Schema::hasTable($table) || !$this->hasColumns($table, $columns)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                $t->unique(array_merge(['businessId'], $columns), $name);
            });
        }

        // Lookups that the sale, return and serial screens run constantly.
        $this->addIndex('invoice_items', ['saleId']);
        $this->addIndex('invoice_items', ['purchaseId']);
        $this->addIndex('product_stocks', ['purchaseId']);
        $this->addIndex('product_stocks', ['productId']);
        $this->addIndex('product_serials', ['purchaseId']);
        $this->addIndex('product_serials', ['saleId']);
        $this->addIndex('product_serials', ['businessId', 'serialNumber']);
        $this->addIndex('sale_products', ['customerId']);
    }

    public function down(): void
    {
        foreach ($this->uniques as [$table, $columns, $name]) {
            if (Schema::hasTable($table) && $this->indexExists($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropUnique($name));
            }
        }
        foreach (['invoice_items' => ['saleId', 'purchaseId'], 'product_stocks' => ['purchaseId', 'productId'],
                  'product_serials' => ['purchaseId', 'saleId'], 'sale_products' => ['customerId']] as $table => $cols) {
            foreach ($cols as $col) {
                $this->dropIndexIfExists($table, $table.'_'.strtolower($col).'_tenant_index');
            }
        }
        $this->dropIndexIfExists('product_serials', 'product_serials_businessid_serialnumber_tenant_index');
        foreach ($this->newTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'businessId')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->dropIndex($table.'_businessid_index');
                    $t->dropColumn('businessId');
                });
            }
        }
        // Global uniques are not restored: per-business duplicates may now exist.
    }

    // ------------------------------------------------------------------ backfill

    private function backfill(): void
    {
        $businessIds = DB::table('business_setups')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (!$businessIds) {
            return; // Fresh install: nothing to assign.
        }

        if (count($businessIds) === 1) {
            $only = $businessIds[0];
            foreach (array_merge($this->newTables, $this->existingTables) as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'businessId')) {
                    DB::table($table)->whereNull('businessId')->update(['businessId' => $only]);
                }
            }
            return;
        }

        // Multi-business: inherit from the owning parent row.
        $this->inherit('product_serials', 'purchase_products', 'purchaseId');
        $this->inherit('product_serials', 'products', 'productId');
        $this->inherit('sale_returns', 'sale_products', 'saleId');
        $this->inherit('return_sale_items', 'sale_products', 'saleId');
        $this->inherit('return_sale_items', 'sale_returns', 'returnId');
        $this->inherit('purchase_returns', 'purchase_products', 'purchaseId');
        $this->inherit('return_purchase_items', 'purchase_products', 'purchaseId');
        $this->inherit('damage_products', 'products', 'product_id');
        $this->inherit('quotations', 'customers', 'customer_id');
        $this->inherit('quotation_items', 'quotations', 'quotation_id');
        $this->inherit('service_invoices', 'customers', 'customer_id');
        $this->inherit('service_invoice_items', 'service_invoices', 'invoice_id');
        $this->inherit('rmas', 'customers', 'customer_id');
        $this->inherit('rmas', 'product_serials', 'product_serial_id');
        $this->inherit('accounts', 'business_locations', 'business_location_id');
        $this->inherit('account_transactions', 'business_locations', 'business_location_id');
        $this->inherit('account_transactions', 'accounts', 'debit_account_id');
        $this->inherit('expense_entries', 'business_locations', 'business_location_id');
        $this->inherit('expense_entries', 'account_transactions', 'account_transaction_id');

        // Shared lists become per-business copies.
        $this->splitMaster('brands', $businessIds, ['products' => 'brand']);
        $this->splitMaster('categories', $businessIds, ['products' => 'category']);
        $this->splitMaster('product_units', $businessIds, ['products' => 'unitName']);
        $this->splitMaster('expense_categories', $businessIds, ['expense_entries' => 'category_id']);
        $this->splitMaster('services', $businessIds, []);

        foreach (array_merge($this->newTables, $this->existingTables) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'businessId')) {
                $left = DB::table($table)->whereNull('businessId')->count();
                if ($left > 0) {
                    Log::warning("Tenant isolation: {$left} row(s) in {$table} have no business; only superadmin can see them.");
                }
            }
        }
    }

    /** child.businessId = parent.businessId where child.fk = parent.id and child has none yet. */
    private function inherit(string $child, string $parent, string $fk): void
    {
        if (!Schema::hasTable($child) || !Schema::hasTable($parent)
            || !Schema::hasColumn($child, $fk) || !Schema::hasColumn($parent, 'businessId')) {
            return;
        }
        $c = DB::getTablePrefix().$child;
        $p = DB::getTablePrefix().$parent;
        DB::statement("UPDATE {$c} SET businessId = (SELECT {$p}.businessId FROM {$p} WHERE {$p}.id = {$c}.{$fk}) "
            ."WHERE {$c}.businessId IS NULL AND {$c}.{$fk} IS NOT NULL "
            ."AND EXISTS (SELECT 1 FROM {$p} WHERE {$p}.id = {$c}.{$fk} AND {$p}.businessId IS NOT NULL)");
    }

    /**
     * Give each business its own copy of a shared list row.
     * $refs maps referencing table => column holding this row's id.
     * The first business using a row keeps it; others get a copy and their
     * references are re-pointed. Unused rows are copied to every business.
     */
    private function splitMaster(string $table, array $businessIds, array $refs): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        $rows = DB::table($table)->whereNull('businessId')->orderBy('id')->get();
        foreach ($rows as $row) {
            $users = [];
            foreach ($refs as $refTable => $column) {
                if (!Schema::hasTable($refTable) || !Schema::hasColumn($refTable, $column)) {
                    continue;
                }
                $found = DB::table($refTable)->where($column, (string) $row->id)
                    ->whereNotNull('businessId')->distinct()->pluck('businessId')->map(fn ($id) => (int) $id)->all();
                $users = array_merge($users, $found);
            }
            $users = array_values(array_unique($users));
            $targets = $users ?: $businessIds;
            sort($targets);

            $owner = array_shift($targets);
            DB::table($table)->where('id', $row->id)->update(['businessId' => $owner]);

            foreach ($targets as $businessId) {
                $copy = (array) $row;
                unset($copy['id']);
                $copy['businessId'] = $businessId;
                $newId = DB::table($table)->insertGetId($copy);
                foreach ($refs as $refTable => $column) {
                    if (Schema::hasTable($refTable) && Schema::hasColumn($refTable, $column)) {
                        DB::table($refTable)->where('businessId', $businessId)
                            ->where($column, (string) $row->id)->update([$column => (string) $newId]);
                    }
                }
            }
        }
    }

    // ------------------------------------------------------------------ index helpers

    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }
        return true;
    }

    private function indexExists(string $table, string $name): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (strcasecmp($index['name'], $name) === 0) {
                return true;
            }
        }
        return false;
    }

    /** Drop every unique index whose columns are exactly $columns (names differ between installs). */
    private function dropUniqueOn(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        $wanted = array_map('strtolower', $columns);
        foreach (Schema::getIndexes($table) as $index) {
            if (!$index['unique'] || $index['primary']) {
                continue;
            }
            if (array_map('strtolower', $index['columns']) === $wanted) {
                Schema::table($table, fn (Blueprint $t) => $t->dropUnique($index['name']));
            }
        }
    }

    private function addIndex(string $table, array $columns): void
    {
        if (!Schema::hasTable($table) || !$this->hasColumns($table, $columns)) {
            return;
        }
        $wanted = array_map('strtolower', $columns);
        foreach (Schema::getIndexes($table) as $index) {
            if (array_slice(array_map('strtolower', $index['columns']), 0, count($wanted)) === $wanted) {
                return; // An index already starts with these columns.
            }
        }
        $name = $table.'_'.strtolower(implode('_', $columns)).'_tenant_index';
        Schema::table($table, function (Blueprint $t) use ($table, $columns, $name) {
            // Text-typed legacy id columns need a prefix length on MySQL.
            $isText = in_array(Schema::getColumnType($table, $columns[count($columns) - 1]), ['text', 'mediumtext', 'longtext'], true);
            if ($isText && DB::getDriverName() !== 'sqlite') {
                DB::statement('CREATE INDEX '.$name.' ON '.DB::getTablePrefix().$table.' ('.implode(',', $columns).'(64))');
                return;
            }
            $t->index($columns, $name);
        });
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (Schema::hasTable($table) && $this->indexExists($table, $name)) {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
        }
    }
};
