<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('businessId')->index();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->string('invoice',190)->nullable()->index();
            $table->date('purchase_date');
            $table->string('reference',190)->nullable();
            foreach (['total_amount','discount_amount','grand_total','paid_amount','due_amount'] as $field) {
                $table->decimal($field,14,2)->default(0);
            }
            $table->string('discount_type',10)->nullable();
            $table->decimal('discount_percent',7,2)->default(0);
            $table->text('special_note')->nullable();
            $table->timestamps();
        });
        Schema::table('purchase_products', fn(Blueprint $table) =>
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->restrictOnDelete());
        // No automatic grouping/backfill: repeated invoice text cannot establish historical identity.
    }

    public function down(): void
    {
        Schema::table('purchase_products', fn(Blueprint $table) => $table->dropConstrainedForeignId('purchase_invoice_id'));
        Schema::dropIfExists('purchase_invoices');
    }
};
