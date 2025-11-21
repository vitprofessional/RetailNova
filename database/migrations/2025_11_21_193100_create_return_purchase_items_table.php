<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('return_purchase_items')) {
            Schema::create('return_purchase_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('returnId')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('purchase_id')->nullable()->index();
                $table->integer('qty')->default(0);
                $table->decimal('unit_price', 16, 2)->default(0);
                $table->decimal('total', 16, 2)->default(0);
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('return_purchase_items');
    }
};
