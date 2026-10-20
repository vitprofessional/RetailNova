<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_the_authenticated_business_currency_for_all_monetary_views(): void
    {
        BusinessSetup::forceCreate([
            'id' => 1,
            'businessName' => 'Currency Test Business',
            'currencySymbol' => 'BDT-T',
            'currencyPosition' => 'right',
            'currencyNegParentheses' => true,
        ]);
        $user = AdminUser::create([
            'fullName' => 'Currency Tester',
            'sureName' => 'Test',
            'storeName' => 'Currency Test',
            'mail' => 'dashboard-currency@example.test',
            'contactNumber' => '000',
            'password' => bcrypt('test-secret'),
            'role' => 'gm',
            'businessId' => 1,
        ]);

        $this->actingAs($user, 'admin')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-currency-symbol="BDT-T"', false)
            ->assertSee('data-currency-position="right"', false)
            ->assertSee('data-rn-money="0"', false)
            ->assertSee('fmtMoney(context.parsed.y)', false);
    }
}
