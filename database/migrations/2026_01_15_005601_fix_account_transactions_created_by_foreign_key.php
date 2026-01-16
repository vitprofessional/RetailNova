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
        Schema::table('account_transactions', function (Blueprint $table) {
            // Drop the existing foreign key
            $table->dropForeign(['created_by']);
            
            // Make created_by nullable to allow both users and admins
            $table->unsignedBigInteger('created_by')->nullable()->change();
            
            // Add a new column to track the user type
            $table->enum('created_by_type', ['user', 'admin'])->default('admin')->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropColumn('created_by_type');
            
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
