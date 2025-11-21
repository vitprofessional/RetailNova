<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $hasPurchaseDup = DB::table('purchase_products')
            ->selectRaw('invoice, COUNT(*) as c')
            ->whereNotNull('invoice')->where('invoice','<>','')
            ->groupBy('invoice')->havingRaw('COUNT(*) > 1')->limit(1)->exists();
        $hasSaleDup = DB::table('sale_products')
            ->selectRaw('invoice, COUNT(*) as c')
            ->whereNotNull('invoice')->where('invoice','<>','')
            ->groupBy('invoice')->havingRaw('COUNT(*) > 1')->limit(1)->exists();
        if ($hasPurchaseDup || $hasSaleDup) {
            throw new \RuntimeException('Duplicate invoices detected; resolve before applying unique indexes.');
        }

        Schema::table('purchase_products', function (Blueprint $table) {
            if (!self::indexExists('purchase_products','purchase_products_invoice_unique')) {
                $table->unique('invoice','purchase_products_invoice_unique');
            }
        });
        Schema::table('sale_products', function (Blueprint $table) {
            if (!self::indexExists('sale_products','sale_products_invoice_unique')) {
                $table->unique('invoice','sale_products_invoice_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            if (self::indexExists('purchase_products','purchase_products_invoice_unique')) {
                $table->dropUnique('purchase_products_invoice_unique');
            }
        });
        Schema::table('sale_products', function (Blueprint $table) {
            if (self::indexExists('sale_products','sale_products_invoice_unique')) {
                $table->dropUnique('sale_products_invoice_unique');
            }
        });
    }

    private static function indexExists(string $table, string $index): bool
    {
        // Use a direct SHOW INDEX query to avoid requiring doctrine/dbal.
        // This is MySQL-specific but matches the project's DB.
        try {
            $rows = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index]);
            return is_array($rows) && count($rows) > 0;
        } catch (\Exception $e) {
            // If the database doesn't support SHOW INDEX, fall back to false so migration doesn't fail unexpectedly.
            return false;
        }
    }
};
