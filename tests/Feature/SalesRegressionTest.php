<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductStock;
use App\Models\PurchaseProduct;
use App\Models\SaleProduct;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesRegressionTest extends TestCase
{
    use \Tests\Concerns\TestsSalesSerialPolicy;
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    // Never migrate or seed a configured development/production database.
    public function refreshDatabase()
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Sales regression tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    private function actor(string $role = 'gm', int $businessId = 1): AdminUser
    {
        return AdminUser::create([
            'fullName' => 'Regression Operator', 'sureName' => 'Test', 'storeName' => 'Test',
            'mail' => uniqid('operator'). '@example.test', 'contactNumber' => '0000000000',
            'password' => 'unused-test-password', 'role' => $role, 'businessId' => $businessId,
        ]);
    }

    public function test_sales_settings_sidebar_and_persisted_toggle_in_both_locales(): void
    {
        $this->inventory();
        $admin = $this->actor('admin');
        $this->actingAs($admin, 'admin');
        foreach (['en', 'bn'] as $locale) {
            $admin->update(['preferred_locale' => $locale]);
            $page = $this->get(route('sales.settings.edit', ['businessId' => 1]))->assertOk();
            $page->assertSee('lang="'.$locale.'"', false);
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$page->getContent());
            $xpath = new \DOMXPath($document);
            $links = $xpath->query('//*[@id="settings"]//a[@href="'.route('sales.settings.edit').'"]');
            $this->assertCount(1, $links);
            $this->assertCount(1, $xpath->query('//*[@id="settings"]//a[@href="'.route('language.settings.edit').'"]'));
            $this->assertCount(1, $xpath->query('//*[@id="settings"]//a[@href="'.route('addBusinessSetupPage').'"]'));
            $this->assertSame(__('settings.sales_settings'), trim($links->item(0)->textContent));
            $this->assertStringContainsString('show', $xpath->query('//*[@id="settings"]')->item(0)->getAttribute('class'));
            foreach ([false, true] as $required) {
                $this->post(route('sales.settings.update'), ['businessId' => 1, 'sales_serial_selection_required' => (int) $required])
                    ->assertSessionHasNoErrors()->assertRedirect(route('sales.settings.edit', ['businessId' => 1]));
                $this->assertSame($required, \App\Models\BusinessSetup::findOrFail(1)->sales_serial_selection_required);
                $response = $this->get(route('sales.settings.edit', ['businessId' => 1]))->assertOk();
                @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
                $checkbox = (new \DOMXPath($document))->query('//*[@id="salesSerialRequired"]')->item(0);
                $this->assertSame($required, $checkbox->hasAttribute('checked'));
            }
        }
    }

    private function inventory(int $businessId = 1, bool $serialized = true): array
    {
        if (!\App\Models\BusinessSetup::find($businessId)) {
            \App\Models\BusinessSetup::forceCreate(['id' => $businessId, 'businessName' => 'Test Business '.$businessId]);
        }
        $product = Product::create(['name' => 'Regression Product', 'businessId' => $businessId]);
        $supplier = Supplier::create(['name' => 'Regression Supplier', 'businessId' => $businessId]);
        $purchase = PurchaseProduct::create([
            'productName' => $product->id, 'supplier' => $supplier->id, 'businessId' => $businessId,
            'purchase_date' => '2026-10-11', 'invoice' => uniqid('PUR-'), 'qty' => 3,
            'buyPrice' => 100, 'salePriceExVat' => 150, 'totalAmount' => 300,
            'grandTotal' => 300, 'paidAmount' => 300, 'dueAmount' => 0,
        ]);
        $stock = ProductStock::create([
            'productId' => $product->id, 'purchaseId' => $purchase->id,
            'businessId' => $businessId, 'currentStock' => 3,
        ]);
        $serials = [];
        if ($serialized) {
            for ($i = 0; $i < 3; $i++) {
                $serials[] = ProductSerial::create([
                    'productId' => $product->id, 'purchaseId' => $purchase->id,
                    'serialNumber' => 'SER-'.$purchase->id.'-'.$i, 'status' => 'available',
                ]);
            }
        }
        return compact('purchase', 'stock', 'serials');
    }

    private function payload(array $inventory, int $qty = 2): array
    {
        $customer = Customer::create(['name' => 'Regression Customer', 'businessId' => 1]);
        return [
            'date' => '2026-10-11', 'customerId' => $customer->id,
            'purchaseData' => [$inventory['purchase']->id], 'qty' => [$qty],
            'salePrice' => [150], 'buyPrice' => [100], 'warranty_days' => ['365'],
            'serialId' => [array_map(fn ($s) => $s->id, array_slice($inventory['serials'], 0, $qty))],
            'totalSaleAmount' => 150 * $qty, 'discountAmount' => 10,
            'additionalChargeName' => 'Delivery', 'additionalChargeAmount' => 5,
            'grandTotal' => 150 * $qty - 5, 'paidAmount' => 100,
            'dueAmount' => 150 * $qty - 105, 'prevDue' => 0, 'curDue' => 150 * $qty - 105,
        ];
    }

    private function login(): void
    {
        $this->actingAs($this->actor(), 'admin');
    }

    public function test_existing_owner_and_spaced_superadmin_can_save_optional_sales_with_both_actions(): void
    {
        $inventory = $this->inventory();
        $this->optional();
        $owner = $this->actor();
        $owner->update(['role' => null, 'businessId' => null]);
        $spaced = $this->actor('super admin', 0);
        foreach ([$owner, $spaced] as $index => $actor) {
            $this->actingAs($actor, 'admin');
            $this->getJson(route('ajax.purchase.serials.public', $inventory['purchase']->id))
                ->assertOk()->assertJsonPath('selection_required', false);
            $payload = $this->payload($inventory, 1);
            unset($payload['serialId']);
            $payload['businessId'] = 1;
            $payload['saleAction'] = $index === 0 ? 'save' : 'save_print';
            $response = $this->post(route('saveSale'), $payload)->assertSessionHasNoErrors();
            $sale = SaleProduct::orderByDesc('id')->firstOrFail();
            $response->assertRedirect(route('invoiceGenerate', ['id' => $sale->id, 'autoprint' => $index, 'printer' => 'thermal80']));
            $this->assertSame(1, ProductSerial::where('saleId', $sale->id)->count());
            $this->assertSame(1, (int) $sale->businessId);
            $this->get(route('invoiceGenerate', $sale->id))->assertOk();
            $this->get(route('sale.items.edit', $sale->id))->assertOk();
        }
        $this->assertSame(1, (int) $inventory['stock']->fresh()->currentStock);
    }

    public function test_unassigned_nonowner_cannot_lookup_or_submit_optional_inventory(): void
    {
        $inventory = $this->inventory();
        $this->optional();
        $this->actor('superadmin');
        $actor = $this->actor();
        $actor->update(['role' => null, 'businessId' => null]);
        $this->actingAs($actor, 'admin');
        $this->getJson(route('ajax.purchase.serials.public', $inventory['purchase']->id))->assertNotFound();
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        $this->postJson(route('saveSale'), $payload)->assertUnprocessable();
        $this->assertRolledBack($inventory);
    }

    public function test_unassigned_legacy_owner_still_requires_selection_when_mandatory(): void
    {
        $inventory = $this->inventory();
        $owner = $this->actor();
        $owner->update(['role' => null, 'businessId' => null]);
        $this->actingAs($owner, 'admin');
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        $this->postJson(route('saveSale'), $payload)->assertUnprocessable()->assertJsonValidationErrors('serialId.0');
        $this->assertRolledBack($inventory);
        $this->post(route('saveSale'), $this->payload($inventory))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2, ProductSerial::whereNotNull('saleId')->count());
    }

    private function assertRolledBack(array $inventory): void
    {
        $this->assertDatabaseCount('sale_products', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertDatabaseCount('invoice_sequences', 0);
        $this->assertSame(3, (int) $inventory['stock']->fresh()->currentStock);
        foreach ($inventory['serials'] as $serial) {
            $this->assertNull($serial->fresh()->saleId);
            $this->assertSame('available', $serial->fresh()->status);
        }
    }

    public function test_valid_serial_sale_deducts_stock_and_preserves_payment_totals(): void
    {
        $inventory = $this->inventory();
        $this->login();
        $this->post(route('saveSale'), $this->payload($inventory))->assertRedirect();
        $sale = SaleProduct::firstOrFail();
        $this->assertDatabaseCount('invoice_items', 1);
        $item = InvoiceItem::firstOrFail();
        $this->assertEquals(2, $item->qty);
        $this->assertEquals(300, $item->totalSale);
        $this->assertEquals(200, $item->totalPurchase);
        $this->assertEquals(100, $item->profitTotal);
        $this->assertEquals(295, $sale->grandTotal);
        $this->assertEquals(195, $sale->invoiceDue);
        $this->assertEquals((float) $sale->grandTotal - (float) $sale->paidAmount, $sale->invoiceDue);
        $this->assertSame(1, (int) $inventory['stock']->fresh()->currentStock);
        foreach (array_slice($inventory['serials'], 0, 2) as $serial) {
            $this->assertEquals($sale->id, $serial->fresh()->saleId);
            $this->assertSame('sold', $serial->fresh()->status);
            $this->assertNotNull($serial->fresh()->sold_at);
        }
        $this->assertNull($inventory['serials'][2]->fresh()->saleId);
    }

    public function test_optional_missing_serial_selection_preserves_auto_allocation(): void
    {
        $inventory = $this->inventory();
        $this->login();
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        \App\Models\BusinessSetup::findOrFail(1)->update(['sales_serial_selection_required' => false]);
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertDatabaseCount('sale_products', 1);
        $this->assertSame(2, ProductSerial::where('status', 'sold')->count());
        $this->assertSame(1, (int) $inventory['stock']->fresh()->currentStock);
    }

    public function test_mismatched_serial_count_rolls_back_the_entire_sale(): void
    {
        $inventory = $this->inventory();
        $this->login();
        $payload = $this->payload($inventory);
        $payload['serialId'] = [[$inventory['serials'][0]->id]];
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertRolledBack($inventory);
    }

    public function test_duplicate_or_wrong_purchase_serials_roll_back(): void
    {
        $inventory = $this->inventory();
        $other = $this->inventory();
        $this->login();
        $payload = $this->payload($inventory);
        foreach ([[$inventory['serials'][0]->id, $inventory['serials'][0]->id],
            [$other['serials'][0]->id, $other['serials'][1]->id]] as $ids) {
            $payload['serialId'] = [$ids];
            $this->post(route('saveSale'), $payload)->assertRedirect();
            $this->assertRolledBack($inventory);
        }
    }

    public function test_insufficient_stock_rolls_back_serial_assignment(): void
    {
        $inventory = $this->inventory();
        $inventory['stock']->update(['currentStock' => 1]);
        $this->login();
        $this->post(route('saveSale'), $this->payload($inventory))->assertRedirect();
        $this->assertDatabaseCount('sale_products', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertDatabaseCount('invoice_sequences', 0);
        $this->assertSame(1, (int) $inventory['stock']->fresh()->currentStock);
        foreach ($inventory['serials'] as $serial) {
            $this->assertNull($serial->fresh()->saleId);
            $this->assertSame('available', $serial->fresh()->status);
        }
    }

    public function test_already_sold_serial_cannot_be_reassigned(): void
    {
        $inventory = $this->inventory();
        $inventory['serials'][0]->update(['saleId' => 123, 'status' => 'sold']);
        $this->login();
        $this->post(route('saveSale'), $this->payload($inventory))->assertRedirect();
        $this->assertDatabaseCount('sale_products', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertSame(3, (int) $inventory['stock']->fresh()->currentStock);
        $this->assertEquals(123, $inventory['serials'][0]->fresh()->saleId);
        $this->assertNull($inventory['serials'][1]->fresh()->saleId);
    }

    public function test_missing_selection_fails_when_serial_count_is_insufficient(): void
    {
        $inventory = $this->inventory();
        $this->login();
        $payload = $this->payload($inventory, 4);
        unset($payload['serialId']);
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertRolledBack($inventory);
    }

    public function test_negative_payment_is_rejected_before_any_writes(): void
    {
        $inventory = $this->inventory();
        $this->login();
        $payload = $this->payload($inventory);
        $payload['paidAmount'] = -1;
        $this->postJson(route('saveSale'), $payload)->assertUnprocessable()->assertJsonValidationErrors('paidAmount');
        $this->assertRolledBack($inventory);
    }

    public function test_later_row_failure_rolls_back_earlier_stock_and_serial_changes(): void
    {
        $first = $this->inventory();
        $second = $this->inventory();
        $this->login();
        $payload = $this->payload($first);
        $payload['purchaseData'][] = $second['purchase']->id;
        $payload['qty'][] = 2;
        $payload['salePrice'][] = 150;
        $payload['buyPrice'][] = 100;
        $payload['serialId'][] = [$second['serials'][0]->id];
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertRolledBack($first);
        $this->assertRolledBack($second);
    }

    public function test_zero_and_fractional_quantities_fail_request_validation(): void
    {
        $inventory = $this->inventory();
        $this->login();
        foreach ([0, 1.5] as $qty) {
            $payload = $this->payload($inventory);
            $payload['qty'] = [$qty];
            $this->postJson(route('saveSale'), $payload)->assertUnprocessable()->assertJsonValidationErrors('qty.0');
            $this->assertRolledBack($inventory);
        }
    }

    public function test_nonserialized_sale_needs_no_serial_selection(): void
    {
        $inventory = $this->inventory(1, false);
        $this->login();
        $payload = $this->payload($inventory);
        unset($payload['serialId']);
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertDatabaseCount('sale_products', 1);
        $this->assertDatabaseCount('product_serials', 0);
        $this->assertSame(1, (int) $inventory['stock']->fresh()->currentStock);
    }

    public function test_header_totals_remain_client_supplied_characterization(): void
    {
        // Known risk, deliberately recorded rather than changing accounting policy here.
        $inventory = $this->inventory();
        $this->login();
        $payload = $this->payload($inventory);
        $payload['grandTotal'] = 999;
        $payload['dueAmount'] = 7;
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $this->assertEquals(999, SaleProduct::firstOrFail()->grandTotal);
        $this->assertEquals(7, SaleProduct::firstOrFail()->invoiceDue);
        $this->assertEquals(300, InvoiceItem::firstOrFail()->totalSale);
    }

    public function test_serial_endpoint_rejects_guests_and_other_businesses(): void
    {
        $own = $this->inventory();
        $other = $this->inventory(2);
        $this->getJson(route('ajax.purchase.serials.public', $own['purchase']->id))->assertUnauthorized();
        foreach (['gm', 'storemanager', 'salesmanager'] as $role) {
            $this->actingAs($this->actor($role), 'admin');
            $this->getJson(route('ajax.purchase.serials.public', $own['purchase']->id))
                ->assertOk()->assertJsonCount(3, 'serials');
            $this->getJson(route('ajax.purchase.serials.public', $other['purchase']->id))
                ->assertNotFound()->assertExactJson(['status' => 'error', 'serials' => []]);
        }
    }

    public function test_serial_endpoint_preserves_platform_admin_access_and_filters_sold_serials(): void
    {
        $inventory = $this->inventory(2);
        $inventory['serials'][0]->update(['saleId' => 123, 'status' => 'sold']);
        foreach (['admin', 'superadmin'] as $role) {
            $this->actingAs($this->actor($role), 'admin');
            $this->getJson(route('ajax.purchase.serials.public', $inventory['purchase']->id))
                ->assertOk()->assertJsonCount(2, 'serials')
                ->assertJsonMissing(['id' => $inventory['serials'][0]->id]);
        }
    }

    public function test_gm_cannot_deduct_another_business_stock_through_sale_creation(): void
    {
        $inventory = $this->inventory(2);
        $this->login();
        $this->post(route('saveSale'), $this->payload($inventory))->assertRedirect();
        $this->assertDatabaseCount('sale_products', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        // Query by primary key without business scope to inspect the protected inventory.
        $this->assertEquals(3, ProductStock::withoutGlobalScopes()->findOrFail($inventory['stock']->id)->currentStock);
        foreach ($inventory['serials'] as $serial) {
            $this->assertNull($serial->fresh()->saleId);
        }
    }
    public function test_new_sale_save_intents_only_change_autoprint_and_keep_legacy_default(): void
    {
        $inventory=$this->inventory();$this->login();
        foreach (['save'=>0,'save_print'=>1,'legacy'=>1] as $index=>$autoprint) {
            $payload=$this->payload($inventory,1);
            $serial=$inventory['serials'][SaleProduct::count()];$payload['serialId']=[[$serial->id]];
            if ($index!=='legacy') {$payload['saleAction']=$index;}
            $this->post(route('saveSale'),$payload)->assertSessionHasNoErrors()
                ->assertRedirect(route('invoiceGenerate',['id'=>SaleProduct::latest('id')->first()->id,'autoprint'=>$autoprint,'printer'=>'thermal80']));
            $sale=SaleProduct::latest('id')->first();
            $this->assertEquals(145,$sale->grandTotal);$this->assertEquals(100,$sale->paidAmount);
            $this->assertEquals(45,$sale->invoiceDue);$this->assertEquals($sale->id,$serial->fresh()->saleId);
        }
        $this->assertEquals(0,$inventory['stock']->fresh()->currentStock);
        $this->assertDatabaseCount('invoice_items',3);
    }

    public function test_invalid_new_sale_action_is_rejected_before_stock_or_serial_changes(): void
    {
        $inventory=$this->inventory();$this->login();$payload=$this->payload($inventory);
        $payload['saleAction']='unsupported';$this->postJson(route('saveSale'),$payload)->assertUnprocessable()->assertJsonValidationErrors('saleAction');
        $this->assertRolledBack($inventory);
    }

    public function test_new_sale_has_one_authoritative_input_and_explicit_actions_in_both_languages(): void
    {
        $this->inventory();$this->login();
        foreach (['en','bn'] as $locale) {
            auth('admin')->user()->update(['preferred_locale'=>$locale]);
            $page=$this->get(route('newsale'))->assertOk()
                ->assertSee(\Illuminate\Support\Facades\Lang::get('sales.billing_summary', [], $locale))
                ->assertSee(\Illuminate\Support\Facades\Lang::get('sales.add_products', [], $locale))
                ->assertSee(\Illuminate\Support\Facades\Lang::get('sales.product_search', [], $locale));
            $doc=new \DOMDocument;@$doc->loadHTML('<?xml encoding="UTF-8">'.$page->getContent());$xpath=new \DOMXPath($doc);
            foreach (['customerId','date','invoice','note','saleAction','totalSaleAmount','discountAmount','additionalChargeName','additionalChargeAmount','grandTotal','paidAmount','dueAmount','prevDue','curDue','printerProfile'] as $name) {
                $this->assertCount(1,$xpath->query('//*[@id="saveSaleForm"]//*[@name="'.$name.'"]'),$locale.' '.$name);
            }
            $this->assertCount(2,$xpath->query('//*[@id="saveSaleForm"]//button[@type="submit" and @disabled]'));
            $this->assertCount(1,$xpath->query('//a[@data-sale-back and @href="'.route('saleList').'"]'));
            $this->assertCount(1,$xpath->query('//*[@id="productDetails"]'));
            $this->assertCount(1,$xpath->query('//*[@id="saleProductSearch"]'),$locale.' product search input');
            $searchLabels=$xpath->query('//label[@for="saleProductSearch"]');
            $this->assertCount(1,$searchLabels,$locale.' product search label');
            $this->assertSame(\Illuminate\Support\Facades\Lang::get('sales.product_search', [], $locale),trim($searchLabels->item(0)->textContent),$locale.' product search label text');
            $this->assertCount(1,$xpath->query('//*[@id="categorySelect"]'),$locale.' product category selector');
            $this->assertCount(1,$xpath->query('//*[@id="productName"]'),$locale.' product selector');
            $this->assertCount(1,$xpath->query('//*[@id="saleSerialModal"]'));
            $this->assertCount(1,$xpath->query('//*[@id="customerModal"]'));
            $this->assertCount(1,$xpath->query('//*[@id="barcodeInput" and not(@name)]'));
        }
    }

    public function test_new_sale_field_errors_render_without_creating_a_transaction(): void
    {
        $inventory=$this->inventory();$this->login();$payload=$this->payload($inventory);$payload['customerId']='';
        $this->from(route('newsale'))->post(route('saveSale'),$payload)->assertRedirect(route('newsale'))->assertSessionHasErrors('customerId');
        $this->get(route('newsale'))->assertOk()->assertSee('invalid-feedback',false);
        $this->assertRolledBack($inventory);
    }

    public function test_customer_first_form_starts_unselected_with_all_product_controls_disabled(): void
    {
        $this->inventory(); $this->login();
        $page = $this->get(route('newsale'))->assertOk();
        $doc = new \DOMDocument; @$doc->loadHTML($page->getContent()); $xpath = new \DOMXPath($doc);
        $this->assertCount(0, $xpath->query('//*[@id="customerName"]/option[@selected and @value!=""]'));
        foreach (['saleProductSearch','barcodeInput','searchBarcodeBtn','categorySelect','productName','outOfStockBtn'] as $id) {
            $this->assertTrue($xpath->query('//*[@id="'.$id.'"]')->item(0)->hasAttribute('disabled'), $id);
        }
    }

    public function test_sale_rejects_a_customer_from_a_different_business_before_inventory_changes(): void
    {
        $inventory = $this->inventory(); $this->login();
        $customer = Customer::forceCreate(['name'=>'Foreign Customer','businessId'=>2]);
        $payload = $this->payload($inventory); $payload['customerId'] = $customer->id;
        $this->postJson(route('saveSale'), $payload)->assertUnprocessable()->assertJsonValidationErrors('customerId');
        $this->assertRolledBack($inventory);
    }

    public function test_customer_lookups_reject_guests_and_foreign_business_customers(): void
    {
        $this->inventory();
        $own=Customer::forceCreate(['name'=>'Own','businessId'=>1]);
        $foreign=Customer::forceCreate(['name'=>'Foreign','businessId'=>2]);
        foreach (['ajax.customer.products.public','ajax.customer.previousDue.public'] as $route) {
            $this->getJson(route($route,$own->id))->assertUnauthorized();
        }
        $this->login();
        foreach (['ajax.customer.products.public','ajax.customer.previousDue.public'] as $route) {
            $this->getJson(route($route,$own->id))->assertOk();
            $this->getJson(route($route,$foreign->id))->assertNotFound();
        }
    }

    public function test_walk_in_customer_lookups_use_the_resolved_business_and_reject_invalid_targets(): void
    {
        $inventory = $this->inventory();
        $walkIn = Customer::forceCreate(['name' => 'Walking Customer', 'businessId' => null]);
        SaleProduct::forceCreate([
            'invoice' => 'WALKIN-DUE-TEST', 'date' => '2026-10-15', 'customerId' => $walkIn->id,
            'businessId' => 1, 'grandTotal' => 50, 'paidAmount' => 37.5, 'invoiceDue' => 12.5,
            'prevDue' => 0, 'curDue' => 12.5, 'status' => 'Ordered',
        ]);
        $this->login();
        $this->assertNull($walkIn->fresh()->businessId, 'Fixture must represent the legacy shared Walk-in record.');

        $products = $this->getJson(route('ajax.customer.products.public', [
            'id' => $walkIn->id, 'businessId' => 1,
        ]))->assertOk();
        $products->assertSee('purchase_'.$inventory['purchase']->id);
        $this->getJson(route('ajax.customer.previousDue.public', [
            'id' => $walkIn->id, 'businessId' => 1,
        ]))->assertOk()->assertJsonPath('prevDue', '12.50');

        foreach (['ajax.customer.products.public', 'ajax.customer.previousDue.public'] as $route) {
            $this->getJson(route($route, ['id' => 999999, 'businessId' => 1]))->assertNotFound();
            $this->getJson(route($route, ['id' => $walkIn->id, 'businessId' => 2]))->assertForbidden();
        }
    }

    public function test_platform_customer_lookups_require_an_explicit_authorized_business(): void
    {
        $inventory = $this->inventory();
        \App\Models\BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Other Business']);
        $walkIn = Customer::forceCreate(['name' => 'Walking Customer', 'businessId' => null]);
        $this->actingAs($this->actor('superadmin'), 'admin');

        $this->getJson(route('ajax.customer.products.public', [
            'id' => $walkIn->id, 'businessId' => 1,
        ]))->assertOk()->assertSee('purchase_'.$inventory['purchase']->id);
        $this->getJson(route('ajax.customer.products.public', $walkIn->id))
            ->assertUnprocessable()->assertJsonValidationErrors('businessId');
    }

    public function test_platform_customer_creation_requires_explicit_business_and_saves_to_that_business(): void
    {
        $this->inventory();
        \App\Models\BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Other Business']);
        $this->actingAs($this->actor('superadmin'), 'admin');

        $salePage = $this->get(route('newsale'))->assertOk();
        $salePage->assertSee("if(f.id === 'customerForm')", false)
            ->assertSee('#saveSaleForm [name="businessId"]', false)
            ->assertSee('id="businessId" name="businessId"', false);

        $this->postJson(route('createCustomer'), [
            'fullName' => 'Explicit Business Customer', 'businessId' => 2,
        ])->assertOk();
        $this->assertDatabaseHas('customers', [
            'name' => 'Explicit Business Customer', 'businessId' => 2,
        ]);
        $this->postJson(route('createCustomer'), ['fullName' => 'Missing Business Customer'])
            ->assertUnprocessable()->assertJsonValidationErrors('businessId');
        $this->assertDatabaseMissing('customers', ['name' => 'Missing Business Customer']);
    }

    public function test_missing_invalid_and_deleted_customer_ids_roll_back_and_walk_in_remains_valid(): void
    {
        $inventory=$this->inventory(); $this->optional(); $this->login();
        $payload=$this->payload($inventory); unset($payload['serialId']);
        $customer=Customer::findOrFail($payload['customerId']); $customer->delete();
        foreach (['',999999,'walk-in',$customer->id] as $id) {
            $payload['customerId']=$id;
            $this->postJson(route('saveSale'),$payload)->assertUnprocessable()->assertJsonValidationErrors('customerId');
            $this->assertRolledBack($inventory);
        }
        $walk=Customer::forceCreate(['name'=>'Walking Customer','businessId'=>null]);
        $payload['customerId']=$walk->id;
        $this->post(route('saveSale'),$payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($walk->id,(int)SaleProduct::firstOrFail()->customerId);
        $this->assertSame(1,Customer::withoutGlobalScopes()->where('name','Walking Customer')->count());
    }

    public function test_new_sale_isolated_browser_fixtures_include_real_sales_handlers_and_policy_responses(): void
    {
        $inventory=$this->inventory();$plain=$this->inventory(1,false);$this->login();
        $category=\App\Models\Category::forceCreate(['name'=>'Sprint03 QA Category']);
        foreach ([$inventory,$plain] as $i=>$stock) {
            Product::find($stock['purchase']->productName)->update(['name'=>$i===0 ? 'Sprint03 QA Serialized' : 'Sprint03 QA Plain','barCode'=>$i===0 ? 'SPRINT03-QA-SCAN' : 'SPRINT03-QA-PLAIN','category'=>$category->id]);
        }
        $customer=Customer::create(['name'=>'Sprint03 QA Customer','businessId'=>1]);
        SaleProduct::forceCreate(['invoice'=>'SPRINT03-PRIOR-DUE','date'=>'2026-10-12','customerId'=>$customer->id,'businessId'=>1,'salespersonId'=>auth('admin')->id(),'totalSale'=>50,'grandTotal'=>50,'paidAmount'=>15,'invoiceDue'=>35,'curDue'=>35,'prevDue'=>0,'status'=>'Ordered']);
        foreach (['en','bn'] as $locale) {
            auth('admin')->user()->update(['preferred_locale'=>$locale]);
            foreach ([true,false] as $required) {
                \App\Models\BusinessSetup::find(1)->update(['sales_serial_selection_required'=>$required]);
                $page=$this->get(route('newsale'))->assertOk()->assertSee('lang="'.$locale.'"',false);
                $responses=[];
                foreach (Customer::all() as $c) {
                    foreach (['ajax.customer.products.public','ajax.customer.previousDue.public'] as $route) {
                        $url=route($route,['id'=>$c->id]);$responses[parse_url($url,PHP_URL_PATH)]=$this->getJson($url)->assertOk()->json();
                    }
                }
                $url=route('ajax.categories.public');$responses[parse_url($url,PHP_URL_PATH)]=$this->getJson($url)->assertOk()->json();
                $url=route('ajax.category.products.public',['id'=>$category->id]);$responses[parse_url($url,PHP_URL_PATH)]=$this->getJson($url)->assertOk()->json();
                foreach ([$inventory,$plain] as $stock) {
                    foreach (['ajax.purchase.details.public','ajax.purchase.serials.public'] as $route) {
                        $url=route($route,['id'=>$stock['purchase']->id]);$responses[parse_url($url,PHP_URL_PATH)]=$this->getJson($url)->assertOk()->json();
                    }
                    $url=route('ajax.sale.product.purchaseDetails.public',['id'=>$stock['purchase']->productName]);$responses[parse_url($url,PHP_URL_PATH)]=$this->getJson($url)->assertOk()->json();
                }
                $url=route('product.findByBarcode');$responses[parse_url($url,PHP_URL_PATH)]=$this->postJson($url,['barcode'=>'SPRINT03-QA-SCAN'])->assertOk()->json();
                if(getenv('RN_EXPORT_SALE_FIXTURES')==='1') {
                    $stem=storage_path('logs/sprint03-sale-'.$locale.'-'.($required ? 'required' : 'optional'));
                    file_put_contents($stem.'.html',$page->getContent());
                    file_put_contents($stem.'.json',json_encode(['responses'=>$responses,'customerId'=>$customer->id,'purchaseId'=>$inventory['purchase']->id,'plainPurchaseId'=>$plain['purchase']->id,'categoryId'=>$category->id]));
                }
            }
        }
        $this->assertDatabaseCount('sale_products',1);
        $this->assertEquals(6,ProductStock::sum('currentStock'));
    }
}
