<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Sanitize empty strings to 0 before altering types
        DB::statement("UPDATE purchase_products SET buyPrice = NULLIF(buyPrice,'')");
        DB::statement("UPDATE purchase_products SET salePriceExVat = NULLIF(salePriceExVat,'')");
        DB::statement("UPDATE purchase_products SET salePriceInVat = NULLIF(salePriceInVat,'')");
        DB::statement("UPDATE purchase_products SET profit = NULLIF(profit,'')");
        DB::statement("UPDATE purchase_products SET totalAmount = NULLIF(totalAmount,'')");
        DB::statement("UPDATE purchase_products SET disAmount = NULLIF(disAmount,'')");
        DB::statement("UPDATE purchase_products SET grandTotal = NULLIF(grandTotal,'')");
        DB::statement("UPDATE purchase_products SET paidAmount = NULLIF(paidAmount,'')");
        DB::statement("UPDATE purchase_products SET dueAmount = NULLIF(dueAmount,'')");

        DB::statement("UPDATE invoice_items SET salePrice = NULLIF(salePrice,'')");
        DB::statement("UPDATE invoice_items SET buyPrice = NULLIF(buyPrice,'')");
        DB::statement("UPDATE invoice_items SET totalSale = NULLIF(totalSale,'')");
        DB::statement("UPDATE invoice_items SET totalPurchase = NULLIF(totalPurchase,'')");
        DB::statement("UPDATE invoice_items SET profitTotal = NULLIF(profitTotal,'')");
        DB::statement("UPDATE invoice_items SET profitMargin = NULLIF(profitMargin,'')");

        Schema::table('purchase_products', function (Blueprint $table) {
            $table->decimal('buyPrice',12,2)->nullable()->change();
            $table->decimal('salePriceExVat',12,2)->nullable()->change();
            $table->decimal('salePriceInVat',12,2)->nullable()->change();
            $table->decimal('profit',12,2)->nullable()->change();
            $table->decimal('totalAmount',12,2)->nullable()->change();
            $table->decimal('disAmount',12,2)->nullable()->change();
            $table->decimal('grandTotal',12,2)->nullable()->change();
            $table->decimal('paidAmount',12,2)->nullable()->change();
            $table->decimal('dueAmount',12,2)->nullable()->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('salePrice',12,2)->nullable()->change();
            $table->decimal('buyPrice',12,2)->nullable()->change();
            $table->decimal('totalSale',12,2)->nullable()->change();
            $table->decimal('totalPurchase',12,2)->nullable()->change();
            $table->decimal('profitTotal',12,2)->nullable()->change();
            $table->decimal('profitMargin',12,2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->string('buyPrice')->nullable()->change();
            $table->string('salePriceExVat')->nullable()->change();
            $table->string('salePriceInVat')->nullable()->change();
            $table->string('profit')->nullable()->change();
            $table->string('totalAmount')->nullable()->change();
            $table->string('disAmount')->nullable()->change();
            $table->string('grandTotal')->nullable()->change();
            $table->string('paidAmount')->nullable()->change();
            $table->string('dueAmount')->nullable()->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('salePrice')->nullable()->change();
            $table->string('buyPrice')->nullable()->change();
            $table->string('totalSale')->nullable()->change();
            $table->string('totalPurchase')->nullable()->change();
            $table->string('profitTotal')->nullable()->change();
            $table->string('profitMargin')->nullable()->change();
        });
    }
};
