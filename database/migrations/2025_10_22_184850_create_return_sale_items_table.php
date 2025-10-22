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
        Schema::create('return_sale_items', function (Blueprint $table) {
            $table->id();
            $table->Integer('returnId')->nullable();
            $table->Integer('saleId')->nullable();
            $table->Integer('purchaseId')->nullable();
            $table->Integer('customerId')->nullable();
            $table->Integer('qty')->nullable();
            $table->Integer('productId')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_sale_items');
    }
};
