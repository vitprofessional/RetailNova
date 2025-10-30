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
        Schema::create('return_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->integer('returnId')->nullable();
            $table->integer('purchaseId')->nullable();
            $table->integer('supplierId')->nullable();
            $table->integer('qty')->nullable();
            $table->integer('productId')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_purchase_items');
    }
};
