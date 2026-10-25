<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'customers', 'suppliers'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'image')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('image')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['products', 'customers', 'suppliers'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'image')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('image');
                });
            }
        }
    }
};
