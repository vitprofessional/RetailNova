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
        Schema::create('expense_entries', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');
            $table->unsignedBigInteger('category_id');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'bank', 'card', 'cheque', 'mobile']);
            $table->string('reference_no', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_file')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('business_location_id')->nullable();
            $table->unsignedBigInteger('account_transaction_id')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('expense_categories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('account_transaction_id')->references('id')->on('account_transactions')->onDelete('set null');
            
            $table->index('expense_date');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_entries');
    }
};
