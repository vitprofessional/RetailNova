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
        Schema::table('product_serials', function (Blueprint $table) {
            if (!Schema::hasColumn('product_serials', 'purchaseId')) {
                $table->unsignedBigInteger('purchaseId')->nullable()->after('productId');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_serials', function (Blueprint $table) {
            if (Schema::hasColumn('product_serials', 'purchaseId')) {
                $table->dropColumn('purchaseId');
            }
        });
    }
};
