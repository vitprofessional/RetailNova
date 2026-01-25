<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('business_setups', function (Blueprint $table) {
            if (!Schema::hasColumn('business_setups', 'invoice_terms_enabled')) {
                $table->boolean('invoice_terms_enabled')->default(true)->after('invoiceFooter');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_setups', function (Blueprint $table) {
            if (Schema::hasColumn('business_setups', 'invoice_terms_enabled')) {
                $table->dropColumn('invoice_terms_enabled');
            }
        });
    }
};
