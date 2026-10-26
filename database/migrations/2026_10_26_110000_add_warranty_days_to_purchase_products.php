<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('purchase_products') && !Schema::hasColumn('purchase_products', 'warranty_days')) {
            Schema::table('purchase_products', fn (Blueprint $table) => $table->string('warranty_days', 32)->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_products') && Schema::hasColumn('purchase_products', 'warranty_days')) {
            Schema::table('purchase_products', fn (Blueprint $table) => $table->dropColumn('warranty_days'));
        }
    }
};
