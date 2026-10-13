<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class UpdatePurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\BusinessSetup::forceCreate(['id'=>1,'businessName'=>'Purchase QA','businessType'=>'computer_shop']);
        $this->actingAs(\App\Models\AdminUser::create(['fullName'=>'Purchase QA','mail'=>'purchase@example.test','password'=>'unused','role'=>'superadmin','businessId'=>1]),'admin');
    }


    /** @test */
    public function update_path_updates_existing_rows_adjusts_stock_and_can_create_new_row()
    {
        // seed supplier and two products
        $supplierId = DB::table('suppliers')->insertGetId([
            'businessId'=>1, 'name' => 'Upd Supplier', 'mail'=>'upd@example.test', 'mobile'=>'000', 'created_at'=>now(), 'updated_at'=>now()
        ]);

        $productA = DB::table('products')->insertGetId(['businessId'=>1, 'name' => 'Upd A', 'created_at'=>now(), 'updated_at'=>now()]);
        $productB = DB::table('products')->insertGetId(['businessId'=>1, 'name' => 'Upd B', 'created_at'=>now(), 'updated_at'=>now()]);
        $productC = DB::table('products')->insertGetId(['businessId'=>1, 'name' => 'Upd C', 'created_at'=>now(), 'updated_at'=>now()]);

        // Both existing items belong to an explicit invoice identity, never inferred from invoice text.
        $header=\App\Models\PurchaseInvoice::create(['businessId'=>1,'supplier_id'=>$supplierId,'purchase_date'=>now()->toDateString(),'total_amount'=>110,'grand_total'=>110,'due_amount'=>110]);
        // create two existing purchases
        $purchase1 = DB::table('purchase_products')->insertGetId([
            'purchase_invoice_id'=>$header->id, 'businessId'=>1, 'productName' => (string)$productA,
            'supplier' => (string)$supplierId,
            'purchase_date' => now()->toDateString(),
            'qty' => 5,
            'buyPrice' => '10',
            'salePriceExVat' => '15',
            'created_at'=>now(), 'updated_at'=>now()
        ]);

        $purchase2 = DB::table('purchase_products')->insertGetId([
            'purchase_invoice_id'=>$header->id, 'businessId'=>1, 'productName' => (string)$productB,
            'supplier' => (string)$supplierId,
            'purchase_date' => now()->toDateString(),
            'qty' => 3,
            'buyPrice' => '20',
            'salePriceExVat' => '25',
            'created_at'=>now(), 'updated_at'=>now()
        ]);

        // create product stocks
        DB::table('product_stocks')->insert([
            ['businessId'=>1, 'purchaseId' => (string)$purchase1, 'productId' => (string)$productA, 'currentStock' => 5, 'created_at'=>now(), 'updated_at'=>now()],
            ['businessId'=>1, 'purchaseId' => (string)$purchase2, 'productId' => (string)$productB, 'currentStock' => 3, 'created_at'=>now(), 'updated_at'=>now()],
        ]);

        // Prepare payload: update purchase1 qty => 7 (+2), update purchase2 qty => 1 (-2), and add a new row for productC qty 4
        $payload = [
            // single global fields
            'businessId'=>1, 'purchaseDate' => now()->toDateString(),
            'supplierName' => $supplierId,
            'invoiceData' => 'INV-UPDATE',
            'refData' => 'REF-UPDATE',

            // purchaseId[] - correspond to indexes
            'purchaseId' => [ (string)$purchase1, (string)$purchase2, '' ],
            'productName' => [ (string)$productA, (string)$productB, (string)$productC ],
            'quantity' => [ 7, 1, 4 ],
            'buyPrice' => [ '11', '19', '12' ],
            'salePriceExVat' => [ '16', '22', '18' ],
            'vatStatus' => ['', '', ''],
            'profitMargin' => ['', '', ''],
            'totalAmount' => [77, 22, 48],
            // serials nested arrays
            'serialNumber' => [ ['PA-1'], ['PB-1'], ['PC-1','PC-2'] ],
            'discountStatus' => '',
            'discountAmount' => 0,
            'discountPercent' => 0,
            'grandTotal' => 147,
            'paidAmount' => 0,
            'dueAmount' => 147,
            'specialNote' => 'Update test'
        ];


        $resp = $this->post(route('savePurchase'), $payload);
        $resp->assertStatus(302)->assertSessionHasNoErrors();

        // purchase1 qty should be updated
        $this->assertDatabaseHas('purchase_products', ['id' => $purchase1, 'qty' => 7]);
        // product_stocks for purchase1 increased by +2 => 7
        $this->assertDatabaseHas('product_stocks', ['businessId'=>1, 'purchaseId' => (string)$purchase1, 'productId' => (string)$productA, 'currentStock' => 7]);

        // purchase2 qty updated to 1
        $this->assertDatabaseHas('purchase_products', ['id' => $purchase2, 'qty' => 1]);
        // product_stocks for purchase2 decreased by 2 => 1
        $this->assertDatabaseHas('product_stocks', ['businessId'=>1, 'purchaseId' => (string)$purchase2, 'productId' => (string)$productB, 'currentStock' => 1]);

        // new row for productC created
        $this->assertDatabaseHas('purchase_products', ['purchase_invoice_id'=>$header->id, 'businessId'=>1, 'productName' => (string)$productC, 'qty' => 4]);
        // stock for new purchase exists with qty 4
        $this->assertDatabaseHas('product_stocks', ['productId' => (string)$productC, 'currentStock' => 4]);

        // serials present
        $this->assertDatabaseHas('product_serials', ['serialNumber' => 'PA-1']);
        $this->assertDatabaseHas('product_serials', ['serialNumber' => 'PB-1']);
        $this->assertDatabaseHas('product_serials', ['serialNumber' => 'PC-1']);
        $this->assertDatabaseHas('product_serials', ['serialNumber' => 'PC-2']);
    }
}
