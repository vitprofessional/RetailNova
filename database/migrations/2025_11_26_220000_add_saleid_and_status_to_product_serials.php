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
            if (! Schema::hasColumn('product_serials', 'saleId')) {
                $table->unsignedBigInteger('saleId')->nullable()->after('purchaseId');
            }
            if (! Schema::hasColumn('product_serials', 'sold_at')) {
                $table->timestamp('sold_at')->nullable()->after('saleId');
            }
            if (! Schema::hasColumn('product_serials', 'status')) {
                $table->string('status')->nullable()->after('sold_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_serials', function (Blueprint $table) {
            if (Schema::hasColumn('product_serials', 'saleId')) {
                $table->dropColumn('saleId');
            }
            if (Schema::hasColumn('product_serials', 'sold_at')) {
                $table->dropColumn('sold_at');
            }
            if (Schema::hasColumn('product_serials', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
