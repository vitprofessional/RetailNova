<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('damage_products', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->date('date')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->integer('qty')->default(0);
            $table->decimal('buy_price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('total', 14, 2)->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('damage_products');
    }
};
