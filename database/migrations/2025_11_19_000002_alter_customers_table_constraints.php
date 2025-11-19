<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Normalize numeric columns (stored as strings) before altering type
        DB::statement("UPDATE customers SET accReceivable = CASE WHEN accReceivable REGEXP '^[0-9]+$' THEN accReceivable ELSE '0' END");
        DB::statement("UPDATE customers SET accPayable = CASE WHEN accPayable REGEXP '^[0-9]+$' THEN accPayable ELSE '0' END");

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('accReceivable')->default(0)->change();
            $table->unsignedBigInteger('accPayable')->default(0)->change();
            // Add unique constraints (ignore if already exist)
            $table->unique('mail', 'customers_mail_unique');
            $table->unique('mobile', 'customers_mobile_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_mail_unique');
            $table->dropUnique('customers_mobile_unique');
            $table->string('accReceivable')->nullable()->change();
            $table->string('accPayable')->nullable()->change();
        });
    }
};
