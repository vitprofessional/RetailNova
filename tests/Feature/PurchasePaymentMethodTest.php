<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductStock;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PurchasePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_payment_method_persists_with_partial_due_stock_and_serials(): void
    {
        $this->setupBusiness();
        $supplier=Supplier::forceCreate(['businessId'=>1,'name'=>'Local supplier','openingBalance'=>120]);
        $serialized=Product::forceCreate(['businessId'=>1,'name'=>'Serialized stock']);
        $bulk=Product::forceCreate(['businessId'=>1,'name'=>'Bulk stock']);

        $this->post(route('savePurchase'),[
            'businessId'=>1,'supplierName'=>$supplier->id,'purchaseDate'=>'2026-10-26','invoiceData'=>'Q6-PARTIAL',
            'productName'=>[$serialized->id,$bulk->id],'quantity'=>[2,3],'buyPrice'=>[10,20],
            'salePriceExVat'=>[15,28],'serialNumber'=>[['Q6-SERIAL-1','Q6-SERIAL-2'],[]],
            'warranty_days'=>[365,null],
            'discountStatus'=>'','discountAmount'=>0,'discountPercent'=>0,'grandTotal'=>80,'paidAmount'=>30,
            'dueAmount'=>50,'payment_method'=>'bkash','specialNote'=>'Q6 regression',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $invoice=DB::table('purchase_invoices')->where('invoice','Q6-PARTIAL')->firstOrFail();
        $this->assertSame('bkash',$invoice->payment_method);
        $this->assertEquals(80,(float)$invoice->grand_total);
        $this->assertEquals(30,(float)$invoice->paid_amount);
        $this->assertEquals(50,(float)$invoice->due_amount);
        $this->assertDatabaseHas('purchase_products',['purchase_invoice_id'=>$invoice->id,'productName'=>(string)$serialized->id,'qty'=>2,'payment_method'=>'bkash','warranty_days'=>'365']);
        $this->assertDatabaseHas('purchase_products',['purchase_invoice_id'=>$invoice->id,'productName'=>(string)$bulk->id,'qty'=>3,'payment_method'=>'bkash']);
        $this->assertDatabaseHas('product_stocks',['productId'=>(string)$serialized->id,'businessId'=>1,'currentStock'=>2]);
        $this->assertDatabaseHas('product_stocks',['productId'=>(string)$bulk->id,'businessId'=>1,'currentStock'=>3]);
        $this->assertSame(2,ProductSerial::where('purchaseId',$invoice->id)->count());
        $this->assertDatabaseHas('product_serials',['serialNumber'=>'Q6-SERIAL-1']);
        $this->assertDatabaseHas('product_serials',['serialNumber'=>'Q6-SERIAL-2']);

        $editRows=DB::table('purchase_products')->where('purchase_invoice_id',$invoice->id)->get()->mapWithKeys(fn($line)=>['line'.$line->id=>[
            'id'=>$line->id,'product_id'=>$line->productName,'qty'=>$line->qty,'buy_price'=>$line->buyPrice,
            'sale_price'=>$line->salePriceExVat,'vat_percent'=>$line->vatPercent,'vat_included'=>$line->vatStatus,'serials'=>'',
        ]])->all();
        $this->post(route('purchase.invoice.update',$invoice->id),[
            'purchaseDate'=>'2026-10-26','rows'=>$editRows,'discountStatus'=>'1','discountAmount'=>0,
            'discountPercent'=>0,'payment_method'=>'nagad','specialNote'=>'Q6 edited method',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('nagad',DB::table('purchase_invoices')->where('id',$invoice->id)->value('payment_method'));
    }

    public function test_purchase_without_serials_can_be_saved_as_supplier_due(): void
    {
        $this->setupBusiness();
        $supplier=Supplier::forceCreate(['businessId'=>1,'name'=>'Due supplier']);
        $product=Product::forceCreate(['businessId'=>1,'name'=>'Unserialized stock']);
        $this->post(route('savePurchase'),[
            'businessId'=>1,'supplierName'=>$supplier->id,'purchaseDate'=>'2026-10-26','invoiceData'=>'Q6-DUE',
            'productName'=>[$product->id],'quantity'=>[2],'buyPrice'=>[40],'salePriceExVat'=>[60],
            'serialNumber'=>[[]],'discountStatus'=>'','discountAmount'=>0,'discountPercent'=>0,
            'grandTotal'=>80,'paidAmount'=>0,'dueAmount'=>80,'payment_method'=>'due',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $invoice=DB::table('purchase_invoices')->where('invoice','Q6-DUE')->firstOrFail();
        $this->assertSame('due',$invoice->payment_method);
        $this->assertEquals(80,(float)$invoice->due_amount);
        $this->assertDatabaseHas('product_stocks',['purchaseId'=>$invoice->id,'productId'=>(string)$product->id,'currentStock'=>2]);
        $this->assertDatabaseHas('purchase_products',['purchase_invoice_id'=>$invoice->id,'payment_method'=>'due']);
        $this->assertDatabaseCount('product_serials',0);
    }

    public function test_payment_column_migration_and_mobile_desktop_controls_are_present(): void
    {
        $this->setupBusiness();
        $this->assertTrue(Schema::hasColumn('purchase_invoices','payment_method'));
        $this->assertTrue(Schema::hasColumn('purchase_products','payment_method'));
        $this->assertTrue(Schema::hasColumn('purchase_products','warranty_days'));
        $this->withHeader('User-Agent','Mozilla/5.0 (Linux; Android 14) Mobile')->get(route('addPurchase'))
            ->assertOk()->assertSee('data-q6-purchase',false)->assertSee('data-q6-payment',false)
            ->assertSee('data-q6-mobile-method',false)->assertSee('id="paymentMethodDesktop"',false)
            ->assertSee('data-q6-open-payment',false)->assertSee('rn-q6-line-panel',false)
            ->assertSee('data-sale-price-short=',false)->assertDontSee('mobile.sale_price_short');
        $this->withHeader('User-Agent','Mozilla/5.0 (Windows NT 10.0; Win64; x64)')->get(route('addPurchase'))
            ->assertOk()->assertSee('id="paymentMethodDesktop"',false);
    }

    public function test_purchase_return_exception_keeps_input_and_adds_persistent_error(): void
    {
        $this->setupBusiness();
        $workflow = \Mockery::mock(\App\Services\PurchaseWorkflow::class);
        $workflow->shouldReceive('lockSerialNamespace')->once()->andThrow(new \RuntimeException('simulated return failure'));
        $this->app->instance(\App\Services\PurchaseWorkflow::class, $workflow);

        $this->from(route('returnPurchase.select'))->post(route('purchaseReturnSave'), [
            'purchaseId' => 1, 'supplierId' => 1, 'productId' => 1, 'returnQty' => 1,
        ])->assertRedirect(route('returnPurchase.select'))
            ->assertSessionHasErrors(['return'])
            ->assertSessionHas('_old_input.returnQty', 1);
    }

    public function test_purchase_save_exception_keeps_input_and_adds_persistent_error(): void
    {
        $this->setupBusiness();
        $supplier = Supplier::forceCreate(['businessId'=>1,'name'=>'Retry supplier']);
        $product = Product::forceCreate(['businessId'=>1,'name'=>'Retry product']);
        $workflow = \Mockery::mock(\App\Services\PurchaseWorkflow::class);
        $workflow->shouldReceive('save')->once()->andThrow(new \RuntimeException('simulated purchase failure'));
        $this->app->instance(\App\Services\PurchaseWorkflow::class, $workflow);

        $this->from(route('addPurchase'))->post(route('savePurchase'), [
            'businessId'=>1,'supplierName'=>$supplier->id,'purchaseDate'=>'2026-10-26','invoiceData'=>'Q8-RETRY',
            'productName'=>[$product->id],'quantity'=>[1],'buyPrice'=>[10],'salePriceExVat'=>[15],
            'serialNumber'=>[[]],'discountStatus'=>'','discountAmount'=>0,'discountPercent'=>0,
            'grandTotal'=>10,'paidAmount'=>0,'dueAmount'=>10,'payment_method'=>'due',
        ])->assertRedirect(route('addPurchase'))
            ->assertSessionHasErrors(['purchase'])
            ->assertSessionHas('_old_input.invoiceData','Q8-RETRY');
    }

    private function setupBusiness(): void
    {
        BusinessSetup::forceCreate(['id'=>1,'businessName'=>'Q6 purchase QA','businessType'=>'computer_shop']);
        $actor=AdminUser::forceCreate(['fullName'=>'Q6 admin','mail'=>'q6-purchase@example.test','password'=>bcrypt('test-only'),'role'=>'admin','businessId'=>1]);
        $this->actingAs($actor,'admin');
    }
}
