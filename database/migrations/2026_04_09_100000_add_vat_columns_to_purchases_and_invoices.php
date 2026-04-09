<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add vatPercent to purchase_products if it doesn't exist
        if (Schema::hasTable('purchase_products') && !Schema::hasColumn('purchase_products', 'vatPercent')) {
            Schema::table('purchase_products', function (Blueprint $table) {
                $table->decimal('vatPercent', 5, 2)->nullable()->default(0)->after('vatStatus');
            });
        }

        // Add VAT columns to invoice_items if they don't exist
        if (Schema::hasTable('invoice_items')) {
            if (!Schema::hasColumn('invoice_items', 'vatPercent')) {
                Schema::table('invoice_items', function (Blueprint $table) {
                    $table->decimal('vatPercent', 5, 2)->nullable()->default(0)->after('profitMargin');
                });
            }
            if (!Schema::hasColumn('invoice_items', 'vatAmount')) {
                Schema::table('invoice_items', function (Blueprint $table) {
                    $table->decimal('vatAmount', 12, 2)->nullable()->default(0)->after('vatPercent');
                });
            }
            if (!Schema::hasColumn('invoice_items', 'salePriceIncVat')) {
                Schema::table('invoice_items', function (Blueprint $table) {
                    $table->decimal('salePriceIncVat', 12, 2)->nullable()->after('vatAmount');
                });
            }
            if (!Schema::hasColumn('invoice_items', 'vatIncluded')) {
                Schema::table('invoice_items', function (Blueprint $table) {
                    $table->boolean('vatIncluded')->default(false)->after('salePriceIncVat');
                });
            }
        }

        // Add VAT columns to sale_products if they don't exist
        if (Schema::hasTable('sale_products')) {
            if (!Schema::hasColumn('sale_products', 'totalVat')) {
                Schema::table('sale_products', function (Blueprint $table) {
                    $table->decimal('totalVat', 12, 2)->nullable()->default(0)->after('grandTotal');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_products') && Schema::hasColumn('purchase_products', 'vatPercent')) {
            Schema::table('purchase_products', function (Blueprint $table) {
                $table->dropColumn('vatPercent');
            });
        }

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                if (Schema::hasColumn('invoice_items', 'vatPercent')) {
                    $table->dropColumn('vatPercent');
                }
                if (Schema::hasColumn('invoice_items', 'vatAmount')) {
                    $table->dropColumn('vatAmount');
                }
                if (Schema::hasColumn('invoice_items', 'salePriceIncVat')) {
                    $table->dropColumn('salePriceIncVat');
                }
                if (Schema::hasColumn('invoice_items', 'vatIncluded')) {
                    $table->dropColumn('vatIncluded');
                }
            });
        }

        if (Schema::hasTable('sale_products') && Schema::hasColumn('sale_products', 'totalVat')) {
            Schema::table('sale_products', function (Blueprint $table) {
                $table->dropColumn('totalVat');
            });
        }
    }
};
