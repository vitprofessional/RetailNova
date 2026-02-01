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
        Schema::table('sale_products', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_products', 'additionalChargeName')) {
                $table->string('additionalChargeName')->nullable()->after('discountAmount');
            }
            if (!Schema::hasColumn('sale_products', 'additionalChargeAmount')) {
                // Use decimal for robust arithmetic; defaults to 0
                $table->decimal('additionalChargeAmount', 10, 2)->default(0)->nullable()->after('additionalChargeName');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_products', function (Blueprint $table) {
            if (Schema::hasColumn('sale_products', 'additionalChargeAmount')) {
                $table->dropColumn('additionalChargeAmount');
            }
            if (Schema::hasColumn('sale_products', 'additionalChargeName')) {
                $table->dropColumn('additionalChargeName');
            }
        });
    }
};
