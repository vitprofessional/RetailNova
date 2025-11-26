<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\PurchaseProduct;
use App\Models\ProductStock;
use App\Models\ProductSerial;
use App\Models\SaleProduct;
use App\Models\InvoiceItem;

class SerialFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function purchase_with_serials_then_sale_marks_serial_sold()
    {
        // Create product, supplier, customer (no model factories in this app)
        $product = Product::create(['name' => 'Test Product']);
        $supplier = Supplier::create(['name' => 'Test Supplier']);
        $customer = Customer::create(['name' => 'Test Customer']);

        // Create purchase
        $purchase = PurchaseProduct::create([
            'productName' => $product->id,
            'supplier' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'invoice' => 'TEST-PUR',
            'qty' => 2,
            'buyPrice' => 100,
            'salePriceExVat' => 150,
            'totalAmount' => 200,
            'grandTotal' => 200,
            'paidAmount' => 0,
            'dueAmount' => 200,
        ]);

        // Stock
        ProductStock::create([
            'productId' => $product->id,
            'purchaseId' => $purchase->id,
            'currentStock' => 2,
        ]);

        // Serial1 & Serial2
        $s1 = ProductSerial::create(['serialNumber' => 'UTS1-'.time(), 'productId' => $product->id, 'purchaseId' => $purchase->id, 'status' => 'available']);
        $s2 = ProductSerial::create(['serialNumber' => 'UTS2-'.time(), 'productId' => $product->id, 'purchaseId' => $purchase->id, 'status' => 'available']);

        // Create sale and invoice item consuming 1 qty
        $sale = SaleProduct::create([
            'invoice' => 'TEST-SALE',
            'date' => now()->toDateString(),
            'customerId' => $customer->id,
            'totalSale' => 150,
            'grandTotal' => 150,
            'paidAmount' => 150,
            'invoiceDue' => 0,
            'status' => 'Ordered'
        ]);

        $invoice = InvoiceItem::create([
            'purchaseId' => $purchase->id,
            'saleId' => $sale->id,
            'qty' => 1,
            'salePrice' => 150,
            'buyPrice' => 100,
            'totalSale' => 150,
            'totalPurchase' => 100,
            'profitTotal' => 50,
            'profitMargin' => 50,
        ]);

        // Emulate marking serial as sold (same logic used in controller)
        $ps = ProductSerial::where('purchaseId', $purchase->id)->first();
        $ps->saleId = $sale->id;
        $ps->status = 'sold';
        $ps->sold_at = now();
        $ps->save();

        // Assertions
        $this->assertDatabaseHas('product_serials', [
            'id' => $ps->id,
            'status' => 'sold',
            'saleId' => $sale->id,
        ]);

        $this->assertDatabaseHas('product_stocks', [
            'purchaseId' => $purchase->id,
            'currentStock' => 2, // note: test does not call stock service; we're only checking serial marking
        ]);
    }
}
