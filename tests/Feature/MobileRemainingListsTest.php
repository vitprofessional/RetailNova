<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\BusinessLocation;
use OwenIt\Auditing\Models\Audit;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSaleFixtures;
use Tests\TestCase;

class MobileRemainingListsTest extends TestCase
{
    use BuildsSaleFixtures;
    use RefreshDatabase;

    private const PHONE = 'Mozilla/5.0 (Linux; Android 13; Mobile)';

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
    }

    public function test_remaining_transaction_lists_render_mobile_cards_without_desktop_tables(): void
    {
        foreach (['damageProductList', 'serviceProvideList', 'serialList', 'rma.index', 'quotation.list', 'returnSaleList', 'returnPurchaseList', 'account.chart', 'account.transactions'] as $name) {
            $response = $this->withHeader('User-Agent', self::PHONE)->get(route($name))->assertOk();
            $html = $response->getContent();
            $this->assertStringNotContainsString('<table', $html, $name.' renders mobile content, not the desktop table');
            $this->assertStringContainsString('rn-m-list-page', $html, $name);
        }
    }

    public function test_mobile_contact_lists_keep_deleted_contact_restore_actions_available(): void
    {
        $customer = Customer::forceCreate(['name' => 'Deleted Mobile Customer', 'businessId' => 1]);
        $customer->delete();
        $supplier = Supplier::forceCreate(['name' => 'Deleted Mobile Supplier', 'businessId' => 1]);
        $supplier->delete();

        $this->withHeader('User-Agent', self::PHONE)->get(route('addCustomer'))->assertOk()
            ->assertSee('<section class="rn-m-list-section rn-m-restored-list">', false)->assertSee('Deleted Mobile Customer')
            ->assertSee(route('restoreCustomer', ['id' => $customer->id]), false);
        $this->withHeader('User-Agent', self::PHONE)->get(route('addSupplier'))->assertOk()
            ->assertSee('<section class="rn-m-list-section rn-m-restored-list">', false)->assertSee('Deleted Mobile Supplier')
            ->assertSee(route('restoreSupplier', ['id' => $supplier->id]), false);
    }

    public function test_mobile_stock_page_displays_purchase_lots_and_invoice_context(): void
    {
        $inventory = $this->inventory();

        $this->withHeader('User-Agent', self::PHONE)->get(route('stockProduct'))->assertOk()
            ->assertSee('data-rn-list="stock-lots"', false)
            ->assertSee($inventory['product']->name)
            ->assertSee($inventory['purchase']->invoice)
            ->assertSee((string) $inventory['stock']->currentStock)
            ->assertDontSee('Purchase ID');
    }

    public function test_mobile_control_lists_render_records_as_cards_and_keep_actions(): void
    {
        $inventory = $this->inventory();
        $location = BusinessLocation::forceCreate([
            'businessId' => 1, 'name' => 'Mobile Branch', 'address' => 'Road 1', 'city' => 'Dhaka',
            'phone' => '01700000000', 'email' => 'branch@example.test', 'is_main_location' => false, 'status' => true,
        ]);
        Audit::create([
            'user_id' => auth('admin')->id(), 'event' => 'created',
            'auditable_type' => BusinessLocation::class, 'auditable_id' => $location->id,
            'old_values' => [], 'new_values' => ['name' => 'Mobile Branch'],
            'url' => '/business/locations', 'ip_address' => '127.0.0.1',
        ]);

        $this->withHeader('User-Agent', self::PHONE)->get(route('business.locations'))
            ->assertOk()->assertSee('data-rn-list="business-locations"', false)->assertSee('Mobile Branch')
            ->assertSee(route('business.locations.edit', $location->id), false)->assertDontSee('<table', false);

        $this->withHeader('User-Agent', self::PHONE)->get(route('barcode.index'))
            ->assertOk()->assertSee('data-rn-list="product-barcodes"', false)->assertSee($inventory['product']->name)
            ->assertSee('barcodeMobileSearch')->assertSee('product_ids[]')->assertDontSee('<table', false);

        $this->withHeader('User-Agent', self::PHONE)->get(route('audits.index'))
            ->assertOk()->assertSee('data-rn-list="audit-history"', false)->assertSee(route('audits.export'), false)
            ->assertSee('Mobile Branch')->assertDontSee('<table', false);
        $export = $this->get(route('audits.export'));
        $export->assertOk()->assertHeader('content-type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('Mobile Branch', $export->streamedContent());

        $this->withHeader('User-Agent', self::PHONE)->get(route('admin.manage.users.index'))
            ->assertOk()->assertSee('rn-superadmin-user-table', false)
            ->assertDontSee('Business ID', false);
    }
}
