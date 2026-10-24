<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AdminUser;
use App\Models\BusinessSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountMobileFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_create_edit_and_transaction_forms_use_shared_mobile_patterns(): void
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Account Mobile Shop']);
        $actor = AdminUser::forceCreate([
            'fullName' => 'Account Mobile Admin',
            'mail' => 'account-mobile@example.test',
            'password' => bcrypt('test-only'),
            'businessId' => $business->id,
            'role' => 'admin',
        ]);
        $account = Account::forceCreate([
            'businessId' => $business->id,
            'account_code' => 'MOB-100',
            'account_name' => 'Mobile Cash Account',
            'account_type' => 'asset',
            'opening_balance' => 25,
            'current_balance' => 25,
        ]);

        $browser = $this->actingAs($actor, 'admin')
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile');

        $browser->get(route('account.create'))->assertOk()
            ->assertSee('class="rn-m-form rn-m-account-form"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="account_code"', false)
            ->assertSee('name="account_name"', false)
            ->assertSee('name="account_type"', false)
            ->assertSee('name="opening_balance"', false)
            ->assertSee('name="business_location_id"', false)
            ->assertSee('rn-m-sticky-actions rn-m-account-actions', false)
            ->assertSee(route('account.store'), false);

        $browser->get(route('account.edit', $account->id))->assertOk()
            ->assertSee('name="account_code"', false)
            ->assertSee('value="MOB-100"', false)
            ->assertSee('name="account_name"', false)
            ->assertSee('value="Mobile Cash Account"', false)
            ->assertSee('name="is_active"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('rn-m-sticky-actions rn-m-account-actions', false);

        $browser->get(route('account.transactions.create'))->assertOk()
            ->assertSee('class="rn-m-form rn-m-account-form rn-m-account-transaction-form"', false)
            ->assertSee('name="transaction_date"', false)
            ->assertSee('name="transaction_type"', false)
            ->assertSee('name="reference_no"', false)
            ->assertSee('name="amount"', false)
            ->assertSee('name="debit_account_id"', false)
            ->assertSee('name="credit_account_id"', false)
            ->assertSee('name="_token"', false)
            ->assertSee(route('account.transactions.store'), false)
            ->assertSee('rn-m-sticky-actions rn-m-account-actions', false);
    }
}
