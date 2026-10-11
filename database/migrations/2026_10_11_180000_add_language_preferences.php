<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('business_setups', fn (Blueprint $table) => $table->string('default_locale', 2)->default('en'));
        Schema::table('admin_users', fn (Blueprint $table) => $table->string('preferred_locale', 2)->nullable());
    }

    public function down(): void
    {
        Schema::table('admin_users', fn (Blueprint $table) => $table->dropColumn('preferred_locale'));
        Schema::table('business_setups', fn (Blueprint $table) => $table->dropColumn('default_locale'));
    }
};
