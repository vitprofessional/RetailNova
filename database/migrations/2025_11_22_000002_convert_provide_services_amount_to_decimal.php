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
        // Trim whitespace and convert known numeric strings to a decimal value first
        DB::statement("UPDATE provide_services SET amount = TRIM(amount) WHERE amount IS NOT NULL");

        // Convert all amount values to numeric (non-numeric become 0) and alter column to DECIMAL
        // Some DB drivers (SQLite) don't support ALTER ... MODIFY; run driver-aware logic.
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            // SQLite doesn't support MODIFY; ensure values are numeric and leave schema as-is for tests.
            DB::statement("UPDATE provide_services SET amount = CASE WHEN amount GLOB '[0-9]*' THEN amount ELSE '0.00' END");
        } else {
            // MySQL/Postgres: alter the column type
            DB::statement("ALTER TABLE provide_services MODIFY amount DECIMAL(12,2) NULL DEFAULT 0.00");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert column back to string where possible
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            // SQLite: nothing to do to schema in tests
        } else {
            DB::statement("ALTER TABLE provide_services MODIFY amount VARCHAR(191) NULL");
        }
    }
};
