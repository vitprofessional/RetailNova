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
        Schema::create('business_tables', function (Blueprint $table) {
            $table->id();
            $table->string('businessName')->nullable();
            $table->string('businessNumber')->nullable();
            $table->string('currencyId')->nullable();
            $table->string('businessCategories')->nullable();
            $table->string('stDate')->nullable();
            $table->string('wonerId')->nullable();
            $table->string('timeZone')->nullable();
            $table->string('businessAcc')->nullable();
            $table->string('profitPercent')->nullable();
            $table->string('taxId')->nullable();
            $table->string('tinNumber')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_tables');
    }
};
