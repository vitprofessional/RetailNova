<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('business_setups')) {
            // Attempt fallback to singular/plural uncertainty; skip if table unknown
            if (!Schema::hasTable('business_setup')) return;
            $tableName = 'business_setup';
        } else {
            $tableName = 'business_setups';
        }

        Schema::table($tableName, function (Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'currencySymbol')) {
                $table->string('currencySymbol', 16)->nullable()->after('invoiceFooter');
            }
            if (!Schema::hasColumn($table->getTable(), 'currencyPosition')) {
                $table->string('currencyPosition', 8)->default('left')->after('currencySymbol');
            }
            if (!Schema::hasColumn($table->getTable(), 'currencyNegParentheses')) {
                $table->boolean('currencyNegParentheses')->default(true)->after('currencyPosition');
            }
        });
    }

    public function down(): void
    {
        $tableName = Schema::hasTable('business_setups') ? 'business_setups' : (Schema::hasTable('business_setup') ? 'business_setup' : null);
        if (!$tableName) return;

        Schema::table($tableName, function (Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'currencyNegParentheses')) {
                $table->dropColumn('currencyNegParentheses');
            }
            if (Schema::hasColumn($table->getTable(), 'currencyPosition')) {
                $table->dropColumn('currencyPosition');
            }
            if (Schema::hasColumn($table->getTable(), 'currencySymbol')) {
                $table->dropColumn('currencySymbol');
            }
        });
    }
};
