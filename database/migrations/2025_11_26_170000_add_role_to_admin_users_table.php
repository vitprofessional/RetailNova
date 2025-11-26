<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('admin_users', 'role')) {
            Schema::table('admin_users', function (Blueprint $table) {
                $table->string('role')->nullable()->after('businessId');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('admin_users', 'role')) {
            Schema::table('admin_users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
