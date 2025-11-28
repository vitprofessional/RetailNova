<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('rmas')) return;

        // If column doesn't exist, add it nullable. If it exists but is NOT NULL, attempt to alter to NULL.
        if (!Schema::hasColumn('rmas', 'rma_no')) {
            try {
                Schema::table('rmas', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('rma_no')->nullable()->after('id');
                });
            } catch (\Throwable $e) {
                // fallback: raw SQL add column
                try {
                    DB::statement('ALTER TABLE `rmas` ADD COLUMN `rma_no` VARCHAR(191) NULL AFTER `id`');
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            return;
        }

        // Column exists; attempt to make it nullable using raw ALTER (avoids requiring doctrine/dbal)
        try {
            // try a common VARCHAR length change to allow NULL
            DB::statement('ALTER TABLE `rmas` MODIFY COLUMN `rma_no` VARCHAR(191) NULL');
        } catch (\Throwable $e) {
            // if that fails, attempt a more permissive SQL (MySQL compatible)
            try {
                DB::statement("ALTER TABLE `rmas` CHANGE `rma_no` `rma_no` VARCHAR(191) NULL");
            } catch (\Throwable $e) {
                // ignore - can't alter column in this environment automatically
            }
        }
    }

    public function down()
    {
        // Intentionally left blank: reversing this operation (making column NOT NULL again)
        // may be destructive; leave to manual DBA action if desired.
    }
};
