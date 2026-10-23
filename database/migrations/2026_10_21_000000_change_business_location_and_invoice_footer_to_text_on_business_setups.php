<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings accept up to 500 characters of address and 2000 of invoice footer,
 * which exceed the original VARCHAR(255) columns.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('business_setups')) {
            return;
        }

        Schema::table('business_setups', function (Blueprint $table) {
            if (Schema::hasColumn('business_setups', 'businessLocation')) {
                $table->text('businessLocation')->nullable()->change();
            }
            if (Schema::hasColumn('business_setups', 'invoiceFooter')) {
                $table->text('invoiceFooter')->nullable()->change();
            }
        });
    }

    /** Rolling back fails on, or truncates, values longer than 255 characters, depending on the SQL mode. */
    public function down(): void
    {
        if (!Schema::hasTable('business_setups')) {
            return;
        }

        Schema::table('business_setups', function (Blueprint $table) {
            if (Schema::hasColumn('business_setups', 'businessLocation')) {
                $table->string('businessLocation')->nullable()->change();
            }
            if (Schema::hasColumn('business_setups', 'invoiceFooter')) {
                $table->string('invoiceFooter')->nullable()->change();
            }
        });
    }
};
