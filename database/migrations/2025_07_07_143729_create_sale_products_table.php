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
        Schema::create('sale_products', function (Blueprint $table) {
            $table->id();
            $table->string('date')->nullable();
            $table->string('invoice')->nullable();
            $table->string('customerId')->nullable();
            $table->string('reference')->nullable();
            $table->string('note')->nullable();
            $table->string('totalSale')->nullable();
            $table->string('discountAmount')->nullable();
            $table->string('grandTotal')->nullable();
            $table->string('paidAmount')->nullable();
            $table->string('invoiceDue')->nullable();
            $table->string('prevDue')->nullable();
            $table->string('curDue')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_products');
    }
};
