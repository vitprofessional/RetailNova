<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Models\ProductSerial;
use App\Models\ProductStock;
use App\Models\SaleProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSaleFixtures;
use Tests\TestCase;

/**
 * Cancelling a sale keeps its history (soft delete + audit fields), returns only the
 * stock that the sale still holds, releases its serials, and is limited to admins.
 */
class SaleCancellationTest extends TestCase
{
    use BuildsSaleFixtures;
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    // Never migrate or seed a configured development/production database.
    public function refreshDatabase()
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Sale cancellation tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    private function cancel(SaleProduct $sale, array $data = ['reason' => 'Customer changed their mind'])
    {
        return $this->delete(route('delSale', $sale->id), $data);
    }

    private function assertSaleUntouched(SaleProduct $sale, array $inventory, int $expectedStock = 1, int $soldSerials = 2): void
    {
        $fresh = SaleProduct::withTrashed()->findOrFail($sale->id);
        $this->assertFalse($fresh->trashed(), 'Sale must not be cancelled.');
        $this->assertNull($fresh->cancelled_at);
        $this->assertNull($fresh->cancel_reason);
        $this->assertSame(0, InvoiceItem::onlyTrashed()->where('saleId', $sale->id)->count());
        $this->assertSame($expectedStock, (int) $inventory['stock']->fresh()->currentStock);
        $this->assertSame($soldSerials, ProductSerial::where('saleId', $sale->id)->where('status', 'sold')->count());
    }

    public function test_sale_return_page_exposes_shared_mobile_form_and_labeled_product_fields(): void
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $sale = $this->sell($inventory);

        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile')
            ->get(route('returnSale', $sale->id))->assertOk();

