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

        $indexes = [];
        try {
            $connection = Schema::getConnection();
            $tableName = $connection->getTablePrefix() . 'suppliers';
            // Try to obtain Doctrine schema manager for index inspection
            $sm = method_exists($connection, 'getDoctrineSchemaManager')
                ? $connection->getDoctrineSchemaManager()
                : null;
            if ($sm) {
                $indexes = $sm->listTableIndexes($tableName);
            }
        } catch (\Throwable $e) {
            // If introspection fails, proceed and let the DDL run
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
