<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A cancelled sale is kept for history: the sale and its invoice items are
 * soft-deleted and the sale records who cancelled it, when and why.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sale_products')) {
            Schema::table('sale_products', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_products', 'deleted_at')) {
                    $table->softDeletes();
                }
                if (!Schema::hasColumn('sale_products', 'cancel_reason')) {
                    $table->string('cancel_reason', 500)->nullable();
                }
                if (!Schema::hasColumn('sale_products', 'cancelled_by')) {
                    $table->unsignedBigInteger('cancelled_by')->nullable()->index();
                }
                if (!Schema::hasColumn('sale_products', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('invoice_items') && !Schema::hasColumn('invoice_items', 'deleted_at')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /** Rolling back drops the audit fields; cancelled sales then reappear as live sales. */
    public function down(): void
    {
        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'deleted_at')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('sale_products')) {
            if (Schema::hasColumn('sale_products', 'cancelled_by')) {
                Schema::table('sale_products', function (Blueprint $table) {
                    $table->dropIndex(['cancelled_by']);
                });
            }
            Schema::table('sale_products', function (Blueprint $table) {
                foreach (['cancelled_at', 'cancelled_by', 'cancel_reason', 'deleted_at'] as $column) {
                    if (Schema::hasColumn('sale_products', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
