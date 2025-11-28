<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * If a UNIQUE index exists on purchase_products.invoice, drop it safely.
     */
    public function up()
    {
        try {
            $db = DB::getDatabaseName();
            $rows = DB::select("SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND NON_UNIQUE = 0", [$db, 'purchase_products', 'invoice']);
            if (!empty($rows)) {
                foreach ($rows as $r) {
                    $index = $r->INDEX_NAME ?? $r->index_name ?? null;
                    if ($index) {
                        DB::statement("ALTER TABLE `purchase_products` DROP INDEX `" . $index . "`");
                    }
                }
            }
        } catch (\Exception $e) {
            // Do not fail migration in case of permissions or platform differences; log for diagnostics
            try { \Log::warning('Drop invoice unique index migration failed: ' . $e->getMessage()); } catch (\Exception $_) {}
        }
    }

    /**
     * Reverse the migrations.
     * Re-create a unique index named `purchase_products_invoice_unique` if it does not exist.
     * Note: creating a unique index may fail if duplicate invoice values already exist.
     */
    public function down()
    {
        try {
            $db = DB::getDatabaseName();
            $exists = DB::select("SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?", [$db, 'purchase_products', 'purchase_products_invoice_unique']);
            if (empty($exists)) {
                // Use a prefix length to avoid index length issues on older MySQL versions
                DB::statement("ALTER TABLE `purchase_products` ADD UNIQUE `purchase_products_invoice_unique` (`invoice`(191))");
            }
        } catch (\Exception $e) {
            try { \Log::warning('Recreate invoice unique index migration failed: ' . $e->getMessage()); } catch (\Exception $_) {}
        }
    }
};
