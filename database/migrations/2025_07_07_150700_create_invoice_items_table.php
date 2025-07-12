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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->string('saleId')->nullable();
            $table->string('purchaseId')->nullable();
            $table->string('qty')->nullable();
            $table->string('salePrice')->nullable();
            $table->string('buyPrice')->nullable();
            $table->string('totalSale')->nullable();
            $table->string('totalPurchase')->nullable();
            $table->string('profitTotal')->nullable();
            $table->string('profitMargin')->nullable();
            // $table->string('buyPrice')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
