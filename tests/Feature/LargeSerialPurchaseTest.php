<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class LargeSerialPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function createSupplierAndProduct(): array
    {
        $supplierId = DB::table('suppliers')->insertGetId([
            'name' => 'Perf Supplier',
            'mail' => 'perf@example.test',
            'mobile' => '0123456789',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'name' => 'Perf Product',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$supplierId, $productId];
    }

    /** @test */
    public function purchase_with_200_serials_succeeds_and_creates_200_rows()
    {
        [$supplierId, $productId] = $this->createSupplierAndProduct();

        $prefix = 'P200-' . time() . '-';
        $serials = [];
        for ($i = 1; $i <= 200; $i++) { $serials[] = $prefix . $i; }

        $payload = [
            'purchaseDate' => now()->toDateString(),
            'supplierName' => $supplierId,
            'invoiceData'  => 'INV-P200',
            'refData'      => 'REF-P200',
            'productName'  => [$productId],
            'quantity'     => [200],
            'buyPrice'     => [10],
            'salePriceExVat' => [15],
            'vatStatus'    => [''],
            'profitMargin' => [''],
            'totalAmount'  => [2000],
            'serialNumber' => [ $serials ],
            'discountStatus' => '',
            'discountAmount' => 0,
            'discountPercent' => 0,
            'grandTotal' => 2000,
            'paidAmount' => 0,
            'dueAmount' => 2000,
            'specialNote' => 'Perf 200 test',
        ];

        $this->withoutMiddleware();
        $start = microtime(true);
        $response = $this->post(route('savePurchase'), $payload);
        $elapsed = microtime(true) - $start;

        $response->assertStatus(302);

        // Assert 200 serials created matching the prefix
        $count = DB::table('product_serials')->where('serialNumber', 'like', $prefix . '%')->count();
        $this->assertSame(200, $count, 'Expected 200 product_serials rows');

        // Basic performance sanity (loose upper bound to catch pathological cases)
        $this->assertTrue($elapsed < 5.0, 'Expected request to complete under 5s, got ' . round($elapsed, 3) . 's');
    }

    /** @test */
    public function purchase_with_500_serials_succeeds_and_creates_500_rows()
    {
        [$supplierId, $productId] = $this->createSupplierAndProduct();

        $prefix = 'P500-' . time() . '-';
        $serials = [];
        for ($i = 1; $i <= 500; $i++) { $serials[] = $prefix . $i; }

        $payload = [
            'purchaseDate' => now()->toDateString(),
            'supplierName' => $supplierId,
            'invoiceData'  => 'INV-P500',
            'refData'      => 'REF-P500',
            'productName'  => [$productId],
            'quantity'     => [500],
            'buyPrice'     => [10],
            'salePriceExVat' => [15],
            'vatStatus'    => [''],
            'profitMargin' => [''],
            'totalAmount'  => [5000],
            'serialNumber' => [ $serials ],
            'discountStatus' => '',
            'discountAmount' => 0,
            'discountPercent' => 0,
            'grandTotal' => 5000,
            'paidAmount' => 0,
            'dueAmount' => 5000,
            'specialNote' => 'Perf 500 test',
        ];

        $this->withoutMiddleware();
        $start = microtime(true);
        $response = $this->post(route('savePurchase'), $payload);
        $elapsed = microtime(true) - $start;

        $response->assertStatus(302);

        // Assert 500 serials created matching the prefix
        $count = DB::table('product_serials')->where('serialNumber', 'like', $prefix . '%')->count();
        $this->assertSame(500, $count, 'Expected 500 product_serials rows');

        // Basic performance sanity
        $this->assertTrue($elapsed < 10.0, 'Expected request to complete under 10s, got ' . round($elapsed, 3) . 's');
    }
}
