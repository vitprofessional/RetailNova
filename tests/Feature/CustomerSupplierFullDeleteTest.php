<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Models\PurchaseProduct;
use App\Models\SaleProduct;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSaleFixtures;
use Tests\TestCase;

/**
 * "Delete all data" of a customer or supplier permanently removes its sales or purchases,
 * including the history of cancelled sales, so only admins and superadmins may use it.
 */
class CustomerSupplierFullDeleteTest extends TestCase
{
    use BuildsSaleFixtures;
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    // Never migrate or seed a configured development/production database.
    public function refreshDatabase()
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Full-delete tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    /** A customer with one sale and the supplier of the sold lot, created by an admin. */
    private function customerWithSale(): array
    {
        $inventory = $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $customer = Customer::create(['name' => 'Wipe Customer', 'businessId' => 1]);
        $sale = $this->sell($inventory, 2, false, $customer);

        return [$customer, $sale, $inventory['supplier'], $inventory['purchase']];
    }

    public function test_non_admin_roles_are_refused_delete_all_data_for_customers_and_suppliers(): void
    {
        [$customer, $sale, $supplier, $purchase] = $this->customerWithSale();

        foreach (['gm', 'salesmanager'] as $role) {
            $this->actingAs($this->actor($role), 'admin');
            $this->delete(route('delCustomer', $customer->id), ['deleteType' => 'fullDelete'])->assertForbidden();
            $this->delete(route('customers.bulkDelete'), ['deleteType' => 'fullDelete', 'selected' => [$customer->id]])->assertForbidden();
            $this->delete(route('delSupplier', $supplier->id), ['deleteType' => 'fullDelete'])->assertForbidden();
            $this->delete(route('suppliers.bulkDelete'), ['deleteType' => 'fullDelete', 'selected' => [$supplier->id]])->assertForbidden();
        }

        $this->assertFalse(Customer::withTrashed()->findOrFail($customer->id)->trashed());
        $this->assertFalse(Supplier::withTrashed()->findOrFail($supplier->id)->trashed());
        $this->assertNotNull(SaleProduct::find($sale->id));
        $this->assertNotNull(PurchaseProduct::find($purchase->id));
        $this->assertSame(1, InvoiceItem::where('saleId', $sale->id)->count());
    }

    public function test_profile_only_delete_is_unchanged_for_non_admins(): void
    {
        [$customer, $sale] = $this->customerWithSale();

        $this->actingAs($this->actor('gm'), 'admin');
        $this->delete(route('delCustomer', $customer->id), ['deleteType' => 'profileOnly'])->assertRedirect();

        $this->assertTrue(Customer::withTrashed()->findOrFail($customer->id)->trashed());
        $this->assertNotNull(SaleProduct::find($sale->id), 'Profile-only delete keeps the sales.');
    }

    public function test_admin_delete_all_data_permanently_removes_sales_including_cancelled_history(): void
    {
        [$customer, $sale] = $this->customerWithSale();
        $cancelled = $this->sell($this->inventory(), 1, false, $customer);
        $this->delete(route('delSale', $cancelled->id), ['reason' => 'Entered twice'])->assertSessionHasNoErrors();
        $this->assertTrue(SaleProduct::withTrashed()->findOrFail($cancelled->id)->trashed());

        $this->delete(route('delCustomer', $customer->id), ['deleteType' => 'fullDelete'])->assertRedirect();

        $this->assertNull(Customer::withTrashed()->find($customer->id));
        $this->assertSame(0, SaleProduct::withTrashed()->whereIn('id', [$sale->id, $cancelled->id])->count());
        $this->assertSame(0, InvoiceItem::withTrashed()->whereIn('saleId', [$sale->id, $cancelled->id])->count());
    }

    public function test_delete_all_data_controls_and_permanent_warning_are_admin_only(): void
    {
        $admin = $this->actor('admin');
        $this->inventory();
        Customer::create(['name' => 'Listed Customer', 'businessId' => 1]);
        $this->actingAs($admin, 'admin');

        foreach (['en', 'bn'] as $locale) {
            $admin->update(['preferred_locale' => $locale]);
            foreach (['addCustomer' => 'customers', 'addSupplier' => 'suppliers'] as $page => $group) {
                $this->get(route($page))->assertOk()
                    ->assertSee('lang="'.$locale.'"', false)
                    ->assertSee("submitDelete('fullDelete')", false)
                    ->assertSee('data-full-delete="allowed"', false)
                    ->assertSee(__($group.'.delete_profile_and_all_related_transaction_data'))
                    ->assertSee(__($group.'.delete_all_data_confirm'));
            }
        }
        $this->assertStringContainsString('বাতিল বিক্রির ইতিহাস', __('customers.delete_profile_and_all_related_transaction_data', [], 'bn'));
        $this->assertStringContainsString('cancelled sales', __('suppliers.delete_profile_and_all_related_transaction_data', [], 'en'));

        $this->actingAs($this->actor('gm'), 'admin');
        foreach (['addCustomer' => 'customers', 'addSupplier' => 'suppliers'] as $page => $group) {
            $this->get(route($page))->assertOk()
                ->assertDontSee("submitDelete('fullDelete')", false)
                ->assertSee('data-full-delete="denied"', false)
                ->assertDontSee(__($group.'.delete_profile_and_all_related_transaction_data'));
        }
    }
}
