<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\SaleProduct;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSaleFixtures;
use Tests\TestCase;

class Ui30DesktopFixesTest extends TestCase
{
    use BuildsSaleFixtures;
    use RefreshDatabase;

    public function test_expense_creator_uses_the_table_recorded_on_the_expense(): void
    {
        $this->inventory(1);
        $admin = AdminUser::create([
            'fullName' => 'Expense Admin Creator', 'mail' => 'expense-admin@example.test',
            'password' => 'test', 'businessId' => 1, 'role' => 'admin',
        ]);
        $category = ExpenseCategory::forceCreate(['name' => 'Rent', 'businessId' => 1, 'is_active' => true]);

        $adminExpense = ExpenseEntry::forceCreate([
            'businessId' => 1, 'expense_date' => now()->toDateString(), 'category_id' => $category->id,
            'amount' => 100, 'payment_method' => 'cash', 'created_by' => $admin->id, 'created_by_type' => 'admin',
        ]);
        $this->assertSame('Expense Admin Creator', $adminExpense->creatorLabel());

        $user = User::create(['name' => 'Expense User Creator', 'email' => 'expense-user@example.test', 'password' => 'test']);
        $userExpense = ExpenseEntry::forceCreate([
            'businessId' => 1, 'expense_date' => now()->toDateString(), 'category_id' => $category->id,
            'amount' => 100, 'payment_method' => 'cash', 'created_by' => $user->id, 'created_by_type' => 'user',
        ]);
        $this->assertSame('Expense User Creator', $userExpense->creatorLabel());
        $otherBusinessAdmin = AdminUser::create([
            'fullName' => 'Foreign Expense Admin', 'mail' => 'expense-foreign@example.test',
            'password' => 'test', 'businessId' => 2, 'role' => 'admin',
        ]);
        $this->assertSame('—', ExpenseEntry::forceCreate([
            'businessId' => 1, 'expense_date' => now()->toDateString(), 'category_id' => $category->id,
            'amount' => 100, 'payment_method' => 'cash', 'created_by' => $otherBusinessAdmin->id, 'created_by_type' => 'admin',
        ])->creatorLabel());
    }

    public function test_sale_edit_exposes_and_persists_the_supported_payment_method(): void
    {
        $inventory = $this->inventory(1);
        $actor = $this->actor('admin', 1);
        $this->actingAs($actor, 'admin');
        $sale = $this->sell($inventory, 1);

        $this->get(route('sale.edit', ['id' => $sale->id]))
            ->assertOk()->assertSee('name="payment_method"', false)->assertSee('value="nagad"', false);

        $this->post(route('sale.update', ['id' => $sale->id]), [
            'paidAmount' => 100, 'payment_method' => 'nagad', 'note' => 'edited payment type',
        ])->assertRedirect(route('saleList'));

        $this->assertSame('nagad', $sale->fresh()->payment_method);
        $this->assertEquals(50.0, (float) $sale->fresh()->curDue);
    }

    public function test_mobile_sale_edit_uses_shared_form_components_without_changing_payment_fields(): void
    {
        $inventory = $this->inventory(1);
        $actor = $this->actor('admin', 1);
        $this->actingAs($actor, 'admin');
        $sale = $this->sell($inventory, 1, false, null, ['payment_method' => 'bkash']);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile')
            ->get(route('sale.edit', ['id' => $sale->id]))
            ->assertOk()
            ->assertSee('rn-m-sale-edit-page', false)
            ->assertSee('class="rn-m-form rn-m-sale-edit-form"', false)
            ->assertSee('name="paidAmount"', false)
            ->assertSee('name="date"', false)
            ->assertSee('name="payment_method"', false)
            ->assertSee('name="note"', false)
            ->assertSee('rn-m-sale-edit-actions', false)
            ->assertSee('value="bkash" selected', false)
            ->assertSee(route('sale.items.edit', ['id' => $sale->id]));
    }

    public function test_desktop_sale_cancel_form_skips_generic_confirmation_for_reason_prompt(): void
    {
        $inventory = $this->inventory(1);
        $actor = $this->actor('admin', 1);
        $this->actingAs($actor, 'admin');
        $this->sell($inventory, 1);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)')
            ->get(route('saleList'))
            ->assertOk()
            ->assertSee('data-cancel-sale-form data-confirm="false"', false);
    }

    public function test_service_details_endpoint_returns_only_services_in_the_active_business(): void
    {
        $this->inventory(1);
        $this->inventory(2);
        $own = Service::forceCreate(['businessId' => 1, 'serviceName' => 'Own repair', 'rate' => 45]);
        $foreign = Service::forceCreate(['businessId' => 2, 'serviceName' => 'Private repair', 'rate' => 99]);
        $this->actingAs($this->actor('admin', 1), 'admin');

        $this->get(route('getServiceDetails', $own->id))->assertOk()->assertJsonPath('serviceName', 'Own repair');
        $this->get(route('getServiceDetails', $foreign->id))->assertNotFound();
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('getServiceDetails'));
    }

    public function test_walk_in_label_is_used_in_desktop_and_mobile_sales_lists(): void
    {
        $this->inventory(1);
        $actor = $this->actor('admin', 1);
        $walkIn = Customer::forceCreate(['name' => Customer::WALK_IN_NAME, 'businessId' => null]);
        SaleProduct::forceCreate([
            'invoice' => 'UI30-WALK-IN', 'customerId' => $walkIn->id, 'businessId' => 1,
            'salespersonId' => $actor->id, 'date' => now()->toDateString(), 'grandTotal' => 20,
            'paidAmount' => 20, 'curDue' => 0, 'payment_method' => 'cash',
        ]);
        $this->actingAs($actor, 'admin');
        $label = Customer::walkInLabel();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)')
            ->get(route('saleList'))->assertOk()->assertSee($label);
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile')
            ->get(route('saleList'))->assertOk()->assertSee($label);
    }
}
