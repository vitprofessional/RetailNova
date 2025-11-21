<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // purchase, sale
            $table->date('seq_date');
            $table->unsignedInteger('seq');
            $table->string('invoice_number')->unique();
            $table->timestamps();
            $table->unique(['type','seq_date','seq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_sequences');
    }
};