        $this->assertStringContainsString('rn-m-return-form', $response->getContent());
        $this->assertStringContainsString('data-label="'.__('sales.return_qty').'"', $response->getContent());
        $this->assertStringContainsString('rn-m-return-actions', $response->getContent());
        $this->assertSame(1, substr_count($response->getContent(), 'rn-m-return-actions'), 'Sale return actions must share one sticky row.');
    }

    // a) salesmanager and gm get 403 on cancel; sale, stock and serials unchanged
    public function test_non_admin_roles_cannot_cancel_single_or_bulk(): void
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $sale = $this->sell($inventory);

        foreach (['salesmanager', 'gm'] as $role) {
            $this->actingAs($this->actor($role), 'admin');
            $this->cancel($sale)->assertForbidden();
            $this->post(route('sales.bulkDelete'), ['selected' => [$sale->id], 'reason' => 'Not allowed'])->assertForbidden();
        }

        $this->assertSaleUntouched($sale, $inventory);
    }

    // b) GET /sale/delete/{id} returns 405 (and a plain POST without method spoofing too)
    public function test_cancel_route_only_accepts_delete(): void
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $sale = $this->sell($inventory);

        $this->get(route('delSale', $sale->id))->assertStatus(405);
        $this->post(route('delSale', $sale->id), ['reason' => 'Plain post'])->assertStatus(405);
        $this->get(route('sales.bulkDelete'))->assertStatus(405);

        $this->assertSaleUntouched($sale, $inventory);
    }

    // c) cancel without a valid reason → validation error, nothing changed
    public function test_cancel_requires_a_reason_of_3_to_500_characters(): void
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $sale = $this->sell($inventory);

        foreach ([[], ['reason' => ''], ['reason' => '   '], ['reason' => 'ab'], ['reason' => str_repeat('x', 501)]] as $data) {
            $this->cancel($sale, $data)->assertSessionHasErrors('reason');
        }
        $this->post(route('sales.bulkDelete'), ['selected' => [$sale->id]])->assertSessionHasErrors('reason');

        $this->assertSaleUntouched($sale, $inventory);
    }

    // d) admin cancel of a 2-unit serialized sale
    public function test_admin_cancel_returns_stock_releases_serials_and_keeps_an_audited_soft_deleted_record(): void
    {
        $inventory = $this->inventory();
        $admin = $this->actor('admin');
        $this->actingAs($admin, 'admin');
        $sale = $this->sell($inventory);
        $this->assertSame(1, (int) $inventory['stock']->fresh()->currentStock);

        $this->cancel($sale, ['reason' => '  Wrong customer selected  '])
            ->assertSessionHasNoErrors()->assertRedirect(route('saleList'));

        $this->assertSame(3, (int) $inventory['stock']->fresh()->currentStock);
        foreach (array_slice($inventory['serials'], 0, 2) as $serial) {
            $serial = $serial->fresh();
            $this->assertNull($serial->saleId);
            $this->assertSame('available', $serial->status);
            $this->assertNull($serial->sold_at);
        }

        $this->assertNull(SaleProduct::find($sale->id), 'Cancelled sale must be hidden from normal queries.');
        $cancelled = SaleProduct::withTrashed()->findOrFail($sale->id);
        $this->assertTrue($cancelled->trashed());
        $this->assertSame('Wrong customer selected', $cancelled->cancel_reason);
        $this->assertSame((int) $admin->id, (int) $cancelled->cancelled_by);
        $this->assertNotNull($cancelled->cancelled_at);

        $this->assertSame(0, InvoiceItem::where('saleId', $sale->id)->count());
        $this->assertSame(1, InvoiceItem::onlyTrashed()->where('saleId', $sale->id)->count());
        $this->assertDatabaseHas('sale_products', ['id' => $sale->id]);
        $this->assertStringContainsString((string) $sale->invoice, $this->alertText());
    }

    // e) backorder lines never reduced stock, so cancelling them must not add stock
    public function test_cancelling_a_backorder_line_does_not_increase_stock(): void
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $sale = $this->sell($inventory, 4, true);
        $this->assertTrue((bool) InvoiceItem::where('saleId', $sale->id)->value('isBackorder'));
        $this->assertSame(3, (int) $inventory['stock']->fresh()->currentStock);

        $this->cancel($sale)->assertSessionHasNoErrors()->assertRedirect(route('saleList'));

        $this->assertSame(3, (int) $inventory['stock']->fresh()->currentStock);
        $this->assertTrue(SaleProduct::withTrashed()->findOrFail($sale->id)->trashed());
        $this->assertSame(1, InvoiceItem::onlyTrashed()->where('saleId', $sale->id)->count());
    }

    // f) after a partial return, cancel returns only the quantity the sale still holds
    public function test_cancel_after_partial_return_restores_only_the_remaining_quantity(): void
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $sale = $this->sell($inventory);

        $this->post(route('saleReturnSave'), [
            'customerId' => $sale->customerId,
            'saleId' => [$sale->id], 'productId' => [$inventory['product']->id],
            'purchaseId' => [$inventory['purchase']->id], 'totalQty' => [1],
            'adjustAmount' => 0, 'returnNote' => 'One unit back',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(1, (int) InvoiceItem::where('saleId', $sale->id)->value('qty'));
        $this->assertSame(2, (int) $inventory['stock']->fresh()->currentStock);

        $this->cancel($sale)->assertSessionHasNoErrors()->assertRedirect(route('saleList'));

        $this->assertSame(3, (int) $inventory['stock']->fresh()->currentStock, 'Stock must never exceed what was purchased.');
        $this->assertSame(0, ProductSerial::whereNotNull('saleId')->count());
        $this->assertSame(3, ProductSerial::where('status', 'available')->count());
    }

    // g) a cancelled sale no longer counts in previous due and is not listed
    public function test_cancelled_sale_leaves_previous_due_and_the_sale_list(): void
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $customer = Customer::create(['name' => 'Due Customer', 'businessId' => 1]);
        $sale = $this->sell($inventory, 2, false, $customer);

        $before = (float) $this->getJson(route('ajax.customer.previousDue.public', $customer->id))->assertOk()->json('prevDue');
        $this->assertGreaterThan(0, $before);
        $this->get(route('saleList'))->assertOk()->assertSee((string) $sale->invoice);

        $this->cancel($sale)->assertSessionHasNoErrors();
        // The row is kept (soft-deleted); it must be filtered out, not simply gone.
        $this->assertTrue(SaleProduct::withTrashed()->findOrFail($sale->id)->trashed());

        $this->assertSame('0.00', $this->getJson(route('ajax.customer.previousDue.public', $customer->id))->assertOk()->json('prevDue'));
        $this->get(route('saleList'))->assertOk()->assertDontSee((string) $sale->invoice);
        $this->get(route('returnSale', $sale->id))->assertRedirect();
    }

    // h) cancelling twice is refused and never returns stock twice
    public function test_second_cancel_is_refused_and_stock_is_not_doubled(): void
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $sale = $this->sell($inventory);

        $this->cancel($sale)->assertSessionHasNoErrors();
        $firstCancelledAt = SaleProduct::withTrashed()->findOrFail($sale->id)->cancelled_at;
        $this->assertSame(3, (int) $inventory['stock']->fresh()->currentStock);

        $this->cancel($sale, ['reason' => 'Second attempt'])->assertRedirect();
        $this->assertStringContainsString((string) $sale->invoice, $this->alertText());

        $this->assertSame(3, (int) $inventory['stock']->fresh()->currentStock);
        $cancelled = SaleProduct::withTrashed()->findOrFail($sale->id);
        $this->assertSame('Customer changed their mind', $cancelled->cancel_reason);
        $this->assertEquals($firstCancelledAt, $cancelled->cancelled_at);
    }

    // UI: only admins get the cancel controls, and they always carry a reason field
    public function test_cancel_controls_are_shown_only_to_admins_in_both_languages(): void
    {
        $inventory = $this->inventory();
        $admin = $this->actor('admin');
        $this->actingAs($admin, 'admin');
        $this->sell($inventory);

        foreach (['en', 'bn'] as $locale) {
            $admin->update(['preferred_locale' => $locale]);
            $page = $this->get(route('saleList'))->assertOk();
            $page->assertSee('lang="'.$locale.'"', false)
                ->assertSee('data-cancel-sale-form', false)
                ->assertSee('data-bulk-reason', false)
                ->assertSee('name="reason"', false)
                ->assertSee(__('sales.cancel_sale'))
                ->assertSee(__('sales.cancel_selected_sales'));
        }

        $this->actingAs($this->actor('salesmanager'), 'admin');
        $this->get(route('saleList'))->assertOk()
            ->assertDontSee('data-cancel-sale-form', false)
            ->assertDontSee(route('sales.bulkDelete'), false);
    }

    // Sales are cancelled only from the sale list; the return list opens and offers no cancel/delete
    public function test_return_list_opens_and_offers_no_sale_cancellation(): void
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $sale = $this->sell($inventory);
        $this->post(route('saleReturnSave'), [
            'customerId' => $sale->customerId,
            'saleId' => [$sale->id], 'productId' => [$inventory['product']->id],
            'purchaseId' => [$inventory['purchase']->id], 'totalQty' => [1],
        ])->assertSessionHasNoErrors()->assertRedirect();

        // "/return/sale/list" used to be captured by "/return/sale/{id}" and bounced back.
        $this->get(route('returnSaleList'))->assertOk()
            ->assertSee((string) $sale->invoice)
            ->assertDontSee('data-cancel-sale-form', false)
            ->assertDontSee(route('delSale', $sale->id), false);
        $this->get(route('returnSale', $sale->id))->assertOk();
    }

    // i) bulk cancel is all-or-nothing and names the invoice that failed
    public function test_bulk_cancel_rolls_back_every_sale_when_one_fails(): void
    {
        $first = $this->inventory();
        $second = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $saleA = $this->sell($first);
        $saleB = $this->sell($second);
        // The second sale's stock row is gone, so its stock cannot be restored.
        ProductStock::where('purchaseId', $second['purchase']->id)->delete();

        $this->post(route('sales.bulkDelete'), ['selected' => [$saleA->id, $saleB->id], 'reason' => 'Duplicate entries'])
            ->assertRedirect();

        $this->assertStringContainsString((string) $saleB->invoice, $this->alertText());
        $this->assertSaleUntouched($saleA, $first);
        $this->assertFalse(SaleProduct::withTrashed()->findOrFail($saleB->id)->trashed());
        $this->assertSame(2, ProductSerial::where('saleId', $saleB->id)->count());

        // Without the broken sale the same request succeeds.
        $this->post(route('sales.bulkDelete'), ['selected' => [$saleA->id], 'reason' => 'Duplicate entries'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue(SaleProduct::withTrashed()->findOrFail($saleA->id)->trashed());
        $this->assertSame(3, (int) $first['stock']->fresh()->currentStock);
    }
}
