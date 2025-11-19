<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers','deleted_at')) {
                $table->softDeletes();
            }
        });

        // Swap unique indexes to composite unique with deleted_at to allow same email/mobile on soft-deleted rows
        Schema::table('customers', function (Blueprint $table) {
            // Drop existing simple unique indexes if they exist
            try { $table->dropUnique('customers_mail_unique'); } catch (\Throwable $e) {}
            try { $table->dropUnique('customers_mobile_unique'); } catch (\Throwable $e) {}
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique(['mail','deleted_at'], 'customers_mail_deleted_unique');
            $table->unique(['mobile','deleted_at'], 'customers_mobile_deleted_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Revert composite unique
            try { $table->dropUnique('customers_mail_deleted_unique'); } catch (\Throwable $e) {}
            try { $table->dropUnique('customers_mobile_deleted_unique'); } catch (\Throwable $e) {}
            // Restore simple unique
            $table->unique('mail', 'customers_mail_unique');
            $table->unique('mobile', 'customers_mobile_unique');

            // Drop soft deletes column
            if (Schema::hasColumn('customers','deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
