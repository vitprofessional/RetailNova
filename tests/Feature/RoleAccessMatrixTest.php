<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

class RoleAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(string $role = 'admin'): AdminUser
    {
        return AdminUser::create([
            'fullName' => ucfirst($role).' User',
            'sureName' => $role,
            'storeName' => 'RetailNova',
            'mail' => $role.'@example.com',
            'contactNumber' => '0000000000',
            'password' => Hash::make('password123'),
            'businessId' => 1,
            'role' => $role,
        ]);
    }

    public function test_super_admin_access_panel_and_audits()
    {
        $user = $this->createAdmin('superadmin');
        $this->actingAs($user, 'admin');

        // Super Admin panel
        $this->get(route('admin.super.users.index'))->assertStatus(200);
        // Audits gate allows
        $this->get(route('audits.index'))->assertStatus(200);
    }

    public function test_admin_cannot_access_super_admin_panel_but_can_view_audits()
    {
        $user = $this->createAdmin('admin');
        $this->actingAs($user, 'admin');

        // Super Admin panel redirects to dashboard
        $this->get(route('admin.super.users.index'))->assertRedirect(route('dashboard'));
        // Audits allowed
        $this->get(route('audits.index'))->assertStatus(200);
    }

    public function test_gm_can_manage_users_and_access_business_modules()
    {
        $user = $this->createAdmin('gm');
        $this->actingAs($user, 'admin');

        // Manage users index accessible
        $this->get(route('admin.manage.users.index'))->assertStatus(200);
        // Products list accessible
        $this->get(route('productlist'))->assertStatus(200);
        // Purchases list accessible
        $this->get(route('purchaseList'))->assertStatus(200);
        // Accounts allowed
        $this->get(route('account.chart'))->assertStatus(200);
        // Expenses allowed
        $this->get(route('expense.list'))->assertStatus(200);
        // Business settings allowed
        $this->get(route('addBusinessSetupPage'))->assertStatus(200);
        // Audits denied for GM
        $this->get(route('audits.index'))->assertStatus(403);
    }

    public function test_store_manager_has_restrictions_but_can_access_core_modules()
    {
        $user = $this->createAdmin('storemanager');
        $this->actingAs($user, 'admin');

        // Manage users index accessible (controller restricts scope)
        $this->get(route('admin.manage.users.index'))->assertStatus(200);
        // Products and Purchases accessible
        $this->get(route('productlist'))->assertStatus(200);
        $this->get(route('purchaseList'))->assertStatus(200);
        // Accounts restricted
        $this->get(route('account.chart'))->assertStatus(403);
        // Expenses restricted
        $this->get(route('expense.list'))->assertStatus(403);
        // Business settings restricted
        $this->get(route('addBusinessSetupPage'))->assertStatus(403);
        // Audits restricted
        $this->get(route('audits.index'))->assertStatus(403);
    }

    public function test_sales_manager_unrestricted_except_super_admin_and_audits()
    {
        $user = $this->createAdmin('salesmanager');
        $this->actingAs($user, 'admin');

        // Products, Purchases, Accounts, Expenses, Business Settings accessible
        $this->get(route('productlist'))->assertStatus(200);
        $this->get(route('purchaseList'))->assertStatus(200);
        $this->get(route('account.chart'))->assertStatus(200);
        $this->get(route('expense.list'))->assertStatus(200);
        $this->get(route('addBusinessSetupPage'))->assertStatus(200);
        // Super Admin panel denied (redirect)
        $this->get(route('admin.super.users.index'))->assertRedirect(route('dashboard'));
        // Audits restricted
        $this->get(route('audits.index'))->assertStatus(403);
    }
}
