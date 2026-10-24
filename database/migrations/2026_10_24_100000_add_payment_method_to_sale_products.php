<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a sale was paid: cash, bKash, Nagad or due (on credit). Sales recorded before
 * this column existed keep NULL, since their payment method is unknown.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sale_products') && !Schema::hasColumn('sale_products', 'payment_method')) {
            Schema::table('sale_products', function (Blueprint $table) {
                $table->enum('payment_method', ['cash', 'bkash', 'nagad', 'due'])->nullable()->after('paidAmount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sale_products') && Schema::hasColumn('sale_products', 'payment_method')) {
            Schema::table('sale_products', function (Blueprint $table) {
                $table->dropColumn('payment_method');
            });
        }
    }
};
