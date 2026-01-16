<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_locations', function (Blueprint $table) {
            // Rename columns to match model/controller
            if (Schema::hasColumn('business_locations', 'zipCode')) {
                $table->renameColumn('zipCode', 'postal_code');
            }
            if (Schema::hasColumn('business_locations', 'mobileNumber')) {
                $table->renameColumn('mobileNumber', 'phone');
            }
            if (Schema::hasColumn('business_locations', 'mail')) {
                $table->renameColumn('mail', 'email');
            }

            // Change status to boolean
            if (Schema::hasColumn('business_locations', 'status')) {
                $table->boolean('status')->default(true)->nullable()->change();
            }

            // Add new columns used by the app
            if (!Schema::hasColumn('business_locations', 'manager_name')) {
                $table->string('manager_name')->nullable()->after('email');
            }
            if (!Schema::hasColumn('business_locations', 'is_main_location')) {
                $table->boolean('is_main_location')->default(false)->after('manager_name');
            }
            if (!Schema::hasColumn('business_locations', 'description')) {
                $table->text('description')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_locations', function (Blueprint $table) {
            if (Schema::hasColumn('business_locations', 'postal_code')) {
                $table->renameColumn('postal_code', 'zipCode');
            }
            if (Schema::hasColumn('business_locations', 'phone')) {
                $table->renameColumn('phone', 'mobileNumber');
            }
            if (Schema::hasColumn('business_locations', 'email')) {
                $table->renameColumn('email', 'mail');
            }
            if (Schema::hasColumn('business_locations', 'status')) {
                $table->string('status')->nullable()->change();
            }
            if (Schema::hasColumn('business_locations', 'manager_name')) {
                $table->dropColumn('manager_name');
            }
            if (Schema::hasColumn('business_locations', 'is_main_location')) {
                $table->dropColumn('is_main_location');
            }
            if (Schema::hasColumn('business_locations', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
