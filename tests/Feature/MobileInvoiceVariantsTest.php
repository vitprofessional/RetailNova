<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseProduct;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceItem;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileInvoiceVariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_purchase_invoice_renders_its_product_line_without_qr_print_markup(): void
    {
        [$business, $actor] = $this->businessAndActor();
        $supplier = Supplier::forceCreate(['name' => 'Mobile Supplier', 'businessId' => $business->id]);
        $product = Product::forceCreate(['name' => 'Mobile Purchase Cable', 'businessId' => $business->id]);
        $invoice = PurchaseInvoice::forceCreate([
            'businessId' => $business->id, 'supplier_id' => $supplier->id,
            'invoice' => 'MOBILE-PURCHASE-1', 'purchase_date' => '2026-10-24',
            'total_amount' => 24, 'grand_total' => 24, 'due_amount' => 24,
        ]);
        PurchaseProduct::forceCreate([
            'businessId' => $business->id, 'productName' => $product->id,
            'supplier' => (string) $supplier->id, 'invoice' => $invoice->invoice,
            'purchase_date' => '2026-10-24', 'qty' => 2, 'buyPrice' => 12,
            'salePriceExVat' => 18, 'vatPercent' => 0, 'totalAmount' => 24,
            'purchase_invoice_id' => $invoice->id,
        ]);

        $this->actingAs($actor, 'admin')
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) Mobile')
            ->get(route('purchase.invoice.show', $invoice->id))
            ->assertOk()->assertSee('data-mobile-document-receipt', false)
            ->assertSee('Mobile Purchase Cable')->assertDontSee(__('purchases.no_items'))
            ->assertDontSee('invoice-qr-section')->assertDontSee('data-invoice-qr')
            ->assertDontSee('id="purchaseInvoicePrint"', false)->assertDontSee('<div class="invoice-header">', false);
    }

    public function test_mobile_quotation_and_service_invoice_render_their_lines_without_qr_markup(): void
    {
        [$business, $actor] = $this->businessAndActor();
        $customer = Customer::forceCreate(['name' => 'Mobile Invoice Customer', 'businessId' => $business->id]);
        $product = Product::forceCreate(['name' => 'Mobile Quote Product', 'businessId' => $business->id]);
        $quote = Quotation::forceCreate([
            'businessId' => $business->id, 'quote_number' => 'MOBILE-QUOTE-1',
            'customer_id' => $customer->id, 'date' => '2026-10-24', 'status' => 'draft',
            'subtotal' => 30, 'discount_total' => 0, 'grand_total' => 30,
        ]);
        QuotationItem::forceCreate([
            'businessId' => $business->id, 'quotation_id' => $quote->id,
            'product_id' => $product->id, 'description' => 'Mobile quote line',
            'qty' => 1, 'unit_price' => 30, 'discount_percent' => 0,
            'discount_amount' => 0, 'line_total' => 30,
        ]);
        $serviceInvoice = ServiceInvoice::forceCreate([
            'businessId' => $business->id, 'invoice_number' => 'MOBILE-SERVICE-1',
            'customer_id' => $customer->id, 'is_walkin' => false, 'total_amount' => 40,
        ]);
        ServiceInvoiceItem::forceCreate([
            'businessId' => $business->id, 'invoice_id' => $serviceInvoice->id,
            'service_name' => 'Mobile Screen Repair', 'rate' => 40, 'qty' => 1,
            'line_total' => 40,
        ]);

        $this->actingAs($actor, 'admin')->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) Mobile');
        $this->get(route('quotation.show', $quote->id))->assertOk()
            ->assertSee('data-mobile-document-receipt', false)->assertSee('Mobile Quote Product')
            ->assertSee('Mobile quote line')->assertDontSee('invoice-qr-section')->assertDontSee('data-invoice-qr')
            ->assertDontSee('<div class="invoice-header">', false);
        $this->get(route('serviceInvoiceView', $serviceInvoice->id))->assertOk()
            ->assertSee('data-mobile-document-receipt', false)->assertSee('Mobile Screen Repair')
            ->assertDontSee('invoice-qr-section')->assertDontSee('data-invoice-qr')
            ->assertDontSee('rn-m-service-invoice-header', false);
    }

    private function businessAndActor(): array
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Mobile Invoice Shop', 'businessType' => 'computer_shop']);
        $actor = AdminUser::forceCreate([
            'fullName' => 'Mobile Invoice Admin', 'mail' => uniqid('mobile-invoice-', true).'@example.test',
            'password' => bcrypt('test-only'), 'role' => 'admin', 'businessId' => $business->id,
        ]);

        return [$business, $actor];
    }
}
