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
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('fullName')->nullable();
            $table->string('storeName')->nullable();
            $table->string('sureName')->nullable();
            $table->string('contactNumber')->nullable();
            $table->string('mail')->nullable();
            $table->string('password')->nullable();
            $table->string('businessId')->nullable();
            $table->string('accStatus')->nullable();
            $table->string('dob')->nullable();
            $table->string('employeeId')->nullable();
            $table->string('designation')->nullable();
            $table->string('profilePhoto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
