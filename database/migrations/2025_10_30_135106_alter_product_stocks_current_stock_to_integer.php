<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            // First, convert any decimal values to integers
            DB::statement('UPDATE product_stocks SET currentStock = FLOOR(CAST(currentStock AS DECIMAL(10,2))) WHERE currentStock IS NOT NULL');
            
            // Then change the column type to integer
            $table->integer('currentStock')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            // Revert back to string if needed
            $table->string('currentStock')->nullable()->change();
        });
    }
};
