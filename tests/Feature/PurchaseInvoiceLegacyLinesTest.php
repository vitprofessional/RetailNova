<?php

namespace Tests\Feature;

use App\Models\{AdminUser, BusinessSetup, Product, PurchaseInvoice, PurchaseProduct, Supplier};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoiceLegacyLinesTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        BusinessSetup::forceCreate(['id'=>1,'businessName'=>'Invoice QA','businessType'=>'computer_shop']);
        $user=AdminUser::create(['fullName'=>'QA','mail'=>'invoice-qa@example.test','password'=>'unused','role'=>'superadmin','businessId'=>1]);
        $this->actingAs($user,'admin');
        $supplier=Supplier::create(['name'=>'Legacy Supplier','businessId'=>1]);
        $product=Product::create(['name'=>'Legacy USB Cable','businessId'=>1]);
        $invoice=PurchaseInvoice::create(['businessId'=>1,'supplier_id'=>$supplier->id,'invoice'=>'LEGACY-2026-01','purchase_date'=>'2026-10-24','total_amount'=>20,'grand_total'=>20,'due_amount'=>20]);

        return [$supplier,$product,$invoice];
    }

    private function legacyLine(Supplier $supplier, Product $product): PurchaseProduct
    {
        return PurchaseProduct::create([
            'productName'=>$product->id,'supplier'=>(string)$supplier->id,'businessId'=>1,
            'invoice'=>'LEGACY-2026-01','purchase_date'=>'2026-10-24','qty'=>2,
            'buyPrice'=>10,'salePriceExVat'=>15,'vatPercent'=>0,'totalAmount'=>20,
            'purchase_invoice_id'=>null,
        ]);
    }

    public function test_invoice_shows_legacy_lines_only_when_full_identity_matches_one_header(): void
    {
        [$supplier,$product,$invoice]=$this->fixture();
        $line=$this->legacyLine($supplier,$product);

        $this->get(route('purchase.invoice.show',$invoice->id))
            ->assertOk()->assertSee('Legacy USB Cable')->assertSee('data-purchase-line="'.$line->id.'"',false)
            ->assertDontSee(__('purchases.no_items'));
    }

    public function test_ambiguous_legacy_invoice_identity_is_not_arbitrarily_attached(): void
    {
        [$supplier,$product,$invoice]=$this->fixture();
        $this->legacyLine($supplier,$product);
        $duplicate=PurchaseInvoice::create(['businessId'=>1,'supplier_id'=>$supplier->id,'invoice'=>'LEGACY-2026-01','purchase_date'=>'2026-10-24','total_amount'=>20,'grand_total'=>20,'due_amount'=>20]);

        $this->get(route('purchase.invoice.show',$invoice->id))->assertOk()->assertDontSee('Legacy USB Cable')->assertSee(__('purchases.no_items'));
        $this->get(route('purchase.invoice.show',$duplicate->id))->assertOk()->assertDontSee('Legacy USB Cable')->assertSee(__('purchases.no_items'));
    }
}
