<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SaleReturnDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_details_page_opens_for_saved_return_record()
    {
        $adminId = DB::table('admin_users')->insertGetId([
            'fullName' => 'Super Admin',
            'sureName' => 'Admin',
            'storeName' => 'RetailNova',
            'mail' => 'super@example.test',
            'contactNumber' => '0123456789',
            'password' => Hash::make('password123'),
            'businessId' => 1,
            'role' => 'superadmin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $admin = AdminUser::find($adminId);
        $this->actingAs($admin, 'admin');

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Test Customer',
            'mail' => 'customer@example.test',
            'mobile' => '01700000000',
            'city' => 'Dhaka',
            'area' => 'Mirpur',
            'businessId' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'name' => 'Test Product',
            'barCode' => 'TEST-PROD-001',
            'businessId' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchaseId = DB::table('purchase_products')->insertGetId([
            'productName' => $productId,
            'supplier' => 1,
            'purchase_date' => now()->toDateString(),
            'invoice' => 'PUR-RET-001',
            'qty' => 2,
            'buyPrice' => 100,
            'salePriceExVat' => 150,
            'totalAmount' => 200,
            'grandTotal' => 200,
            'paidAmount' => 200,
            'dueAmount' => 0,
            'businessId' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saleId = DB::table('sale_products')->insertGetId([
            'date' => now()->toDateString(),
            'invoice' => 'SALE-RET-001',
            'customerId' => $customerId,
            'reference' => 'REF-RET-001',
            'note' => 'Test sale',
            'totalSale' => 300,
            'discountAmount' => 0,
            'grandTotal' => 300,
            'paidAmount' => 300,
            'invoiceDue' => 0,
            'prevDue' => 0,
            'curDue' => 0,
            'status' => 'complete',
            'businessId' => 1,
            'salespersonId' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoice_items')->insert([
            'saleId' => $saleId,
            'purchaseId' => $purchaseId,
            'qty' => 2,
            'salePrice' => 150,
            'buyPrice' => 100,
            'totalSale' => 300,
            'totalPurchase' => 200,
            'profitTotal' => 100,
            'profitMargin' => 33.33,
            'businessId' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $returnId = DB::table('sale_returns')->insertGetId([
            'saleId' => $saleId,
            'totalReturnAmount' => 150,
            'adjustAmount' => 0,
            'returnNote' => 'Customer changed mind',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('return_sale_items')->insert([
            'returnId' => $returnId,
            'saleId' => $saleId,
            'productId' => $productId,
            'purchaseId' => $purchaseId,
            'customerId' => $customerId,
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('saleReturnDetails', ['id' => $returnId]));
        $response->assertStatus(200);
        $response->assertSee('Sale Return Details');
        $response->assertSee('SALE-RET-001');
        $response->assertSee('Test Product');
        $response->assertSee('Customer changed mind');
    }
}
