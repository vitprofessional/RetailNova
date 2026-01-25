<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class InvoiceCodeUnitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function invoice_shows_product_barcode_as_code_and_unit_name()
    {
        // Create unit
        $unitId = DB::table('product_units')->insertGetId([
            'name' => 'UnitX',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create product with barcode and unit
        $productId = DB::table('products')->insertGetId([
            'name' => 'Test Item',
            'unitName' => $unitId,
            'barCode' => 'SKU-123ABC',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a purchase row for the product
        $purchaseId = DB::table('purchase_products')->insertGetId([
            'productName' => $productId,
            'qty' => 2,
            'buyPrice' => 100,
            'salePriceExVat' => 150,
            'purchase_date' => now()->toDateString(),
            'invoice' => 'PINV-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a sale
        $saleId = DB::table('sale_products')->insertGetId([
            'date' => now()->toDateString(),
            'invoice' => 'SINV-001',
            'grandTotal' => 300,
            'paidAmount' => 100,
            'curDue' => 200,
            'status' => 'Ordered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create an invoice item linked to the sale and purchase
        DB::table('invoice_items')->insert([
            'saleId' => $saleId,
            'purchaseId' => $purchaseId,
            'qty' => 2,
            'salePrice' => 150,
            'buyPrice' => 100,
            'warranty_days' => '365',
            'totalSale' => 300,
            'totalPurchase' => 200,
            'profitTotal' => 100,
            'profitMargin' => '33.33',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create and authenticate an admin user so the layout renders
        $adminId = DB::table('admin_users')->insertGetId([
            'fullName' => 'Admin Tester',
            'sureName' => 'Tester',
            'storeName' => 'Test Store',
            'mail' => 'admin@test.local',
            'contactNumber' => '0123456789',
            'password' => bcrypt('secret'),
            'role' => 'superadmin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $admin = \App\Models\AdminUser::find($adminId);
        $this->actingAs($admin, 'admin');

        $response = $this->get(route('invoiceGenerate', ['id' => $saleId]));
        $response->assertStatus(200);

        // Assert barcode (Code) and unit name appear in the rendered invoice
        $response->assertSee('SKU-123ABC');
        $response->assertSee('UnitX');
        $response->assertSee('Warranty (days)');
    }
}
