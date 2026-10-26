<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('business_setups', 'default_invoice_printer')) {
            Schema::table('business_setups', function (Blueprint $table): void {
                $table->string('default_invoice_printer', 12)->default('thermal80');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('business_setups', 'default_invoice_printer')) {
            Schema::table('business_setups', function (Blueprint $table): void {
                $table->dropColumn('default_invoice_printer');
            });
        }
    }
};
