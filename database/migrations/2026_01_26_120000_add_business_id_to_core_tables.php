<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'products',
            'customers',
            'suppliers',
            'product_stocks',
            'purchase_products',
            'sale_products',
            'invoice_items',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'businessId')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->unsignedBigInteger('businessId')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'products',
            'customers',
            'suppliers',
            'product_stocks',
            'purchase_products',
            'sale_products',
            'invoice_items',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'businessId')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropIndex([$tableName . '_businessId_index']);
                    $table->dropColumn('businessId');
                });
            }
        }
    }
};
