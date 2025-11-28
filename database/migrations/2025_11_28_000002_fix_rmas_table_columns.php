<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('rmas')) {
            // If the table doesn't exist at all, create it as expected.
            Schema::create('rmas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('product_serial_id')->nullable()->index();
                $table->string('reason')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('open');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                try { $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null'); } catch (\Throwable $e) {}
                try { $table->foreign('product_serial_id')->references('id')->on('product_serials')->onDelete('set null'); } catch (\Throwable $e) {}
            });
            return;
        }

        // If table exists, add missing columns only
        Schema::table('rmas', function (Blueprint $table) {
            if (!Schema::hasColumn('rmas', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('rmas', 'product_serial_id')) {
                $table->unsignedBigInteger('product_serial_id')->nullable()->index()->after('customer_id');
            }
            if (!Schema::hasColumn('rmas', 'reason')) {
                $table->string('reason')->nullable()->after('product_serial_id');
            }
            if (!Schema::hasColumn('rmas', 'notes')) {
                $table->text('notes')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('rmas', 'status')) {
                $table->string('status')->default('open')->after('notes');
            }
            if (!Schema::hasColumn('rmas', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('status');
            }
            if (!Schema::hasColumn('rmas', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('rmas', 'created_at') || !Schema::hasColumn('rmas', 'updated_at')) {
                // add timestamps if missing
                try { $table->timestamps(); } catch (\Throwable $e) {}
            }
        });

        // attempt to add foreign keys (wrapped in try/catch)
        try {
            Schema::table('rmas', function (Blueprint $table) {
                if (!\Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('rmas', 'customer_id')) return;
                try { $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null'); } catch (\Throwable $e) {}
                if (!\Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('rmas', 'product_serial_id')) return;
                try { $table->foreign('product_serial_id')->references('id')->on('product_serials')->onDelete('set null'); } catch (\Throwable $e) {}
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down()
    {
        // We will not drop the whole table in down; remove columns that we added if they exist
        if (!Schema::hasTable('rmas')) return;

        Schema::table('rmas', function (Blueprint $table) {
            try { $table->dropForeign(['customer_id']); } catch (\Throwable $e) {}
            try { $table->dropForeign(['product_serial_id']); } catch (\Throwable $e) {}
            if (Schema::hasColumn('rmas', 'resolved_at')) { try { $table->dropColumn('resolved_at'); } catch (\Throwable $e) {} }
            if (Schema::hasColumn('rmas', 'created_by')) { try { $table->dropColumn('created_by'); } catch (\Throwable $e) {} }
            if (Schema::hasColumn('rmas', 'status')) { try { $table->dropColumn('status'); } catch (\Throwable $e) {} }
            if (Schema::hasColumn('rmas', 'notes')) { try { $table->dropColumn('notes'); } catch (\Throwable $e) {} }
            if (Schema::hasColumn('rmas', 'reason')) { try { $table->dropColumn('reason'); } catch (\Throwable $e) {} }
            if (Schema::hasColumn('rmas', 'product_serial_id')) { try { $table->dropColumn('product_serial_id'); } catch (\Throwable $e) {} }
            if (Schema::hasColumn('rmas', 'customer_id')) { try { $table->dropColumn('customer_id'); } catch (\Throwable $e) {} }
        });
    }
};
