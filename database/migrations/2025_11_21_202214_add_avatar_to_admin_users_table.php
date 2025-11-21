<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('admin_users') && !Schema::hasColumn('admin_users', 'avatar')) {
            // If `role` exists, place avatar after it for readability; otherwise add normally.
            if (Schema::hasColumn('admin_users', 'role')) {
                Schema::table('admin_users', function (Blueprint $table) {
                    $table->string('avatar')->nullable()->after('role');
                });
            } else {
                Schema::table('admin_users', function (Blueprint $table) {
                    $table->string('avatar')->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('admin_users') && Schema::hasColumn('admin_users', 'avatar')) {
            Schema::table('admin_users', function (Blueprint $table) {
                $table->dropColumn('avatar');
            });
        }
    }
};
