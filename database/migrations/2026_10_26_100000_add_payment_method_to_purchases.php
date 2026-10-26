<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['purchase_invoices', 'purchase_products'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'payment_method')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->enum('payment_method', ['cash', 'bkash', 'nagad', 'bank', 'due'])->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['purchase_products', 'purchase_invoices'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'payment_method')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('payment_method'));
            }
        }
    }
};
