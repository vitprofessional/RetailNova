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
        Schema::table('products', function (Blueprint $table) {
            // Add index on barCode for faster lookups during barcode scans
            if (!Schema::hasColumn('products', 'barCode')) {
                $table->string('barCode')->nullable()->after('details');
            }
            
            // Add unique index if not already present
            try {
                $table->unique('barCode', 'products_barcode_unique');
            } catch (\Exception $e) {
                // Index might already exist, which is fine
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_barcode_unique');
        });
    }
};
