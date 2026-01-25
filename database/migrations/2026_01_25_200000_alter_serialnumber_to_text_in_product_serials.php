<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_serials', function (Blueprint $table) {
            // Change serialNumber to text to remove 255-char limit
            $table->text('serialNumber')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_serials', function (Blueprint $table) {
            // Revert back to string if needed
            $table->string('serialNumber')->nullable()->change();
        });
    }
};
