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
        Schema::table('purchase_products', function (Blueprint $table) {
            // First, convert any decimal values to integers and handle null/empty values.
            // Use a DB-driver aware statement because SQLite doesn't provide FLOOR()
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                DB::statement("UPDATE purchase_products SET qty = CAST(COALESCE(NULLIF(qty, ''), '0') AS INTEGER) WHERE qty IS NOT NULL");
            } else {
                DB::statement("UPDATE purchase_products SET qty = FLOOR(CAST(COALESCE(NULLIF(qty, ''), '0') AS DECIMAL(10,2))) WHERE qty IS NOT NULL");
            }

            // Then change the column type to integer
            $table->integer('qty')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            // Revert back to string if needed
            $table->string('qty')->nullable()->change();
        });
    }
};
