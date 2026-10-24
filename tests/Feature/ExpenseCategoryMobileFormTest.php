<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryMobileFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_expense_category_create_and_edit_keep_existing_fields_and_values(): void
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Expense Category Mobile Shop']);
        $actor = AdminUser::forceCreate([
            'fullName' => 'Expense Category Admin',
            'mail' => 'expense-category-mobile@example.test',
            'password' => bcrypt('test-only'),
            'businessId' => $business->id,
            'role' => 'admin',
        ]);
        $category = ExpenseCategory::forceCreate([
            'businessId' => $business->id,
            'name' => 'Mobile Utilities',
            'description' => 'Monthly utilities',
            'is_active' => true,
        ]);

        $browser = $this->actingAs($actor, 'admin')
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile');

        $browser->get(route('expense.categories.create'))->assertOk()
            ->assertSee('class="rn-m-form rn-m-expense-category-form"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="description"', false)
            ->assertSee(route('expense.categories.store'), false)
            ->assertSee('rn-m-sticky-actions rn-m-expense-category-actions', false);

        $browser->get(route('expense.categories.edit', $category->id))->assertOk()
            ->assertSee('value="Mobile Utilities"', false)
            ->assertSee('Monthly utilities', false)
            ->assertSee('name="is_active"', false)
            ->assertSee('name="_token"', false)
            ->assertSee(route('expense.categories.update', $category->id), false)
            ->assertSee('rn-m-sticky-actions rn-m-expense-category-actions', false);
    }
}
