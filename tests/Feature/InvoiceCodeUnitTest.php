<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class InvoiceCodeUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_shows_barcode_and_warranty_and_loads_the_product_unit()
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

        // ae81261 replaced the printed Unit column with Warranty (days).
        // Keep the unit fixture to verify the controller join without restoring that column.
        $response->assertViewHas('items', function ($items) {
            return $items->count() === 1 && $items->first()->unit === 'UnitX'
                && (string)$items->first()->warranty_days === '365';
        });
        $response->assertSee('SKU-123ABC');
        $response->assertSee('Warranty (days)');
        $response->assertSee('data-label="Warranty (days)" class="text-center">365</td>', false);

        $mobile = $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile')
            ->get(route('invoiceGenerate', ['id' => $saleId]));
        $mobile->assertOk()->assertSee('data-mobile-sale-invoice', false)
            ->assertSee('data-invoice-product', false)->assertSee('Test Item')
            ->assertDontSee('<div class="invoice-qr-section', false)
            ->assertDontSee('id="invoiceBodyA4"', false)
            ->assertDontSee('id="invoiceBodyThermal"', false);

        // The user-facing /invoice route must resolve display fields through the
        // tenant-scoped purchase/product relations instead of returning raw rows.
        $mobileInvoicePage = $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile')
            ->get(route('invoicePage', ['id' => $saleId]));
        $mobileInvoicePage->assertOk()->assertSee('data-mobile-sale-invoice', false)
            ->assertSee('Test Item')->assertSee('data-invoice-product', false)->assertSee('×')
            ->assertDontSee('id="invoiceBodyA4"', false);

        $thermal = $this->get(route('invoiceThermalPrint', ['id' => $saleId, 'printer' => 'thermal58']));
        $thermal->assertOk()->assertSee('Test Item');

        DB::table('invoice_items')->insert([
            'saleId' => $saleId, 'purchaseId' => 999999, 'qty' => 1, 'salePrice' => 20,
            'buyPrice' => 10, 'totalSale' => 20, 'totalPurchase' => 10,
            'profitTotal' => 10, 'profitMargin' => '50', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $mobileWithHistoricalLine = $this->get(route('invoiceGenerate', ['id' => $saleId]));
        $mobileWithHistoricalLine->assertOk()->assertSee(__('invoices.item').' #999999')
            ->assertDontSee(__('invoices.no_items_found'));
        $this->assertSame(2, substr_count($mobileWithHistoricalLine->getContent(), 'data-invoice-product'));
    }
}
