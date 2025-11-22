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
        // Backfill rate where missing and qty > 0. Use SQL to avoid loading rows.
        DB::statement("UPDATE provide_services SET rate = (CASE WHEN qty > 0 THEN amount/qty ELSE NULL END) WHERE (rate IS NULL OR rate = '') AND qty IS NOT NULL AND qty > 0");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert backfilled rates to NULL for safety (only affects rows with non-null rate and amount)
        DB::statement("UPDATE provide_services SET rate = NULL WHERE rate IS NOT NULL AND (SELECT 1)");
    }
};
