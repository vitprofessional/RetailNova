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
}
