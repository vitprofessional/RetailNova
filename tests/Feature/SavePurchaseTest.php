<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class SavePurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\BusinessSetup::forceCreate(['id'=>1,'businessName'=>'Purchase QA','businessType'=>'computer_shop']);
        $this->actingAs(\App\Models\AdminUser::create(['fullName'=>'Purchase QA','mail'=>'purchase@example.test','password'=>'unused','role'=>'superadmin','businessId'=>1]),'admin');
    }


    /** @test */
    public function multi_row_purchase_creates_purchase_stock_and_serials()
    {
        // Prepare minimal supplier and products
        $supplierId = DB::table('suppliers')->insertGetId([
            'businessId'=>1, 'name' => 'Test Supplier',
            'mail' => 'supplier@example.test',
            'mobile' => '0123456789',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $productA = DB::table('products')->insertGetId([
            'businessId'=>1, 'name' => 'Product A',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $productB = DB::table('products')->insertGetId([
            'businessId'=>1, 'name' => 'Product B',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $payload = [
            'businessId'=>1, 'purchaseDate' => now()->toDateString(),
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


        $response = $this->post(route('savePurchase'), $payload);

        // Expect redirect back
        $response->assertStatus(302)->assertSessionHasNoErrors();

        // Assert purchases created
        $this->assertDatabaseHas('purchase_products', [
            'businessId'=>1, 'productName' => (string)$productA,
            'qty' => 2,
        ]);

        $this->assertDatabaseHas('purchase_products', [
            'businessId'=>1, 'productName' => (string)$productB,
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

    public function test_new_purchase_uses_authenticated_business_when_request_omits_business_id(): void
    {
        $actor = auth('admin')->user();
        $actor->forceFill(['role' => 'admin', 'businessId' => 1])->save();

        $supplierId = DB::table('suppliers')->insertGetId([
            'businessId'=>1, 'name'=>'Scoped Supplier', 'created_at'=>now(), 'updated_at'=>now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'businessId'=>1, 'name'=>'Scoped Product', 'created_at'=>now(), 'updated_at'=>now(),
        ]);

        $this->post(route('savePurchase'), [
            'purchaseDate'=>now()->toDateString(), 'supplierName'=>$supplierId,
            'invoiceData'=>'INV-AUTHENTICATED-BUSINESS', 'productName'=>[$productId],
            'quantity'=>[1], 'buyPrice'=>[10], 'salePriceExVat'=>[12],
            'serialNumber'=>[[]], 'discountAmount'=>0, 'discountPercent'=>0,
            'grandTotal'=>10, 'paidAmount'=>0,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('purchase_products', [
            'invoice'=>'INV-AUTHENTICATED-BUSINESS', 'businessId'=>1, 'productName'=>$productId,
        ]);
        $this->assertDatabaseMissing('purchase_products', [
            'invoice'=>'INV-AUTHENTICATED-BUSINESS', 'businessId'=>null,
        ]);
        $this->assertDatabaseHas('product_stocks', [
            'productId'=>$productId, 'businessId'=>1, 'currentStock'=>1,
        ]);
    }

    public function test_purchase_save_redirect_and_invoice_refresh_keep_the_selected_locale_and_serials(): void
    {
        $supplierId = DB::table('suppliers')->insertGetId([
            'businessId'=>1, 'name'=>'Locale Supplier', 'created_at'=>now(), 'updated_at'=>now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'businessId'=>1, 'name'=>'Locale Product', 'created_at'=>now(), 'updated_at'=>now(),
        ]);

        foreach ([
            'bn' => ['PURCHASE-BN-SERIAL', 'চালান ও মহাজনের তথ্য'],
            'en' => ['PURCHASE-EN-SERIAL', 'Invoice & supplier information'],
        ] as $locale => [$serial, $heading]) {
            auth('admin')->user()->forceFill(['preferred_locale'=>$locale])->save();
            $this->get(route('addPurchase'))->assertOk()->assertSee('lang="'.$locale.'"', false);

            $payload = [
                'businessId'=>1, 'purchaseDate'=>now()->toDateString(), 'supplierName'=>$supplierId,
                'invoiceData'=>'INV-LOCALE-'.$locale, 'refData'=>'',
                'productName'=>[$productId], 'quantity'=>[1], 'buyPrice'=>[12],
                'salePriceExVat'=>[15], 'vatStatus'=>[''], 'profitMargin'=>[''], 'totalAmount'=>[12],
                'serialNumber'=>[[$serial]], 'discountStatus'=>'', 'discountAmount'=>0,
                'discountPercent'=>0, 'grandTotal'=>12, 'paidAmount'=>0, 'dueAmount'=>12,
                'specialNote'=>'Locale persistence regression fixture',
            ];

            $save = $this->post(route('savePurchase'), $payload)->assertRedirect();
            $invoice = DB::table('purchase_invoices')->where('invoice', 'INV-LOCALE-'.$locale)->first();
            $this->assertNotNull($invoice);
            $save->assertRedirect(route('purchase.invoice.show', $invoice->id));

            foreach ([
                $this->get(route('purchase.invoice.show', $invoice->id)),
                $this->get(route('purchase.invoice.show', $invoice->id)),
            ] as $page) {
                $page->assertOk()->assertSee('lang="'.$locale.'"', false)
                    ->assertSee($heading)->assertSee($serial);
            }
        }
    }
}
