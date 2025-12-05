<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->boolean('is_walkin')->default(false);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('service_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->string('service_name');
            $table->decimal('rate', 12, 2)->default(0);
            $table->integer('qty')->default(1);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
            $table->foreign('invoice_id')->references('id')->on('service_invoices')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_invoice_items');
        Schema::dropIfExists('service_invoices');
    }
};
