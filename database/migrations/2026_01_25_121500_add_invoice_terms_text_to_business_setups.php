<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('business_setups', function (Blueprint $table) {
            if (!Schema::hasColumn('business_setups', 'invoice_terms_text')) {
                $table->text('invoice_terms_text')->nullable()->after('invoice_terms_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_setups', function (Blueprint $table) {
            if (Schema::hasColumn('business_setups', 'invoice_terms_text')) {
                $table->dropColumn('invoice_terms_text');
            }
        });
    }
};
