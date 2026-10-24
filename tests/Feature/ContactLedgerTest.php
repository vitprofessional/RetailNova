<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ContactLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_ledger_totals_only_include_the_owning_business_sales(): void
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Customer Ledger Shop']);
        $otherBusiness = BusinessSetup::forceCreate(['businessName' => 'Other Ledger Shop']);
        $actor = $this->actor((int) $business->id);
        $customer = Customer::forceCreate(['name' => 'Local Ledger Customer', 'businessId' => $business->id]);
        $foreignCustomer = Customer::forceCreate(['name' => 'Foreign Ledger Customer', 'businessId' => $otherBusiness->id]);

        $this->sale((int) $business->id, (int) $customer->id, 'SALE-LOCAL-1', 100, 40);
        $this->sale((int) $otherBusiness->id, (int) $foreignCustomer->id, 'SALE-FOREIGN-1', 900, 0);

        $this->actingAs($actor, 'admin');
        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Mobile)')
            ->get(route('contact.ledger', ['type' => 'customer', 'id' => $customer->id]))
            ->assertOk()->assertSee('SALE-LOCAL-1')->assertDontSee('SALE-FOREIGN-1')
            ->assertSee('৳100')->assertSee('৳40')->assertSee('৳60');

        $this->assertSame(1, substr_count($response->getContent(), 'SALE-LOCAL-1'));
        $this->get(route('contact.ledger', ['type' => 'customer', 'id' => $foreignCustomer->id]))->assertNotFound();
    }

    public function test_supplier_ledger_uses_invoice_headers_and_excludes_other_businesses(): void
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Supplier Ledger Shop']);
        $otherBusiness = BusinessSetup::forceCreate(['businessName' => 'Other Supplier Shop']);
        $actor = $this->actor((int) $business->id);
        $supplier = Supplier::forceCreate(['name' => 'Local Supplier', 'businessId' => $business->id]);
        $foreignSupplier = Supplier::forceCreate(['name' => 'Foreign Supplier', 'businessId' => $otherBusiness->id]);
        $invoice = PurchaseInvoice::forceCreate([
            'businessId' => $business->id, 'supplier_id' => $supplier->id, 'invoice' => 'PUR-LOCAL-1',
            'purchase_date' => '2026-10-24', 'grand_total' => 250, 'paid_amount' => 100, 'due_amount' => 150,
        ]);
        PurchaseInvoice::forceCreate([
            'businessId' => $otherBusiness->id, 'supplier_id' => $foreignSupplier->id, 'invoice' => 'PUR-FOREIGN-1',
            'purchase_date' => '2026-10-24', 'grand_total' => 800, 'paid_amount' => 0, 'due_amount' => 800,
        ]);

        $this->actingAs($actor, 'admin');
        $this->get(route('contact.ledger', ['type' => 'supplier', 'id' => $supplier->id]))
            ->assertOk()->assertSee('PUR-LOCAL-1')->assertDontSee('PUR-FOREIGN-1')
            ->assertSee('৳250')->assertSee('৳100')->assertSee('৳150');
        $this->assertSame((int) $business->id, (int) $invoice->businessId);
        $this->get(route('contact.ledger', ['type' => 'supplier', 'id' => $foreignSupplier->id]))->assertNotFound();
    }

    public function test_fake_balance_sheet_route_is_removed_and_id_based_ledger_is_registered(): void
    {
        $this->assertFalse(Route::has('balancesheet'));
        $this->assertTrue(Route::has('contact.ledger'));
    }

    public function test_mobile_customer_contact_card_opens_its_business_scoped_ledger(): void
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Contact Card Shop']);
        $actor = $this->actor((int) $business->id);
        $customer = Customer::forceCreate(['name' => 'Ledger Link Customer', 'businessId' => $business->id]);
        $this->actingAs($actor, 'admin');

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Mobile)')
            ->get(route('addCustomer'))->assertOk()
            ->assertSee('href="'.route('contact.ledger', ['type' => 'customer', 'id' => $customer->id]).'"', false);
    }

    private function actor(int $businessId): AdminUser
    {
        return AdminUser::forceCreate([
            'fullName' => 'Ledger Test Admin',
            'mail' => uniqid('ledger-', true).'@example.test',
            'password' => bcrypt('test-only'),
            'businessId' => $businessId,
            'role' => 'admin',
        ]);
    }

    private function sale(int $businessId, int $customerId, string $invoice, float $total, float $paid): void
    {
        DB::table('sale_products')->insert([
            'businessId' => $businessId, 'customerId' => $customerId, 'invoice' => $invoice,
            'date' => '2026-10-24', 'grandTotal' => $total, 'paidAmount' => $paid,
            'curDue' => max(0, $total - $paid), 'status' => 'Ordered', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
