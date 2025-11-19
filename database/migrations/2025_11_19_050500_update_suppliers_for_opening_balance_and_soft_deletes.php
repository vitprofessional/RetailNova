<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers','openingBalance')) {
                $table->bigInteger('openingBalance')->default(0)->after('area');
            }
            if (!Schema::hasColumn('suppliers','deleted_at')) {
                $table->softDeletes();
            }
        });

        // Adjust unique indexes to composite with deleted_at
        Schema::table('suppliers', function (Blueprint $table) {
            // Drop simple unique indexes if exist
            try { $table->dropUnique('suppliers_mail_unique'); } catch (\Throwable $e) {}
            try { $table->dropUnique('suppliers_mobile_unique'); } catch (\Throwable $e) {}
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers','mail') && Schema::hasColumn('suppliers','deleted_at')) {
                $table->unique(['mail','deleted_at'], 'suppliers_mail_deleted_unique');
            }
            if (Schema::hasColumn('suppliers','mobile') && Schema::hasColumn('suppliers','deleted_at')) {
                $table->unique(['mobile','deleted_at'], 'suppliers_mobile_deleted_unique');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }
        Schema::table('suppliers', function (Blueprint $table) {
            // Drop composite unique indexes
            try { $table->dropUnique('suppliers_mail_deleted_unique'); } catch (\Throwable $e) {}
            try { $table->dropUnique('suppliers_mobile_deleted_unique'); } catch (\Throwable $e) {}
        });

        Schema::table('suppliers', function (Blueprint $table) {
            // Recreate simple unique indexes
            if (Schema::hasColumn('suppliers','mail')) {
                $table->unique('mail', 'suppliers_mail_unique');
            }
            if (Schema::hasColumn('suppliers','mobile')) {
                $table->unique('mobile', 'suppliers_mobile_unique');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers','deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('suppliers','openingBalance')) {
                $table->dropColumn('openingBalance');
            }
        });
    }
};
