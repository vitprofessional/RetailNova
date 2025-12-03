<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('invoice_items')) return;
        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'isBackorder')) {
                $table->boolean('isBackorder')->default(false)->after('profitMargin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('invoice_items')) return;
        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'isBackorder')) {
                $table->dropColumn('isBackorder');
            }
        });
    }
};
