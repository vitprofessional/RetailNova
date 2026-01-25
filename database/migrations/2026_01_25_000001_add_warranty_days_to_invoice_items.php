<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('invoice_items') && !Schema::hasColumn('invoice_items', 'warranty_days')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->string('warranty_days')->nullable()->after('qty');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'warranty_days')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropColumn('warranty_days');
            });
        }
    }
};
