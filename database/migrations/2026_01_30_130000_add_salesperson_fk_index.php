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
        Schema::table('sale_products', function (Blueprint $table) {
            if (Schema::hasColumn('sale_products', 'salespersonId')) {
                // Add index for faster lookups
                if (!Schema::hasColumn('sale_products', 'salespersonId_index_temp')) {
                    $table->index('salespersonId', 'sale_products_salesperson_index');
                }
                // Add foreign key if admin_users table exists
                if (Schema::hasTable('admin_users')) {
                    // Avoid duplicate foreign keys if already present
                    try {
                        $sm = Schema::getConnection()->getDoctrineSchemaManager();
                        $fkList = $sm->listTableForeignKeys('sale_products');
                        $exists = false;
                        foreach ($fkList as $fk) {
                            if (in_array('salespersonId', $fk->getLocalColumns())) { $exists = true; break; }
                        }
                        if (!$exists) {
                            $table->foreign('salespersonId', 'sale_products_salesperson_fk')->references('id')->on('admin_users')->onDelete('set null');
                        }
                    } catch (\Throwable $_) {
                        // Doctrine not available or FK exists; ignore
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_products', function (Blueprint $table) {
            try{ $table->dropForeign('sale_products_salesperson_fk'); }catch(\Throwable $_){}
            try{ $table->dropIndex('sale_products_salesperson_index'); }catch(\Throwable $_){}
        });
    }
};
