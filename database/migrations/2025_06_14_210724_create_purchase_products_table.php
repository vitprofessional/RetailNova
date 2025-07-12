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
        Schema::create('purchase_products', function (Blueprint $table) {
            $table->id();
            $table->string('productName')->nullable();
            $table->string('supplier')->nullable();
            $table->string('purchase_date')->nullable();
            $table->string('invoice')->nullable();
            $table->string('reference')->nullable();
            $table->string('qty')->nullable();
            $table->string('buyPrice')->nullable();
            $table->string('salePriceExVat')->nullable();
            $table->string('vatStatus')->nullable();
            $table->string('salePriceInVat')->nullable();
            $table->string('profit')->nullable();
            $table->string('totalAmount')->nullable();
            $table->string('disType')->nullable();
            $table->string('disAmount')->nullable();
            $table->string('disParcent')->nullable();
            $table->string('grandTotal')->nullable();
            $table->string('paidAmount')->nullable();
            $table->string('dueAmount')->nullable();
            $table->string('specialNote')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_products');
    }
};
