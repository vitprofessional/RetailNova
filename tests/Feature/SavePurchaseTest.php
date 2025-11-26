<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class SavePurchaseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function multi_row_purchase_creates_purchase_stock_and_serials()
    {
        // Prepare minimal supplier and products
        $supplierId = DB::table('suppliers')->insertGetId([
            'name' => 'Test Supplier',
            'mail' => 'supplier@example.test',
            'mobile' => '0123456789',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $productA = DB::table('products')->insertGetId([
            'name' => 'Product A',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $productB = DB::table('products')->insertGetId([
            'name' => 'Product B',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $payload = [
            'purchaseDate' => now()->toDateString(),
            'supplierName' => $supplierId,
            'invoiceData'  => 'INV-TEST',
            'refData'      => 'REF-TEST',

            // arrays for two rows
            'productName' => [$productA, $productB],
            'quantity'    => [2, 3],
            'buyPrice'    => [10, 20],
            'salePriceExVat' => [15, 25],
            'vatStatus'   => ['', ''],
            'profitMargin' => ['', ''],
            'totalAmount' => [20, 60],

            // serials: nested arrays -> serialNumber[0] => array, serialNumber[1] => array
            'serialNumber' => [
                [ 'A-1', 'A-2' ],
                [ 'B-1' ]
            ],

            // global fields (copied to each row by controller)
            'discountStatus' => '',
            'discountAmount' => 0,
            'discountPercent' => 0,
            'grandTotal' => 80,
            'paidAmount' => 0,
            'dueAmount' => 80,
            'specialNote' => 'Test purchase'
        ];

        // Bypass middleware (routes are protected in web.php)
        $this->withoutMiddleware();

        $response = $this->post(route('savePurchase'), $payload);

        // Expect redirect back
        $response->assertStatus(302);

        // Assert purchases created
        $this->assertDatabaseHas('purchase_products', [
            'productName' => (string)$productA,
            'qty' => 2,
        ]);

        $this->assertDatabaseHas('purchase_products', [
            'productName' => (string)$productB,
            'qty' => 3,
        ]);

        // Assert stocks created
        $this->assertDatabaseHas('product_stocks', [
            'productId' => (string)$productA,
            'currentStock' => 2,
        ]);
        $this->assertDatabaseHas('product_stocks', [
            'productId' => (string)$productB,
            'currentStock' => 3,
        ]);

        // Assert serials created
        $this->assertDatabaseHas('product_serials', [
            'serialNumber' => 'A-1'
        ]);
        $this->assertDatabaseHas('product_serials', [
            'serialNumber' => 'A-2'
        ]);
        $this->assertDatabaseHas('product_serials', [
            'serialNumber' => 'B-1'
        ]);
    }
}
