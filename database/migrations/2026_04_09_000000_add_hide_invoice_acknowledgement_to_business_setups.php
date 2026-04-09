<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('business_setups', function (Blueprint $table) {
            if (!Schema::hasColumn('business_setups', 'hide_invoice_acknowledgement')) {
                $table->boolean('hide_invoice_acknowledgement')->default(false)->after('currencyNegParentheses');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_setups', function (Blueprint $table) {
            if (Schema::hasColumn('business_setups', 'hide_invoice_acknowledgement')) {
                $table->dropColumn('hide_invoice_acknowledgement');
            }
        });
    }
};