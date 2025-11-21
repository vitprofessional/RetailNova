<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        // Fallback index detection using SHOW INDEX to avoid dependency on doctrine/dbal
        $indexes = [];
        try {
            $rows = DB::select('SHOW INDEX FROM `suppliers`');
            foreach ($rows as $r) {
                if (isset($r->Key_name)) {
                    $indexes[$r->Key_name] = true;
                }
            }
        } catch (\Throwable $e) {
            $indexes = [];
        }

        Schema::table('suppliers', function (Blueprint $table) use ($indexes) {
            if (Schema::hasColumn('suppliers', 'mail') && !isset($indexes['suppliers_mail_unique'])) {
                $table->unique('mail', 'suppliers_mail_unique');
            }
            if (Schema::hasColumn('suppliers', 'mobile') && !isset($indexes['suppliers_mobile_unique'])) {
                $table->unique('mobile', 'suppliers_mobile_unique');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'mail')) {
                $table->dropUnique('suppliers_mail_unique');
            }
            if (Schema::hasColumn('suppliers', 'mobile')) {
                $table->dropUnique('suppliers_mobile_unique');
            }
        });
    }
};
