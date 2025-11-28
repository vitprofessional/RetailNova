<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Only add the column when it does not exist to avoid errors
        if (Schema::hasTable('rmas') && !Schema::hasColumn('rmas', 'product_serial_id')) {
            Schema::table('rmas', function (Blueprint $table) {
                // place after customer_id when possible
                $table->unsignedBigInteger('product_serial_id')->nullable()->index()->after('customer_id');
            });

            // Attempt to add the foreign key; wrap in try/catch because some environments
            // (older MySQL versions or certain setups) may require explicit index names.
            try {
                Schema::table('rmas', function (Blueprint $table) {
                    $table->foreign('product_serial_id')->references('id')->on('product_serials')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // ignore foreign key creation errors; the column is the important part
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('rmas') && Schema::hasColumn('rmas', 'product_serial_id')) {
            Schema::table('rmas', function (Blueprint $table) {
                // Attempt to drop the foreign key using common convention, then drop the column
                try {
                    $table->dropForeign(['product_serial_id']);
                } catch (\Exception $e) {
                    // ignore if FK not present
                }

                // finally drop the column if present
                try {
                    $table->dropColumn('product_serial_id');
                } catch (\Exception $e) {
                    // ignore
                }
            });
        }
    }
};
