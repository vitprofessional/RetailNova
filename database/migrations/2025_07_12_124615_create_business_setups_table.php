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
        Schema::create('business_setups', function (Blueprint $table) {
            $table->id();
            $table->string('businessName')->nullable();
            $table->string('businessLocation')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('tinCert')->nullable();
            $table->string('invoiceFooter')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('youtube')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('website')->nullable();
            $table->string('businessType')->nullable();
            $table->string('status')->nullable();
            $table->string('businessLogo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_setups');
    }
};
