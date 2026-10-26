<?php

namespace Tests\Feature;

use App\Models\{AdminUser, BusinessSetup, Customer, Product, ProductSerial, ProductStock, PurchaseProduct, Supplier};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint02PurchaseCustomerTest extends TestCase
{
    use RefreshDatabase;

    private function setupBusiness(): array
    {
        BusinessSetup::forceCreate(['id'=>1,'businessName'=>'Sprint QA','businessType'=>'computer_shop']);
        $user=AdminUser::create(['fullName'=>'QA','mail'=>'qa@example.test','password'=>'unused',
            'role'=>'superadmin','businessId'=>1]);
        $this->actingAs($user,'admin');
        $supplier=Supplier::create(['name'=>'QA Supplier','businessId'=>1]);
        return [$supplier, Product::create(['name'=>'Alpha','businessId'=>1]), Product::create(['name'=>'Beta','businessId'=>1])];
    }

    private function payload(array $data, array $serials=[]): array
    {
        return ['businessId'=>1,'supplierName'=>$data[0]->id,'purchaseDate'=>'2026-10-12',
            'invoiceData'=>'QA-SAME-NUMBER','productName'=>[2=>$data[1]->id,7=>$data[2]->id],
            'quantity'=>[2=>2,7=>3],'buyPrice'=>[2=>10,7=>20],'salePriceExVat'=>[2=>15,7=>25],
            'totalAmount'=>[2=>20,7=>60],'paidAmount'=>30,'grandTotal'=>80,'serialNumber'=>$serials];
    }

    public function test_sparse_row_serials_persist_on_first_save_without_cross_assignment(): void
    {
        $data=$this->setupBusiness();
        $this->post(route('savePurchase'),$this->payload($data,[2=>['ALPHA-1','ALPHA-2'],7=>['BETA-1']]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('purchase_products',2);
        foreach (['ALPHA-1'=>$data[1]->id,'ALPHA-2'=>$data[1]->id,'BETA-1'=>$data[2]->id] as $serial=>$product) {
            $record=ProductSerial::where('serialNumber',$serial)->firstOrFail();
            $this->assertEquals($product,$record->productId);
            $this->assertEquals($product,PurchaseProduct::findOrFail($record->purchaseId)->productName);
        }
        $this->assertDatabaseCount('purchase_invoices',1);
    }

    public function test_purchase_without_serials_and_invoice_totals_are_not_duplicated(): void
    {
        $data=$this->setupBusiness();
        $this->post(route('savePurchase'),$this->payload($data))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('product_serials',0);
        $this->assertEquals(5,ProductStock::sum('currentStock'));
        $this->assertEquals(80,PurchaseProduct::sum('grandTotal'));
        $this->assertEquals(30,PurchaseProduct::sum('paidAmount'));
        $this->assertEquals(50,PurchaseProduct::sum('dueAmount'));
    }

    public function test_duplicate_serial_rolls_back_all_rows_stock_and_header(): void
    {
        $data=$this->setupBusiness();
        $this->postJson(route('savePurchase'),$this->payload($data,[2=>['DUP'],7=>['DUP']]))->assertUnprocessable();
        $this->assertDatabaseCount('purchase_products',0);
        $this->assertDatabaseCount('product_stocks',0);
        $this->assertDatabaseCount('product_serials',0);
    }

    public function test_customer_empty_email_is_null_and_can_be_cleared(): void
    {
        $this->setupBusiness();
        $this->post(route('saveCustomer'),['fullName'=>'No Email','businessId'=>1])->assertRedirect()->assertSessionHasNoErrors();
        $customer=Customer::where('name','No Email')->firstOrFail();
        $this->assertNull($customer->mail);
        $customer->update(['mail'=>'clear@example.test']);
        $this->post(route('saveCustomer'),['profileId'=>$customer->id,'fullName'=>'No Email','mail'=>''])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($customer->fresh()->mail);
        $this->postJson(route('saveCustomer'),['fullName'=>'Invalid Email','mail'=>'invalid'])->assertUnprocessable();
    }

    public function test_distinct_same_number_invoices_have_separate_header_rows_and_details(): void
    {
        $data=$this->setupBusiness();
        for($i=0;$i<2;$i++){$this->post(route('savePurchase'),$this->payload($data))->assertSessionHasNoErrors();}
        $page=$this->get(route('purchaseList'))->assertOk();
        $this->assertSame(2,substr_count($page->getContent(),'data-purchase-invoice='));
        $this->assertDatabaseCount('purchase_invoices',2);
        foreach (\App\Models\PurchaseInvoice::all() as $header) {
            $this->assertCount(2,$header->items);
            $this->get(route('purchase.invoice.show',$header->id))->assertOk()->assertSee('Alpha')->assertSee('Beta');
        }
    }

    private function createInvoice(array $serials=[]): \App\Models\PurchaseInvoice
    {
        $data=$this->setupBusiness();
        $this->post(route('savePurchase'),$this->payload($data,$serials))->assertSessionHasNoErrors();
        return \App\Models\PurchaseInvoice::firstOrFail();
    }

    private function editPayload($invoice): array
    {
        return ['purchaseDate'=>'2026-10-12','rows'=>$invoice->items->mapWithKeys(fn($line)=>['line'.$line->id=>[
            'id'=>$line->id,'product_id'=>$line->productName,'qty'=>$line->qty,'buy_price'=>$line->buyPrice,
            'sale_price'=>$line->salePriceExVat,'serials'=>'']])->all()];
    }

    public function test_edit_without_new_serials_preserves_records_and_reconciles_totals(): void
    {
        $invoice=$this->createInvoice([2=>['KEEP-A'],7=>['KEEP-B']]);$data=$this->editPayload($invoice);
        $first=$invoice->items->first();$data['rows']['line'.$first->id]['qty']=4;
        $this->post(route('purchase.invoice.update',$invoice->id),$data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('product_serials',2);
        $this->assertSame(4,(int)$first->stock->fresh()->currentStock);
        $this->assertEquals(100,$invoice->fresh()->grand_total);
        $this->assertEquals(30,$invoice->fresh()->paid_amount);
        $this->assertEquals(70,$invoice->fresh()->due_amount);
        $this->assertEquals(100,PurchaseProduct::sum('grandTotal'));
        $this->get(route('purchase.invoice.edit',$invoice->id))->assertOk()->assertSee('KEEP-A')->assertSee('KEEP-B');
    }

    public function test_serial_edit_add_and_remove_are_atomic_and_duplicates_rollback(): void
    {
        $invoice=$this->createInvoice([2=>['OLD-A'],7=>['OLD-B']]);$data=$this->editPayload($invoice);
        $first=$invoice->items->first();$serial=$first->serials->first();
        $data['rows']['line'.$first->id]['serial_updates']=[$serial->id=>'CHANGED-A'];
        $data['rows']['line'.$first->id]['serials']='NEW-A';
        $this->post(route('purchase.invoice.update',$invoice->id),$data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_serials',['id'=>$serial->id,'serialNumber'=>'CHANGED-A']);
        $this->assertDatabaseHas('product_serials',['serialNumber'=>'NEW-A','purchaseId'=>$first->id]);
        $data=$this->editPayload($invoice->fresh());$data['rows']['line'.$first->id]['remove_serials']=[$serial->id];
        $data['rows']['line'.$first->id]['serials']='OLD-B';
        $this->postJson(route('purchase.invoice.update',$invoice->id),$data)->assertUnprocessable();
        $this->assertDatabaseHas('product_serials',['id'=>$serial->id,'serialNumber'=>'CHANGED-A']);
        $this->assertDatabaseCount('product_serials',3);
    }

    public function test_sold_serial_and_quantity_reduction_are_rejected_without_partial_updates(): void
    {
        $invoice=$this->createInvoice([2=>['SOLD-A','AVAILABLE-A']]);$line=$invoice->items->first();
        $serial=$line->serials->first();$serial->update(['saleId'=>77,'status'=>'sold']);$line->stock->update(['currentStock'=>1]);
        $data=$this->editPayload($invoice);$data['rows']['line'.$line->id]['serial_updates']=[$serial->id=>'ILLEGAL'];
        $this->postJson(route('purchase.invoice.update',$invoice->id),$data)->assertUnprocessable();
        $this->assertEquals('SOLD-A',$serial->fresh()->serialNumber);
        $remove=$this->editPayload($invoice);$remove['rows']['line'.$line->id]['remove_serials']=[$serial->id];
        $this->postJson(route('purchase.invoice.update',$invoice->id),$remove)->assertUnprocessable();
        $this->assertDatabaseHas('product_serials',['id'=>$serial->id,'serialNumber'=>'SOLD-A']);
        $data=$this->editPayload($invoice);$data['rows']['line'.$line->id]['qty']=1;
        $this->postJson(route('purchase.invoice.update',$invoice->id),$data)->assertUnprocessable();
        $this->assertEquals(2,$line->fresh()->qty);$this->assertEquals(1,$line->stock->fresh()->currentStock);
        $this->assertEquals(80,$invoice->fresh()->grand_total);
    }

    public function test_foreign_header_line_supplier_product_and_customer_are_rejected(): void
    {
        $invoice=$this->createInvoice();BusinessSetup::forceCreate(['id'=>2,'businessName'=>'Other']);
        $foreignSupplier=Supplier::create(['name'=>'Other','businessId'=>2]);
        $foreignProduct=Product::create(['name'=>'Other','businessId'=>2]);
        $foreignCustomer=Customer::create(['name'=>'Other','businessId'=>2]);
        $user=AdminUser::create(['fullName'=>'Scoped','mail'=>'scoped@example.test','password'=>'unused','role'=>'salesmanager','businessId'=>2]);
        $this->actingAs($user,'admin');
        $this->getJson(route('purchase.invoice.show',$invoice->id))->assertNotFound();
        $this->assertDenied($this->postJson(route('purchase.invoice.update',$invoice->id),$this->editPayload($invoice)));
        $this->assertDenied($this->postJson(route('purchase.invoice.bulkDelete'),['ids'=>[$invoice->id]]));
        $this->get(route('purchaseList'))->assertOk()->assertDontSee('data-purchase-invoice="'.$invoice->id.'"',false);
        $user->update(['businessId'=>1]);
        $this->assertDenied($this->postJson(route('saveCustomer'),['profileId'=>$foreignCustomer->id,'fullName'=>'Hijack']));
        $this->getJson(route('editCustomer',$foreignCustomer->id))->assertNotFound();
        $payload=['supplierName'=>$foreignSupplier->id,'businessId'=>2,'purchaseDate'=>'2026-10-12','productName'=>[$foreignProduct->id],'quantity'=>[1],'buyPrice'=>[10]];
        $this->assertDenied($this->postJson(route('savePurchase'),$payload));
        $payload['supplierName']=$invoice->supplier_id;
        $this->assertDenied($this->postJson(route('savePurchase'),$payload));
        $this->assertDatabaseCount('purchase_invoices',1);
    }

    public function test_safe_item_removal_and_bulk_header_ids_preserve_paid_invoices(): void
    {
        $invoice=$this->createInvoice();$data=$this->editPayload($invoice);
        $last=$invoice->items->last();unset($data['rows']['line'.$last->id]);
        // Remaining value is below recorded payment: deleting this item must roll back.
        $this->postJson(route('purchase.invoice.update',$invoice->id),$data)->assertUnprocessable();
        $this->assertDatabaseHas('purchase_products',['id'=>$last->id]);
        $this->deleteJson(route('purchase.invoice.destroy',$invoice->id))->assertUnprocessable();
        $this->assertDatabaseCount('purchase_invoices',1);
        $invoice->update(['paid_amount'=>0]);$invoice->items()->update(['paidAmount'=>0]);
        $this->post(route('purchase.invoice.bulkDelete'),['ids'=>[$invoice->id]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('purchase_invoices',0);$this->assertDatabaseCount('product_stocks',0);
    }

    public function test_returned_serials_and_stock_remain_consistent_and_original_invoice_is_preserved(): void
    {
        $invoice=$this->createInvoice([2=>['RETURN-A','REMAIN-A']]);$line=$invoice->items->first();$serial=$line->serials->first();
        $mobileReturn=$this->withHeader('User-Agent','Mozilla/5.0 (Linux; Android 13) Mobile')->get(route('returnPurchase',$line->id))->assertOk();
        $this->assertStringContainsString('rn-m-return-form',$mobileReturn->getContent());
        $this->assertStringContainsString('data-label="'.__('purchases.return_qty').'"',$mobileReturn->getContent());
        $this->assertSame(1,substr_count($mobileReturn->getContent(),'rn-m-return-actions'),'Purchase return actions must share one sticky row.');
        $payload=['purchaseId'=>$line->id,'supplierId'=>$line->supplier,'productId'=>$line->productName,'returnQty'=>1,'adjustAmount'=>10,'serialIds'=>[$serial->id]];
        $this->post(route('purchaseReturnSave'),$payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_serials',['id'=>$serial->id,'status'=>'returned']);
        $this->assertEquals(1,$line->stock->fresh()->currentStock);$this->assertEquals(2,$line->fresh()->qty);
        $this->assertEquals(80,$invoice->fresh()->grand_total);
        $this->assertDatabaseCount('purchase_returns',1);$this->assertDatabaseCount('return_purchase_items',1);
        $this->postJson(route('purchaseReturnSave'),$payload)->assertUnprocessable();
        $this->assertDatabaseCount('purchase_returns',1);$this->assertEquals(1,$line->stock->fresh()->currentStock);
        $this->postJson(route('purchase.invoice.update',$invoice->id),$this->editPayload($invoice))->assertUnprocessable();
    }

    public function test_reordered_canonical_rows_additions_and_rounding_reconcile(): void
    {
        $data=$this->setupBusiness();$input=$this->payload($data);
        unset($input['productName'],$input['quantity'],$input['buyPrice']);
        $input['rows']=['z'=>['product_id'=>$data[2]->id,'qty'=>3,'buy_price'=>0.01,'serials'=>['Z']],
            'a'=>['product_id'=>$data[1]->id,'qty'=>3,'buy_price'=>0.01,'serials'=>['A']]];
        $input['paidAmount']=0.05;$input['grandTotal']=0.06;$input['discountAmount']=0;
        $this->post(route('savePurchase'),$input)->assertSessionHasNoErrors();
        $invoice=\App\Models\PurchaseInvoice::firstOrFail();
        $this->assertEquals(0.06,PurchaseProduct::sum('grandTotal'));$this->assertEquals(0.05,PurchaseProduct::sum('paidAmount'));
        $this->assertEquals(0.01,PurchaseProduct::sum('dueAmount'));
        $this->assertSame(0,PurchaseProduct::where('dueAmount','<',0)->count());
        $payload=$this->editPayload($invoice);$payload['rows']=array_reverse($payload['rows'],true);
        $payload['rows']['new']=['product_id'=>$data[1]->id,'qty'=>1,'buy_price'=>1,'serials'=>''];
        $this->post(route('purchase.invoice.update',$invoice->id),$payload)->assertSessionHasNoErrors();
        $this->assertCount(3,$invoice->fresh()->items);$this->assertEquals(1.06,$invoice->fresh()->grand_total);
        $this->assertDatabaseHas('product_serials',['serialNumber'=>'Z','productId'=>$data[2]->id]);
        $this->assertDatabaseHas('product_serials',['serialNumber'=>'A','productId'=>$data[1]->id]);
    }

    public function test_invoice_search_filter_sort_and_pagination_use_headers(): void
    {
        $invoice=$this->createInvoice();$invoice->update(['invoice'=>'FIND-ME']);
        for($i=0;$i<21;$i++){\App\Models\PurchaseInvoice::create(['businessId'=>1,'supplier_id'=>$invoice->supplier_id,'invoice'=>'P-'.$i,'purchase_date'=>'2026-10-12','grand_total'=>0,'due_amount'=>0]);}
        $this->get(route('purchaseList',['q'=>'FIND-ME','status'=>'partial','supplier'=>$invoice->supplier_id,'sort'=>'invoice','direction'=>'asc']))->assertOk()->assertSee('FIND-ME')->assertDontSee('P-20');
        $page=$this->get(route('purchaseList',['sort'=>'id','direction'=>'asc']))->assertOk();
        $this->assertSame(20,substr_count($page->getContent(),'data-purchase-invoice='));
        $page=$this->get(route('purchaseList',['page'=>2]))->assertOk();$this->assertSame(2,substr_count($page->getContent(),'data-purchase-invoice='));
        $this->getJson(route('purchaseList',['sort'=>'invalid']))->assertUnprocessable();
    }

    public function test_customer_duplicate_email_and_ajax_optional_email_preserve_business(): void
    {
        $this->setupBusiness();
        $this->post(route('saveCustomer'),['fullName'=>'First','mail'=>'unique@example.test','businessId'=>1])->assertSessionHasNoErrors();
        $this->postJson(route('saveCustomer'),['fullName'=>'Second','mail'=>'unique@example.test','businessId'=>1])->assertUnprocessable();
        $this->postJson(route('createCustomer'),['fullName'=>'Ajax Customer','businessId'=>1])->assertOk();
        $this->assertDatabaseHas('customers',['name'=>'Ajax Customer','mail'=>null,'businessId'=>1]);
        $customer=Customer::where('name','First')->firstOrFail();
        $this->get(route('editCustomer',$customer->id))->assertOk();
        $this->post(route('saveCustomer'),['profileId'=>$customer->id,'fullName'=>'Edited First'])->assertSessionHasNoErrors();
        $this->assertNull($customer->fresh()->mail);$this->assertEquals(1,$customer->fresh()->businessId);
    }

    public function test_legacy_rows_remain_unmerged_and_report_identity_blocker(): void
    {
        $invoice=$this->createInvoice();$line=$invoice->items->first();$line->purchase_invoice_id=null;$line->save();
        $this->get(route('purchaseList'))->assertOk()->assertSee('data-legacy-purchase="'.$line->id.'"',false);
        $this->postJson(route('savePurchase'),['businessId'=>1,'purchaseId'=>[$line->id],'productName'=>[$line->productName],'quantity'=>[2],'buyPrice'=>[10],'supplierName'=>$line->supplier,'purchaseDate'=>'2026-10-12'])->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertNull($line->fresh()->purchase_invoice_id);$this->assertDatabaseCount('purchase_invoices',1);
    }

    public function test_partial_serial_inventory_never_fabricates_unmatched_sales_assignments(): void
    {
        $data=$this->setupBusiness();$input=$this->payload($data,[2=>['PART-1','PART-2','PART-3','PART-4']]);
        $input['quantity'][2]=10;$input['grandTotal']=160;
        $this->post(route('savePurchase'),$input)->assertSessionHasNoErrors();
        $line=PurchaseProduct::where('productName',$data[1]->id)->firstOrFail();
        $this->assertSame(10,$line->qty);$this->assertDatabaseCount('product_serials',4);
        $customer=Customer::create(['name'=>'Sale QA','businessId'=>1]);
        $payload=['businessId'=>1,'date'=>'2026-10-12','customerId'=>$customer->id,'purchaseData'=>[$line->id],'qty'=>[5],'salePrice'=>[15],'buyPrice'=>[10],
            'totalSaleAmount'=>75,'grandTotal'=>75,'paidAmount'=>0,'dueAmount'=>75,'prevDue'=>0,'curDue'=>75,'discountAmount'=>0,'additionalChargeAmount'=>0,
            'serialId'=>[$line->serials->pluck('id')->all()]];
        $this->postJson(route('saveSale'),$payload)->assertUnprocessable();
        $this->assertEquals(10,$line->stock->fresh()->currentStock);$this->assertDatabaseCount('sale_products',0);
        BusinessSetup::find(1)->update(['sales_serial_selection_required'=>false]);$payload['serialId']=[[]];
        $this->postJson(route('saveSale'),$payload)->assertUnprocessable();
        $payload['qty']=[4];$payload['totalSaleAmount']=$payload['grandTotal']=$payload['dueAmount']=$payload['curDue']=60;
        $this->post(route('saveSale'),$payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals(6,$line->stock->fresh()->currentStock);
        $this->assertSame(4,ProductSerial::where('status','sold')->count());
        $payload['qty']=[1];$payload['totalSaleAmount']=$payload['grandTotal']=$payload['dueAmount']=$payload['curDue']=15;
        $this->postJson(route('saveSale'),$payload)->assertUnprocessable();
        $this->assertDatabaseCount('product_serials',4);$this->assertEquals(6,$line->stock->fresh()->currentStock);
    }

    public function test_ordinary_business_admin_cannot_cross_business_purchase_or_customer_boundaries(): void
    {
        $invoice=$this->createInvoice();BusinessSetup::forceCreate(['id'=>2,'businessName'=>'Other']);
        $actor=AdminUser::create(['role'=>'admin','businessId'=>2,'fullName'=>'Scoped Admin','mail'=>'business-admin@example.test','password'=>'unused']);
        $this->actingAs($actor,'admin');$this->assertFalse(app(\App\Services\PurchaseWorkflow::class)->platform());
        $this->getJson(route('purchase.invoice.show',$invoice->id))->assertNotFound();
        $this->deleteJson(route('purchase.invoice.destroy',$invoice->id))->assertNotFound();
        $customer=Customer::forceCreate(['name'=>'Owned by first business','businessId'=>1]);
        $this->postJson(route('saveCustomer'),['profileId'=>$customer->id,'fullName'=>'Hijack'])->assertNotFound();
        $this->assertEquals(1,$customer->fresh()->businessId);
    }

    public function test_row_keyed_vat_discount_and_profit_do_not_alter_payment_history(): void
    {
        $data=$this->setupBusiness();$payload=$this->payload($data);
        $payload['vatStatus']=[2=>1];$payload['vatPercent']=[2=>10];
        $payload['discountStatus']='2';$payload['discountPercent']=10;$payload['grandTotal']=72;
        $this->post(route('savePurchase'),$payload)->assertSessionHasNoErrors();
        $line=PurchaseProduct::where('productName',$data[1]->id)->firstOrFail();$invoice=$line->invoiceHeader;
        $this->assertEquals(16.50,$line->salePriceInVat);$this->assertEquals(50,$line->profit);
        $this->assertEquals(72,$invoice->grand_total);$this->assertEquals(30,$invoice->paid_amount);
        $this->assertEquals(42,$invoice->due_amount);$this->assertEquals(72,PurchaseProduct::sum('grandTotal'));
        $this->assertEquals(8,PurchaseProduct::sum('disAmount'));
    }

    public function test_modal_array_serials_quantity_matrix_and_reload(): void
    {
        foreach([[1,1],[5,0],[5,3],[5,5]] as [$qty,$count]) {
            $data=$this->setupBusinessIfMissing();
            $serials=array_map(fn($n)=>'MATRIX-'.$qty.'-'.$count.'-'.$n,range(1,max(1,$count)));
            if(!$count){$serials=[];}
            $invoice=app(\App\Services\PurchaseWorkflow::class)->save(['businessId'=>1,'supplierName'=>$data[0]->id,'purchaseDate'=>'2026-10-12','rows'=>['stable'=>['product_id'=>$data[1]->id,'qty'=>$qty,'buy_price'=>10]]]);
            $line=$invoice->items->first();
            $this->post(route('purchase.invoice.update',$invoice->id),['purchaseDate'=>'2026-10-12','rows'=>['stable'=>['id'=>$line->id,'product_id'=>$line->productName,'qty'=>$qty,'buy_price'=>10,'serials'=>$serials]]])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($count,ProductSerial::where('purchaseId',$line->id)->count());
            $this->assertEquals($qty,$line->stock->fresh()->currentStock);
            $this->get(route('purchase.invoice.edit',$invoice->id))->assertOk()->assertSee('purchaseInvoiceEditTable')->assertSee('serialModal')->assertDontSee('new_serials_csv');
            $page=$this->get(route('purchase.invoice.show',$invoice->id))->assertOk();foreach($serials as $value){$page->assertSee($value);}
            $this->postJson(route('purchase.invoice.update',$invoice->id),['purchaseDate'=>'2026-10-12','rows'=>['stable'=>['id'=>$line->id,'product_id'=>$line->productName,'qty'=>$qty,'buy_price'=>10,'serials'=>array_map(fn($n)=>'EXCESS-'.$invoice->id.'-'.$n,range(1,$qty+1))]]])->assertUnprocessable();
            $this->assertSame($count,ProductSerial::where('purchaseId',$line->id)->count());
            $this->assertEquals($qty,$line->stock->fresh()->currentStock);
        }
    }

    private function setupBusinessIfMissing(): array
    {
        if(!BusinessSetup::exists()){return $this->setupBusiness();}
        return [Supplier::first(),Product::first(),Product::orderBy('id')->skip(1)->first()];
    }

    public function test_purchase_list_toolbar_links_labels_and_read_only_legacy_preserve_data(): void
    {
        $invoice=$this->createInvoice([2=>['LIST-KEEP']]);
        $legacy=$invoice->items->first()->replicate();$legacy->purchase_invoice_id=null;$legacy->invoice='LIST-LEGACY';$legacy->save();
        $before=[$invoice->fresh()->toArray(),PurchaseProduct::orderBy('id')->get()->toArray(),ProductStock::orderBy('id')->get()->toArray(),ProductSerial::orderBy('id')->get()->toArray()];
        foreach(['en','bn'] as $locale){
            auth('admin')->user()->update(['preferred_locale'=>$locale]);
            $page=$this->get(route('purchaseList',['q'=>$invoice->invoice,'supplier'=>$invoice->supplier_id,'from'=>'2026-10-01','to'=>'2026-10-31','status'=>'partial','sort'=>'grand_total','direction'=>'asc']))->assertOk();
            $dom=new \DOMDocument;@$dom->loadHTML('<?xml encoding="UTF-8">'.$page->getContent());$xpath=new \DOMXPath($dom);
            $this->assertCount(1,$xpath->query('//*[@data-purchase-invoice]'));
            $this->assertSame((string)$invoice->id,$xpath->query('//*[@id="purchaseInvoiceTable"]//input[@name="ids[]"]')->item(0)->getAttribute('value'));
            $this->assertCount(1,$xpath->query('//*[@id="purchaseInvoiceBulkForm"]//*[@id="purchaseBulkDeleteButton" and @data-confirm="delete" and @disabled]'));
            $this->assertCount(1,$xpath->query('//*[@id="purchaseInvoiceBulkForm"]//*[@id="purchaseListPrintButton" and @type="button"]'));
            $this->assertCount(1,$xpath->query('//header[contains(@class,"rn-purchase-header")]//a[@href="'.route('addPurchase').'"]'));
            foreach(['show','edit'] as $action){$this->assertCount($action==='show'?2:1,$xpath->query('//*[@id="purchaseInvoiceTable"]//a[@href="'.route('purchase.invoice.'.$action,$invoice->id).'"]'));}
            $this->assertCount(1,$xpath->query('//details[@id="purchaseLegacyRecords" and not(@open)]'));
            $this->assertCount(0,$xpath->query('//*[@id="purchaseLegacyRecords"]//input[@name="ids[]"]'));
            $this->assertCount(1,$xpath->query('//time[@datetime="2026-10-12"]'));
            foreach(['list_summary','all_payment_statuses','sort_created','select_page','scroll_hint','legacy_compatibility'] as $key){$this->assertTrue(app('translator')->hasForLocale('purchases.'.$key,$locale));}
            $page->assertDontSee('purchases.list_summary')->assertDontSee('purchases.scroll_hint');
        }
        $page=$this->get(route('purchaseList',['legacy_page'=>1]))->assertOk();$dom=new \DOMDocument;@$dom->loadHTML($page->getContent());$this->assertCount(1,(new \DOMXPath($dom))->query('//details[@id="purchaseLegacyRecords" and @open]'));
        $this->assertSame($before,[$invoice->fresh()->toArray(),PurchaseProduct::orderBy('id')->get()->toArray(),ProductStock::orderBy('id')->get()->toArray(),ProductSerial::orderBy('id')->get()->toArray()]);
    }
    public function test_invoice_presentation_uses_explicit_navigation_and_preserves_edit_contract_in_both_languages(): void
    {
        $invoice=$this->createInvoice([2=>['VIEW-A1','VIEW-A2'],7=>['VIEW-B1']]);
        foreach (['en','bn'] as $locale) {
            auth('admin')->user()->forceFill(['preferred_locale'=>$locale])->save();
            $desktopAgent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/129.0 Safari/537.36';
            $view=$this->withHeader('User-Agent',$desktopAgent)->get(route('purchase.invoice.show',$invoice->id))->assertOk();
            $view->assertSee(__('purchases.invoice_overview'))->assertSee(__('purchases.financial_summary'))
                ->assertSee('VIEW-A1')->assertSee('VIEW-B1')->assertSee('data-invoice-serials',false)
                ->assertSee(route('purchaseList'),false)->assertSee('purchaseInvoicePrintButton',false);
            $edit=$this->withHeader('User-Agent',$desktopAgent)->get(route('purchase.invoice.edit',$invoice->id))->assertOk();
            $edit->assertSee('data-invoice-back',false)->assertSee('data-invoice-cancel',false)
                ->assertSee('id="purchaseInvoiceSaveButton" type="submit"',false)
                ->assertSee('id="purchaseInvoiceEditTable"',false)->assertSee('id="serialModal"',false)
                ->assertSee('name="rows[line'.$invoice->items->first()->id.'][qty]"',false)
                ->assertDontSee('textarea name="serials"',false);
            $mobileEdit=$this->withHeader('User-Agent','Mozilla/5.0 (Linux; Android 13) Mobile')
                ->get(route('purchase.invoice.edit',$invoice->id))->assertOk();
            $dom=new \DOMDocument();$previous=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="UTF-8">'.$mobileEdit->getContent());libxml_clear_errors();libxml_use_internal_errors($previous);$xpath=new \DOMXPath($dom);
            $this->assertSame(1,$xpath->query('//*[@id="purchaseInvoiceEdit" and contains(@class,"rn-m-form")]')->length);
            $this->assertSame(1,$xpath->query('//*[@class and contains(@class,"rn-invoice-footer") and contains(@class,"rn-m-sticky-actions")]')->length);
            $this->assertSame(10,$xpath->query('//*[@id="purchaseInvoiceNewRow"]//td[@data-label]')->length,'Dynamic invoice rows must carry all mobile field labels, including warranty days.');
            $this->assertStringNotContainsString('history.back', $edit->getContent());
        }
        $this->assertDatabaseCount('product_serials',3);
    }

    public function test_invoice_invalid_edit_shows_errors_and_preserves_saved_financials_and_serials(): void
    {
        $invoice=$this->createInvoice([2=>['VALIDATION-A']]);
        $payload=$this->editPayload($invoice);
        $key=array_key_first($payload['rows']); $payload['rows'][$key]['qty']=0;
        $this->from(route('purchase.invoice.edit',$invoice->id))->post(route('purchase.invoice.update',$invoice->id),$payload)
            ->assertRedirect(route('purchase.invoice.edit',$invoice->id))->assertSessionHasErrors('rows.'.$key.'.qty');
        $this->get(route('purchase.invoice.edit',$invoice->id))->assertOk()->assertSee('alert alert-danger',false);
        $this->assertEquals(80,$invoice->fresh()->grand_total);
        $this->assertDatabaseHas('product_serials',['serialNumber'=>'VALIDATION-A']);
        $this->assertEquals(5,ProductStock::sum('currentStock'));
    }
}
