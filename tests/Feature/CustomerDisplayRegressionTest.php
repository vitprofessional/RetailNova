<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Customer;
use App\Models\ProvideService;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SaleProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CustomerDisplayRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_walk_in_name_is_localized_for_display_without_changing_stored_value(): void
    {
        $business = $this->business('Display Shop');
        $customer = Customer::forceCreate([
            'name' => Customer::WALK_IN_NAME,
            'businessId' => $business->id,
        ]);

        App::setLocale('bn');
        $this->assertTrue($customer->fresh()->isWalkIn());
        $this->assertSame(__('common.walking_customer'), $customer->fresh()->name);
        $this->assertSame(Customer::WALK_IN_NAME, DB::table('customers')->where('id', $customer->id)->value('name'));
        App::setLocale('en');
    }

    public function test_service_customer_label_handles_walk_in_regular_and_missing_customers(): void
    {
        $business = $this->business('Service Shop');
        $customer = Customer::forceCreate(['name' => 'Asha Rahman', 'businessId' => $business->id]);

        $walkIn = new ProvideService();
        $walkIn->forceFill(['businessId' => $business->id, 'customerName' => null]);
        $this->assertSame(Customer::walkInLabel(), $walkIn->customerLabel());

        $regular = new ProvideService();
        $regular->forceFill(['businessId' => $business->id, 'customerName' => $customer->id, 'customer_name' => 'Asha Rahman']);
        $this->assertSame('Asha Rahman', $regular->customerLabel());

        $missing = new ProvideService();
        $missing->forceFill(['businessId' => $business->id, 'customerName' => 987654]);
        $this->assertSame(__('common.customer').' #987654', $missing->customerLabel());
    }

    public function test_service_join_does_not_reveal_a_foreign_business_customer_name(): void
    {
        $ownBusiness = $this->business('Service Owner');
        $foreignBusiness = $this->business('Service Other');
        $actor = $this->actor((int) $ownBusiness->id);
        $foreignCustomer = Customer::forceCreate(['name' => 'Private Foreign Customer', 'businessId' => $foreignBusiness->id]);
        $this->assertSame(
            __('common.customer').' #'.$foreignCustomer->id,
            Customer::displayLabel($foreignCustomer, $foreignCustomer->id, $ownBusiness->id)
        );
        ProvideService::forceCreate([
            'businessId' => $ownBusiness->id,
            'customerName' => $foreignCustomer->id,
            'serviceName' => 'Repair',
            'amount' => 10,
        ]);

        $this->actingAs($actor, 'admin');
        $response = $this->get(route('serviceProvideList'))->assertOk();
        $response->assertDontSee('Private Foreign Customer');
        $response->assertSee(__('common.customer').' #'.$foreignCustomer->id);
    }

    public function test_quotation_and_invoice_labels_localize_walk_in_and_identify_deleted_customers(): void
    {
        $business = $this->business('Invoice Shop');
        $actor = $this->actor((int) $business->id);
        $actor->forceFill(['preferred_locale' => 'bn'])->save();
        $walkIn = Customer::forceCreate(['name' => Customer::WALK_IN_NAME, 'businessId' => null]);
        $deletedCustomer = Customer::forceCreate(['name' => 'Former Private Customer', 'businessId' => $business->id]);
        $deletedCustomer->delete();

        $walkInQuote = Quotation::forceCreate([
            'businessId' => $business->id,
            'quote_number' => 'Q-WALK-1',
            'customer_id' => $walkIn->id,
            'date' => '2026-10-24',
            'status' => 'draft',
        ]);
        $deletedQuote = Quotation::forceCreate([
            'businessId' => $business->id,
            'quote_number' => 'Q-DELETED-1',
            'customer_id' => $deletedCustomer->id,
            'date' => '2026-10-24',
            'status' => 'draft',
        ]);
        $walkInSale = $this->sale((int) $business->id, (int) $actor->id, (int) $walkIn->id, 'WALK-IN-INVOICE');
        $deletedSale = $this->sale((int) $business->id, (int) $actor->id, (int) $deletedCustomer->id, 'DELETED-CUSTOMER-INVOICE');

        $this->actingAs($actor, 'admin');
        App::setLocale('bn');

        $label = __('common.walking_customer');
        $this->withSession(['locale' => 'bn'])->get(route('quotation.show', $walkInQuote->id))->assertOk()->assertSee($label);
        $this->get(route('quotation.print', $walkInQuote->id))->assertOk()->assertSee($label);
        $this->get(route('invoicePage', ['id' => $walkInSale->id]))
            ->assertOk()->assertSee($label)->assertDontSee('class="signature-boxes"', false);
        $this->get(route('invoiceThermalPrint', ['id' => $walkInSale->id]))->assertOk()->assertSee($label);

        $missingLabel = __('common.customer').' #'.$deletedCustomer->id;
        $this->get(route('quotation.show', $deletedQuote->id))->assertOk()->assertSee($missingLabel)->assertDontSee('Former Private Customer');
        $this->get(route('invoicePage', ['id' => $deletedSale->id]))->assertOk()->assertSee($missingLabel)->assertDontSee('Former Private Customer');

        App::setLocale('en');
    }

    public function test_quotation_create_and_edit_render_responsive_form_contracts(): void
    {
        $business = $this->business('Quotation Mobile Shop');
        $actor = $this->actor((int) $business->id);
        Customer::forceCreate(['name' => 'Quote Customer', 'businessId' => $business->id]);
        Product::forceCreate(['name' => 'Quote Product', 'businessId' => $business->id]);
        $quote = Quotation::forceCreate([
            'businessId' => $business->id,
            'quote_number' => 'Q-MOBILE-1',
            'date' => '2026-10-24',
            'status' => 'draft',
        ]);
        $this->actingAs($actor, 'admin');

        $this->get(route('quotation.create'))->assertOk()
            ->assertSee('class="rn-m-form rn-m-quotation-form"', false)
            ->assertSee('rn-m-quotation-meta', false)
            ->assertSee('data-label="'.__('common.unit_price').'"', false)
            ->assertSee('rn-m-quotation-actions', false);
        $this->get(route('quotation.edit', $quote->id))->assertOk()
            ->assertSee('class="rn-m-form rn-m-quotation-form"', false)
            ->assertSee('rn-m-quotation-items', false)
            ->assertSee('rn-m-quotation-actions', false);
    }

    public function test_tenant_presence_validation_rejects_foreign_customers_and_suppliers(): void
    {
        $ownBusiness = $this->business('Tenant One');
        $foreignBusiness = $this->business('Tenant Two');
        $actor = $this->actor((int) $ownBusiness->id);
        $foreignCustomer = Customer::forceCreate(['name' => 'Foreign Customer', 'businessId' => $foreignBusiness->id]);
        $foreignSupplier = \App\Models\Supplier::forceCreate(['name' => 'Foreign Supplier', 'businessId' => $foreignBusiness->id]);

        $this->actingAs($actor, 'admin');
        $customerCheck = Validator::make(['record' => $foreignCustomer->id], ['record' => 'required|exists:customers,id']);
        $supplierCheck = Validator::make(['record' => $foreignSupplier->id], ['record' => 'required|exists:suppliers,id']);
        $this->assertTrue($customerCheck->fails());
        $this->assertTrue($supplierCheck->fails());
    }

    public function test_affected_routes_remain_registered_and_removed_supplier_route_was_dead(): void
    {
        $this->assertTrue(Route::has('serviceProvideList'));
        $this->assertTrue(Route::has('getServiceDetails'));
        $this->assertTrue(Route::has('quotation.show'));
        $this->assertTrue(Route::has('quotation.print'));
        $this->assertTrue(Route::has('invoicePage'));
        $this->assertTrue(Route::has('invoiceThermalPrint'));
        $this->assertFalse(Route::has('supplierbalancesheet'));
        $this->assertFalse(method_exists(\App\Http\Controllers\coustomerSupplier::class, 'supplierbalancesheet'));
    }

    private function business(string $name): BusinessSetup
    {
        return BusinessSetup::forceCreate(['businessName' => $name]);
    }

    private function actor(int $businessId): AdminUser
    {
        return AdminUser::forceCreate([
            'fullName' => 'Display Test Admin',
            'mail' => uniqid('display-', true).'@example.test',
            'password' => bcrypt('test-only'),
            'businessId' => $businessId,
            'role' => 'admin',
        ]);
    }

    private function sale(int $businessId, int $actorId, int $customerId, string $invoice): SaleProduct
    {
        $id = DB::table('sale_products')->insertGetId([
            'businessId' => $businessId,
            'salespersonId' => $actorId,
            'customerId' => $customerId,
            'date' => '2026-10-24',
            'invoice' => $invoice,
            'grandTotal' => '10.00',
            'paidAmount' => '0.00',
            'curDue' => '10.00',
            'status' => 'Ordered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return SaleProduct::withoutGlobalScopes()->findOrFail($id);
    }
}
