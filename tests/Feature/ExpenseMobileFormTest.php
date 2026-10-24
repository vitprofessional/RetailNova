<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseMobileFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_edit_expense_pages_use_shared_mobile_form_actions(): void
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Expense Mobile Shop']);
        $actor = AdminUser::forceCreate([
            'fullName' => 'Expense Mobile Admin',
            'mail' => 'expense-mobile@example.test',
            'password' => bcrypt('test-only'),
            'businessId' => $business->id,
            'role' => 'admin',
        ]);
        $category = ExpenseCategory::forceCreate([
            'businessId' => $business->id,
            'name' => 'Utilities',
            'is_active' => true,
        ]);
        $expense = ExpenseEntry::forceCreate([
            'businessId' => $business->id,
            'expense_date' => '2026-10-24',
            'category_id' => $category->id,
            'amount' => 125,
            'payment_method' => 'cash',
            'created_by' => $actor->id,
            'created_by_type' => 'admin',
        ]);
        $this->actingAs($actor, 'admin');
        $mobile = $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile');

        $mobile->get(route('expense.create'))->assertOk()
            ->assertSee('class="rn-m-form rn-m-expense-form"', false)
            ->assertSee('rn-m-expense-fields', false)
            ->assertSee('rn-m-expense-actions', false)
            ->assertSee('name="receipt_file"', false);
        $mobile->get(route('expense.edit', $expense->id))->assertOk()
            ->assertSee('class="rn-m-form rn-m-expense-form"', false)
            ->assertSee('rn-m-expense-actions', false)
            ->assertSee('name="payment_method"', false);
    }
}
