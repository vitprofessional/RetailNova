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
        Schema::table('invoice_items', function (Blueprint $table) {
            // First, convert any decimal values to integers and handle null/empty values
            DB::statement('UPDATE invoice_items SET qty = FLOOR(CAST(COALESCE(NULLIF(qty, ""), "0") AS DECIMAL(10,2))) WHERE qty IS NOT NULL');
            
            // Then change the column type to integer
            $table->integer('qty')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            // Revert back to string if needed
            $table->string('qty')->nullable()->change();
        });
    }
};
