<?php

use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Short helper to print and flush
function out($msg){ echo $msg . PHP_EOL; }

// Import models
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\PurchaseProduct;
use App\Models\ProductStock;
use App\Models\ProductSerial;
use App\Models\SaleProduct;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();
try {
    out('Starting serial flow test...');

    // Ensure we have a product
    $product = Product::first();
    if (!$product) {
        $product = Product::create(['name' => 'TEST PRODUCT ' . time()]);
        out('Created test product id=' . $product->id);
    } else {
        out('Using existing product id=' . $product->id);
    }

    // Ensure supplier
    $supplier = Supplier::first();
    if (!$supplier) {
        $supplier = Supplier::create(['name' => 'Test Supplier ' . time()]);
        out('Created supplier id=' . $supplier->id);
    } else {
        out('Using existing supplier id=' . $supplier->id);
    }

    // Ensure customer
    $customer = Customer::first();
    if (!$customer) {
        $customer = Customer::create(['name' => 'Test Customer ' . time(), 'mobile' => '000']);
        out('Created customer id=' . $customer->id);
    } else {
        out('Using existing customer id=' . $customer->id);
    }

    // Create a purchase with 2 qty and two serials
    $purchase = new PurchaseProduct();
    $purchase->productName = $product->id;
    $purchase->supplier = $supplier->id;
    $purchase->purchase_date = Carbon::now()->toDateString();
    $purchase->invoice = 'TEST-PUR-' . time();
    $purchase->qty = 2;
    $purchase->buyPrice = 100;
    $purchase->salePriceExVat = 150;
    $purchase->totalAmount = 200;
    $purchase->grandTotal = 200;
    $purchase->paidAmount = 0;
    $purchase->dueAmount = 200;
    $purchase->save();
    out('Created purchase id=' . $purchase->id);

    // Create stock row
    $stock = new ProductStock();
    $stock->productId = $product->id;
    $stock->purchaseId = $purchase->id;
    $stock->currentStock = 2;
    $stock->save();
    out('Created stock id=' . $stock->id . ' currentStock=' . $stock->currentStock);

    // Add two serials
    $serials = ['TSERIAL-' . time() . '-A', 'TSERIAL-' . time() . '-B'];
    foreach ($serials as $s) {
        $ps = new ProductSerial();
        $ps->serialNumber = $s;
        $ps->productId = $product->id;
        if (Schema::hasColumn('product_serials', 'purchaseId')) {
            $ps->purchaseId = $purchase->id;
        }
        if (Schema::hasColumn('product_serials', 'status')) {
            $ps->status = 'available';
        }
        $ps->save();
        out('Created serial id=' . $ps->id . ' serial=' . $ps->serialNumber);
    }

    // Create a sale that consumes 1 qty using the first serial
    $sale = new SaleProduct();
    $sale->invoice = 'TEST-SALE-' . time();
    $sale->date = Carbon::now()->toDateString();
    $sale->customerId = $customer->id;
    $sale->reference = 'testref';
    $sale->note = 'test sale';
    $sale->totalSale = 150;
    $sale->discountAmount = 0;
    $sale->grandTotal = 150;
    $sale->paidAmount = 150;
    $sale->invoiceDue = 0;
    $sale->prevDue = 0;
    $sale->curDue = 0;
    $sale->status = 'Ordered';
    $sale->save();
    out('Created sale id=' . $sale->id);

    // Create invoice item
    $item = new InvoiceItem();
    $item->purchaseId = $purchase->id;
    $item->saleId = $sale->id;
    $item->qty = 1;
    $item->salePrice = 150;
    $item->buyPrice = 100;
    $item->totalSale = 150;
    $item->totalPurchase = 100;
    $item->profitTotal = 50;
    $item->profitMargin = 50;
    $item->save();
    out('Created invoice item id=' . $item->id);

    // Decrease stock manually
    $stock->currentStock = max(0, $stock->currentStock - 1);
    $stock->save();
    out('Decreased stock, now ' . $stock->currentStock);

    // Mark the first serial as sold
    $firstSerial = ProductSerial::where('purchaseId', $purchase->id)->first();
    if ($firstSerial) {
        $firstSerial->saleId = $sale->id;
        $firstSerial->status = 'sold';
        $firstSerial->sold_at = Carbon::now();
        $firstSerial->save();
        out('Marked serial id=' . $firstSerial->id . ' as sold');
    }

    DB::commit();

    // Print serials
    out('Final serial rows for this purchase:');
    $rows = ProductSerial::where('purchaseId', $purchase->id)->get();
    foreach ($rows as $r) {
        $soldAt = null;
        try {
            if ($r->sold_at instanceof \Carbon\Carbon) {
                $soldAt = $r->sold_at->toDateTimeString();
            } elseif (!empty($r->sold_at)) {
                $soldAt = (string) $r->sold_at;
            }
        } catch (\Throwable $e) {
            $soldAt = is_scalar($r->sold_at) ? (string)$r->sold_at : null;
        }

        out(json_encode([
            'id' => $r->id,
            'serial' => $r->serialNumber,
            'status' => $r->status ?? null,
            'saleId' => $r->saleId ?? null,
            'sold_at' => $soldAt
        ]));
    }

    exit(0);
} catch (Exception $e) {
    DB::rollBack();
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
