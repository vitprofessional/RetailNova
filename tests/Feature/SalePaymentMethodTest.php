<?php

namespace Tests\Feature;

use App\Models\SaleProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSaleFixtures;
use Tests\TestCase;

/** UI-1b: a sale records how it was paid (cash, bKash, Nagad or due) and shows it in the list and on receipts. */
class SalePaymentMethodTest extends TestCase
{
    use BuildsSaleFixtures;
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    // Never migrate or seed a configured development/production database.
    public function refreshDatabase()
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Payment method tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new \DOMXPath($document);
    }

    public function test_each_payment_method_is_saved(): void
    {
        $this->actingAs($this->actor('admin'), 'admin');

        foreach (SaleProduct::PAYMENT_METHODS as $method) {
            // "Due" means nothing is paid now, as the sale screen sends it.
            $payment = $method === 'due'
                ? ['paidAmount' => 0, 'dueAmount' => 150, 'curDue' => 150]
                : ['paidAmount' => 150, 'dueAmount' => 0, 'curDue' => 0];
            $sale = $this->sell($this->inventory(), 1, false, null, ['payment_method' => $method] + $payment)->fresh();

            $this->assertSame($method, $sale->payment_method);
            $this->assertEquals($method === 'due' ? 0 : 150, (float) $sale->paidAmount, $method);
            $this->assertEquals($method === 'due' ? 150 : 0, (float) $sale->invoiceDue, $method);
        }
        $this->assertSame(['cash', 'bkash', 'nagad', 'due'], SaleProduct::query()->orderBy('id')->pluck('payment_method')->all());
    }

    public function test_an_unknown_payment_method_is_rejected_with_422(): void
    {
        $this->actingAs($this->actor('admin'), 'admin');
        $inventory = $this->inventory();

        foreach (['card', 'CASH', 'bank', 'বিকাশ', ['cash']] as $invalid) {
            $this->postJson(route('saveSale'), $this->salePayload($inventory, 1, false, null, ['payment_method' => $invalid]))
                ->assertStatus(422)
                ->assertJsonValidationErrors('payment_method');
        }
        $this->assertSame(0, SaleProduct::query()->count(), 'A rejected sale is not recorded.');
    }

    public function test_a_sale_without_a_payment_method_stays_null(): void
    {
        // Sales recorded before the column existed, and clients that do not send it, have no method.
        $this->actingAs($this->actor('admin'), 'admin');
        $sale = $this->sell($this->inventory(), 1)->fresh();

        $this->assertNull($sale->payment_method);
        $this->assertNull($sale->paymentMethodLabel());
    }

    public function test_sale_list_and_receipts_show_the_payment_method(): void
    {
        $admin = $this->actor('admin');
        $this->actingAs($admin, 'admin');
        $bkash = $this->sell($this->inventory(), 1, false, null, ['payment_method' => 'bkash']);
        $legacy = $this->sell($this->inventory(), 1);

        foreach (['bn' => 'বিকাশ', 'en' => 'bKash'] as $locale => $label) {
            $admin->update(['preferred_locale' => $locale]);

            $list = $this->xpath($this->get(route('saleList'))->assertOk()->getContent());
            $cell = fn (SaleProduct $sale) => trim($list->query('//tr[td[contains(@class,"rn-invoice-cell") and @title="'.$sale->invoice.'"]]/td[contains(@class,"rn-payment-method-cell")]')->item(0)?->textContent ?? '');
            $this->assertSame($label, $cell($bkash), $locale);
            $this->assertSame('—', $cell($legacy), "$locale: an older sale has no recorded method");
            $this->assertCount(1, $list->query('//table[@id="salesTable"]//th[normalize-space()="'.__('sales.payment_method', [], $locale).'"]'));

            foreach (['a4', 'thermal80', 'thermal58'] as $printer) {
                $this->get(route('invoiceGenerate', ['id' => $bkash->id, 'printer' => $printer]))->assertOk()->assertSee($label);
            }
            $this->followingRedirects()->get(route('invoiceThermalPrint', ['id' => $bkash->id]))->assertOk()->assertSee($label);
            $this->followingRedirects()->get(route('invoiceThermalPrint', ['id' => $legacy->id]))->assertOk()->assertDontSee(__('invoices.payment_mode', [], $locale));
        }
    }
}
