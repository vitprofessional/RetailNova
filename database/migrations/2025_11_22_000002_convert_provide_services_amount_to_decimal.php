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
        // MySQL will cast non-numeric to 0 when converting types; ensure default 0.00
        DB::statement("ALTER TABLE provide_services MODIFY amount DECIMAL(12,2) NULL DEFAULT 0.00");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert column back to string
        DB::statement("ALTER TABLE provide_services MODIFY amount VARCHAR(191) NULL");
    }
};
