<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('rmas')) {
            Schema::create('rmas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('product_serial_id')->nullable()->index();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('open'); // open, in_progress, resolved, closed
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('product_serial_id')->references('id')->on('product_serials')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('rmas');
    }
};
