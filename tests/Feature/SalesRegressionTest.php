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

    private function assertHtmlOptionValue(string $html, int|string $value): void
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8"><select>'.$html.'</select>');
        $xpath = new \DOMXPath($document);
        $options = $xpath->query('//select/option[@value="'.(int)$value.'"]');
        $this->assertNotFalse($options);
        $this->assertCount(1, $options, 'Expected one option with value '.$value);
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

    public function test_explicit_and_spaced_superadmins_can_save_optional_sales_with_both_actions(): void
    {
        $inventory = $this->inventory();
        $this->optional();
        $owner = $this->actor();
        $owner->update(['role' => 'superadmin', 'businessId' => null]);
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

    public function test_unassigned_explicit_superadmin_still_requires_selection_when_mandatory(): void
    {
        $inventory = $this->inventory();
        $owner = $this->actor();
        $owner->update(['role' => 'superadmin', 'businessId' => null]);
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

    public function test_save_recalculates_header_totals_from_authoritative_sale_lines(): void
    {
        $inventory = $this->inventory();
        $this->login();
        $payload = $this->payload($inventory);
        $payload['grandTotal'] = 999;
        $payload['dueAmount'] = 7;
        $this->post(route('saveSale'), $payload)->assertRedirect();
        $sale = SaleProduct::firstOrFail();
        $this->assertEquals(295, $sale->grandTotal);
        $this->assertEquals(100, $sale->paidAmount);
        $this->assertEquals(195, $sale->invoiceDue);
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
        $this->actingAs($this->actor('superadmin'), 'admin');
        $this->getJson(route('ajax.purchase.serials.public', $inventory['purchase']->id))
            ->assertOk()->assertJsonCount(2, 'serials')
            ->assertJsonMissing(['id' => $inventory['serials'][0]->id]);
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
        // New rows are stamped with a business automatically; make it the legacy shared record again.
        Customer::withoutGlobalScopes()->whereKey($walkIn->id)->update(['businessId' => null]);
        SaleProduct::forceCreate([
            'invoice' => 'WALKIN-DUE-TEST', 'date' => '2026-10-15', 'customerId' => $walkIn->id,
            'businessId' => 1, 'grandTotal' => 50, 'paidAmount' => 37.5, 'invoiceDue' => 12.5,
            'prevDue' => 0, 'curDue' => 12.5, 'status' => 'Ordered',
        ]);
        $this->login();
        $this->assertNull($walkIn->fresh()->businessId, 'Fixture must represent the legacy shared Walk-in record.');

        $products = $this->getJson(route('ajax.customer.products.public', [
            'id' => $walkIn->id, 'businessId' => 1,
        ]))->assertOk()->assertJsonStructure(['data', 'outOfStock']);
        $this->assertHtmlOptionValue($products->json('data'), $inventory['purchase']->productName);
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
        // New rows are stamped with a business automatically; make it the legacy shared record again.
        Customer::withoutGlobalScopes()->whereKey($walkIn->id)->update(['businessId' => null]);
        $category=\App\Models\Category::forceCreate(['name'=>'Platform Category']);
        Product::findOrFail($inventory['purchase']->productName)->update(['category'=>(string)$category->id]);
        $this->actingAs($this->actor('superadmin'), 'admin');

        $products = $this->getJson(route('ajax.customer.products.public', [
            'id' => $walkIn->id, 'businessId' => 1,
        ]))->assertOk()->assertJsonStructure(['data', 'outOfStock']);
        $this->assertHtmlOptionValue($products->json('data'), $inventory['purchase']->productName);
        $this->getJson(route('ajax.customer.products.public', $walkIn->id))
            ->assertUnprocessable()->assertJsonValidationErrors('businessId');
        $this->getJson(route('ajax.category.products.public', $category->id))
            ->assertUnprocessable()->assertJsonValidationErrors('businessId');
        $categoryProducts = $this->getJson(route('ajax.category.products.public', ['id'=>$category->id,'businessId'=>1]))
            ->assertOk()->assertJsonStructure(['data', 'outOfStock']);
        $this->assertHtmlOptionValue($categoryProducts->json('data'), $inventory['purchase']->productName);
        $this->getJson(route('ajax.sale.product.purchaseDetails.public', ['id'=>$inventory['purchase']->productName]))
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
        $brandA=\App\Models\Brand::forceCreate(['name'=>'Sprint03 QA Brand A']);
        $brandB=\App\Models\Brand::forceCreate(['name'=>'Sprint03 QA Brand B']);
        $categoryA=\App\Models\Category::forceCreate(['name'=>'Sprint03 QA Category A']);
        $categoryB=\App\Models\Category::forceCreate(['name'=>'Sprint03 QA Category B']);
        foreach ([$inventory,$plain] as $i=>$stock) {
            Product::find($stock['purchase']->productName)->update([
                'name'=>$i===0 ? 'Sprint03 QA Serialized' : 'Sprint03 QA Plain',
                'barCode'=>$i===0 ? 'SPRINT03-QA-SCAN' : 'SPRINT03-QA-PLAIN',
                'brand'=>(string)($i===0 ? $brandA->id : $brandB->id),
                'category'=>(string)($i===0 ? $categoryA->id : $categoryB->id),
            ]);
        }
        // A second supplier and purchase lot for the same plain product exercises
        // product-level aggregation while retaining purchase-lot sale rows.
        $plainSupplier=Supplier::create(['name'=>'Sprint03 QA Second Supplier','businessId'=>1]);
        $plainExtra=PurchaseProduct::create([
            'productName'=>$plain['purchase']->productName,'supplier'=>$plainSupplier->id,'businessId'=>1,
            'purchase_date'=>'2026-10-12','invoice'=>uniqid('PUR-'),'qty'=>3,'buyPrice'=>110,
            'salePriceExVat'=>160,'totalAmount'=>330,'grandTotal'=>330,'paidAmount'=>330,'dueAmount'=>0,
        ]);
        ProductStock::create(['productId'=>$plain['purchase']->productName,'purchaseId'=>$plainExtra->id,'businessId'=>1,'currentStock'=>3]);
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
                $url=route('ajax.brands.public');$responses[parse_url($url,PHP_URL_PATH)]=$this->getJson($url)->assertOk()->json();
                $url=route('ajax.categories.public');$responses[parse_url($url,PHP_URL_PATH)]=$this->getJson($url)->assertOk()->json();
                $url=route('ajax.category.products.public',['id'=>$categoryA->id]);$responses[parse_url($url,PHP_URL_PATH)]=$this->getJson($url)->assertOk()->json();
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
                    file_put_contents($stem.'.json',json_encode(['responses'=>$responses,'customerId'=>$customer->id,'purchaseId'=>$inventory['purchase']->id,'productId'=>$inventory['purchase']->productName,'plainPurchaseId'=>$plain['purchase']->id,'plainProductId'=>$plain['purchase']->productName,'plainExtraPurchaseId'=>$plainExtra->id,'brandAId'=>$brandA->id,'brandBId'=>$brandB->id,'categoryAId'=>$categoryA->id,'categoryBId'=>$categoryB->id]));
                }
            }
        }
        $this->assertDatabaseCount('sale_products',1);
        $this->assertEquals(9,ProductStock::sum('currentStock'));
    }

    public function test_sale_product_and_category_options_are_unique_with_aggregated_business_stock(): void
    {
        $first=$this->inventory(1,false);
        $product=Product::findOrFail($first['purchase']->productName);
        $category=\App\Models\Category::forceCreate(['name'=>'Aggregate Category']);
        $product->update(['category'=>(string)$category->id]);
        $lotIds=[$first['purchase']->id];
        foreach ([2,4] as $index=>$stockQty) {
            $supplier=Supplier::create(['name'=>'Aggregate Supplier '.$index,'businessId'=>1]);
            $purchase=PurchaseProduct::create([
                'productName'=>$product->id,'supplier'=>$supplier->id,'businessId'=>1,'purchase_date'=>'2026-10-12',
                'invoice'=>uniqid('PUR-'),'qty'=>$stockQty,'buyPrice'=>100+$index,'salePriceExVat'=>150,
                'totalAmount'=>$stockQty*(100+$index),'grandTotal'=>$stockQty*(100+$index),'paidAmount'=>0,'dueAmount'=>0,
            ]);
            ProductStock::create(['productId'=>$product->id,'purchaseId'=>$purchase->id,'businessId'=>1,'currentStock'=>$stockQty]);
            $lotIds[]=$purchase->id;
        }
        $sameName=Product::create(['name'=>$product->name,'businessId'=>1,'category'=>(string)$category->id]);
        $sameSupplier=Supplier::create(['name'=>'Same-name Supplier','businessId'=>1]);
        $samePurchase=PurchaseProduct::create(['productName'=>$sameName->id,'supplier'=>$sameSupplier->id,'businessId'=>1,'qty'=>1,'buyPrice'=>50,'salePriceExVat'=>60]);
        ProductStock::create(['productId'=>$sameName->id,'purchaseId'=>$samePurchase->id,'businessId'=>1,'currentStock'=>1]);
        \App\Models\BusinessSetup::forceCreate(['id'=>2,'businessName'=>'Other Business']);
        $foreignProduct=Product::create(['name'=>$product->name,'businessId'=>2,'category'=>(string)$category->id]);
        $foreignSupplier=Supplier::create(['name'=>'Foreign Supplier','businessId'=>2]);
        $foreignPurchase=PurchaseProduct::create(['productName'=>$foreignProduct->id,'supplier'=>$foreignSupplier->id,'businessId'=>2,'qty'=>50,'buyPrice'=>1,'salePriceExVat'=>2]);
        ProductStock::create(['productId'=>$foreignProduct->id,'purchaseId'=>$foreignPurchase->id,'businessId'=>2,'currentStock'=>50]);
        $customer=Customer::create(['name'=>'Aggregate Customer','businessId'=>1]);
        $this->login();

        $all=$this->getJson(route('ajax.customer.products.public',$customer->id))->assertOk()->json('data');
        $this->assertSame(1,substr_count($all,'value="'.$product->id.'"'));
        $this->assertStringContainsString('data-current-stock="9"',$all);
        $this->assertSame(1,substr_count($all,'value="'.$sameName->id.'"'));
        $this->assertStringNotContainsString('value="'.$foreignProduct->id.'"',$all);

        $filtered=$this->getJson(route('ajax.category.products.public',$category->id))->assertOk()->json('data');
        $this->assertSame(1,substr_count($filtered,'value="'.$product->id.'"'));
        $this->assertSame(1,substr_count($filtered,'value="'.$sameName->id.'"'));
        $this->assertStringNotContainsString('value="'.$foreignProduct->id.'"',$filtered);

        $details=$this->getJson(route('ajax.sale.product.purchaseDetails.public',['id'=>$product->id]))
            ->assertOk()->json('getData');
        $this->assertCount(3,$details);
        $this->assertSame($lotIds, collect($details)->pluck('purchaseId')->map(fn($id)=>(int)$id)->all());
        $this->assertSame([3,2,4],collect($details)->pluck('currentStock')->map(fn($qty)=>(int)$qty)->all());
    }

    public function test_brand_category_product_filters_are_business_scoped_and_price_uses_latest_eligible_batch(): void
    {
        $brandA=\App\Models\Brand::forceCreate(['name'=>'Brand A','businessId'=>1]);
        $brandB=\App\Models\Brand::forceCreate(['name'=>'Brand B','businessId'=>1]);
        $categoryA=\App\Models\Category::forceCreate(['name'=>'Category A','businessId'=>1]);
        $categoryB=\App\Models\Category::forceCreate(['name'=>'Category B','businessId'=>1]);
        $base=$this->inventory(1,false);
        $product=Product::findOrFail($base['purchase']->productName);
        $product->update(['brand'=>(string)$brandA->id,'category'=>(string)$categoryA->id,'barCode'=>'LATEST-BATCH-BARCODE']);

        $supplier=Supplier::create(['name'=>'Latest supplier','businessId'=>1]);
        $latestSameDay=PurchaseProduct::create([
            'productName'=>$product->id,'supplier'=>$supplier->id,'businessId'=>1,'purchase_date'=>'2026-10-15',
            'invoice'=>uniqid('LATEST-'),'qty'=>2,'buyPrice'=>125,'salePriceExVat'=>175,'totalAmount'=>250,
        ]);
        ProductStock::create(['productId'=>$product->id,'purchaseId'=>$latestSameDay->id,'businessId'=>1,'currentStock'=>2]);
        // Equal purchase dates are resolved by the higher purchase-row ID.
        $latestTie=PurchaseProduct::create([
            'productName'=>$product->id,'supplier'=>$supplier->id,'businessId'=>1,'purchase_date'=>'2026-10-15',
            'invoice'=>uniqid('LATEST-TIE-'),'qty'=>1,'buyPrice'=>127,'salePriceExVat'=>185,'totalAmount'=>127,
        ]);
        ProductStock::create(['productId'=>$product->id,'purchaseId'=>$latestTie->id,'businessId'=>1,'currentStock'=>1]);
        $outOfStock=PurchaseProduct::create([
            'productName'=>$product->id,'supplier'=>$supplier->id,'businessId'=>1,'purchase_date'=>'2026-10-16',
            'invoice'=>uniqid('EMPTY-'),'qty'=>1,'buyPrice'=>130,'salePriceExVat'=>999,'totalAmount'=>130,
        ]);
        ProductStock::create(['productId'=>$product->id,'purchaseId'=>$outOfStock->id,'businessId'=>1,'currentStock'=>0]);

        $otherBrand=Product::create(['name'=>'Brand B product','businessId'=>1,'brand'=>(string)$brandB->id,'category'=>(string)$categoryB->id]);
        $otherSupplier=Supplier::create(['name'=>'Brand B supplier','businessId'=>1]);
        $otherPurchase=PurchaseProduct::create(['productName'=>$otherBrand->id,'supplier'=>$otherSupplier->id,'businessId'=>1,'purchase_date'=>'2026-10-14','qty'=>2,'buyPrice'=>20,'salePriceExVat'=>40]);
        ProductStock::create(['productId'=>$otherBrand->id,'purchaseId'=>$otherPurchase->id,'businessId'=>1,'currentStock'=>2]);

        \App\Models\BusinessSetup::forceCreate(['id'=>2,'businessName'=>'Other Business']);
        $foreign=Product::create(['name'=>'Foreign Brand Product','businessId'=>2,'brand'=>(string)$brandA->id,'category'=>(string)$categoryA->id]);
        $foreignSupplier=Supplier::create(['name'=>'Foreign Brand Supplier','businessId'=>2]);
        $foreignPurchase=PurchaseProduct::create(['productName'=>$foreign->id,'supplier'=>$foreignSupplier->id,'businessId'=>2,'purchase_date'=>'2026-10-16','qty'=>50,'buyPrice'=>1,'salePriceExVat'=>2]);
        ProductStock::create(['productId'=>$foreign->id,'purchaseId'=>$foreignPurchase->id,'businessId'=>2,'currentStock'=>50]);

        $this->login();
        $brands=$this->getJson(route('ajax.brands.public'))->assertOk()->json('data');
        $this->getJson(route('ajax.brands.public',['businessId'=>2]))->assertForbidden();
        $this->assertStringContainsString('value="'.$brandA->id.'"',$brands);
        $this->assertStringContainsString('value="'.$brandB->id.'"',$brands);
        $categories=$this->getJson(route('ajax.categories.public',['brandId'=>$brandA->id]))->assertOk()->json('data');
        $this->getJson(route('ajax.categories.public',['brandId'=>$brandA->id,'businessId'=>2]))->assertForbidden();
        $this->assertStringContainsString('value="'.$categoryA->id.'"',$categories);
        $this->assertStringNotContainsString('value="'.$categoryB->id.'"',$categories);
        $filtered=$this->getJson(route('ajax.customer.products.public',[
            'id'=>Customer::create(['name'=>'Brand filter customer','businessId'=>1])->id,
            'brandId'=>$brandA->id,'categoryId'=>$categoryA->id,
        ]))->assertOk()->json('data');
        $this->getJson(route('ajax.customer.products.public',[
            'id'=>Customer::where('businessId',1)->latest('id')->value('id'),
            'brandId'=>$brandA->id,'categoryId'=>$categoryA->id,'businessId'=>2,
        ]))->assertForbidden();
        $this->assertSame(1,substr_count($filtered,'value="'.$product->id.'"'));
        $this->assertStringNotContainsString('value="'.$foreign->id.'"',$filtered);
        $details=$this->getJson(route('ajax.sale.product.purchaseDetails.public',['id'=>$product->id]))
            ->assertOk()->assertJsonPath('defaultSalePrice','185.00')->assertJsonPath('defaultPricePurchaseId',$latestTie->id);
        $this->postJson(route('product.findByBarcode'),['barcode'=>'LATEST-BATCH-BARCODE'])
            ->assertOk()->assertJsonPath('product.salePrice','185.00')->assertJsonPath('product.defaultPricePurchaseId',$latestTie->id);
        $this->assertSame('150.00',(string)$base['purchase']->fresh()->salePriceExVat);
        $this->assertSame('175.00',(string)$latestSameDay->fresh()->salePriceExVat);
        $this->assertSame('185.00',(string)$latestTie->fresh()->salePriceExVat);
        $this->assertCount(3,$details->json('getData'));
    }

    public function test_sale_can_allocate_one_product_across_fifo_purchase_lots_without_losing_lot_costs(): void
    {
        $first=$this->inventory(1,false);
        $productId=(int)$first['purchase']->productName;
        $supplier=Supplier::create(['name'=>'Second batch supplier','businessId'=>1]);
        $second=PurchaseProduct::create([
            'productName'=>$productId,'supplier'=>$supplier->id,'businessId'=>1,'purchase_date'=>'2026-10-12',
            'invoice'=>uniqid('PUR-'),'qty'=>2,'buyPrice'=>120,'salePriceExVat'=>160,'totalAmount'=>240,
            'grandTotal'=>240,'paidAmount'=>240,'dueAmount'=>0,
        ]);
        $secondStock=ProductStock::create(['productId'=>$productId,'purchaseId'=>$second->id,'businessId'=>1,'currentStock'=>2]);
        $this->optional();
        $customer=Customer::create(['name'=>'Multi-lot Customer','businessId'=>1]);
        $this->login();
        $this->postJson(route('saveSale'),[
            'date'=>'2026-10-16','customerId'=>$customer->id,
            // A client cannot skip the earlier FIFO lot and submit only a later lot.
            'purchaseData'=>[$second->id],'qty'=>[1],'salePrice'=>[160],'buyPrice'=>[120],
            'warranty_days'=>[''],'serialId'=>[[]],'totalSaleAmount'=>160,'discountAmount'=>0,
            'additionalChargeName'=>'','additionalChargeAmount'=>0,'grandTotal'=>160,
            'paidAmount'=>160,'dueAmount'=>0,'prevDue'=>0,'curDue'=>0,
        ])->assertUnprocessable()->assertJsonValidationErrors('purchaseData');
        $this->assertDatabaseCount('sale_products',0);
        $this->assertSame(3,(int)$first['stock']->fresh()->currentStock);
        $this->assertSame(2,(int)$secondStock->fresh()->currentStock);
        $mismatchedPrices=$this->payload($first, 3);
        $mismatchedPrices['purchaseData']=[$first['purchase']->id,$second->id];
        $mismatchedPrices['qty']=[3,2];
        $mismatchedPrices['salePrice']=[200,201];
        $mismatchedPrices['buyPrice']=[100,120];
        $this->postJson(route('saveSale'),$mismatchedPrices)->assertUnprocessable()->assertJsonValidationErrors('salePrice.1');
        $this->assertDatabaseCount('sale_products',0);
        $this->assertSame(3,(int)$first['stock']->fresh()->currentStock);
        $this->assertSame(2,(int)$secondStock->fresh()->currentStock);
        $response=$this->post(route('saveSale'),[
            'date'=>'2026-10-16','customerId'=>$customer->id,
            'purchaseData'=>[$first['purchase']->id,$second->id],'qty'=>[3,2],
            'salePrice'=>[200,200],'buyPrice'=>[1,1],'warranty_days'=>['',''],
            'serialId'=>[[],[]],'totalSaleAmount'=>1,'discountAmount'=>0,
            'additionalChargeName'=>'','additionalChargeAmount'=>0,'grandTotal'=>1,
            'paidAmount'=>1000,'dueAmount'=>0,'prevDue'=>0,'curDue'=>0,
        ]);
        $response->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(0,(int)$first['stock']->fresh()->currentStock);
        $this->assertSame(0,(int)$secondStock->fresh()->currentStock);
        $items=InvoiceItem::orderBy('id')->get();
        $this->assertSame([$first['purchase']->id,$second->id],$items->pluck('purchaseId')->map(fn($id)=>(int)$id)->all());
        $this->assertSame(['200.00','200.00'],$items->pluck('salePrice')->map(fn($price)=>number_format((float)$price,2,'.',''))->all());
        $this->assertSame(['100.00','120.00'],$items->pluck('buyPrice')->map(fn($price)=>number_format((float)$price,2,'.',''))->all());
        $this->assertSame(['300.00','240.00'],$items->pluck('totalPurchase')->map(fn($price)=>number_format((float)$price,2,'.',''))->all());
        $this->assertSame(['300.00','160.00'],$items->pluck('profitTotal')->map(fn($price)=>number_format((float)$price,2,'.',''))->all());
        $this->assertSame('1000.00',number_format((float)SaleProduct::latest('id')->value('grandTotal'),2,'.',''));
    }

    public function test_serialized_sale_keeps_the_selected_serials_actual_purchase_batch(): void
    {
        $first=$this->inventory(1,true);
        $productId=(int)$first['purchase']->productName;
        $supplier=Supplier::create(['name'=>'Serialized second batch supplier','businessId'=>1]);
        $second=PurchaseProduct::create([
            'productName'=>$productId,'supplier'=>$supplier->id,'businessId'=>1,'purchase_date'=>'2026-10-12',
            'invoice'=>uniqid('SERIAL-PUR-'),'qty'=>2,'buyPrice'=>120,'salePriceExVat'=>160,'totalAmount'=>240,
        ]);
        $secondStock=ProductStock::create(['productId'=>$productId,'purchaseId'=>$second->id,'businessId'=>1,'currentStock'=>2]);
        $serial=ProductSerial::create([
            'productId'=>$productId,'purchaseId'=>$second->id,'serialNumber'=>'SECOND-BATCH-SERIAL','status'=>'available',
        ]);
        $customer=Customer::create(['name'=>'Serialized batch customer','businessId'=>1]);
        $this->login();
        $response=$this->post(route('saveSale'),[
            'date'=>'2026-10-16','customerId'=>$customer->id,
            'purchaseData'=>[$second->id],'qty'=>[1],'salePrice'=>[160],'buyPrice'=>[120],
            'warranty_days'=>[''],'serialId'=>[[$serial->id]],'totalSaleAmount'=>160,'discountAmount'=>0,
            'additionalChargeName'=>'','additionalChargeAmount'=>0,'grandTotal'=>160,
            'paidAmount'=>160,'dueAmount'=>0,'prevDue'=>0,'curDue'=>0,
        ]);
        $response->assertSessionHasNoErrors()->assertRedirect();
        $sale=SaleProduct::firstOrFail();
        $line=InvoiceItem::firstOrFail();
        $this->assertSame((int)$second->id,(int)$line->purchaseId);
        $this->assertSame((int)$sale->id,(int)$serial->fresh()->saleId);
        $this->assertSame(3,(int)$first['stock']->fresh()->currentStock);
        $this->assertSame(1,(int)$secondStock->fresh()->currentStock);
    }

    public function test_barcode_search_uses_the_selected_authorized_business(): void
    {
        $this->inventory();
        \App\Models\BusinessSetup::forceCreate(['id'=>2,'businessName'=>'Other Business']);
        $foreign=Product::create(['name'=>'Foreign Barcode Product','businessId'=>2,'barCode'=>'FOREIGN-LOOKUP']);
        $supplier=Supplier::create(['name'=>'Foreign Barcode Supplier','businessId'=>2]);
        $purchase=PurchaseProduct::create(['productName'=>$foreign->id,'supplier'=>$supplier->id,'businessId'=>2,'qty'=>1,'buyPrice'=>1,'salePriceExVat'=>2]);
        ProductStock::create(['productId'=>$foreign->id,'purchaseId'=>$purchase->id,'businessId'=>2,'currentStock'=>1]);

        $this->login();
        $this->postJson(route('product.findByBarcode'),['barcode'=>'FOREIGN-LOOKUP'])->assertNotFound();
        $this->postJson(route('product.findByBarcode'),['barcode'=>'FOREIGN-LOOKUP','businessId'=>2])->assertForbidden();

        $this->actingAs($this->actor('superadmin'), 'admin');
        $this->postJson(route('product.findByBarcode'),['barcode'=>'FOREIGN-LOOKUP'])
            ->assertUnprocessable()->assertJsonValidationErrors('businessId');
        $this->postJson(route('product.findByBarcode'),['barcode'=>'FOREIGN-LOOKUP','businessId'=>2])
            ->assertOk()->assertJsonPath('product.id',$foreign->id);
    }
}
