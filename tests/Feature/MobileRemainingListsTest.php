<?php

namespace Tests\Feature;

use App\Models\Customer;
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
}
