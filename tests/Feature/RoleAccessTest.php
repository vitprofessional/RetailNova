<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(string $role, int $bizId = 1)
    {
        return AdminUser::create([
            'fullName' => ucfirst($role).' User',
            'sureName' => $role,
            'storeName' => 'RetailNova',
            'mail' => $role.'@example.com',
            'contactNumber' => '0000000000',
            'password' => Hash::make('password123'),
            'businessId' => $bizId,
            'role' => $role,
        ]);
    }

    public function test_superadmin_can_access_super_routes()
    {
        $user = $this->createAdmin('superadmin');
        $this->actingAs($user, 'admin');
        $this->get(route('admin.super.users.index'))->assertStatus(200);
    }

    public function test_admin_cannot_access_super_routes()
    {
        $user = $this->createAdmin('admin');
        $this->actingAs($user, 'admin');
        $this->get(route('admin.super.users.index'))->assertRedirect(route('dashboard'));
    }

    public function test_gm_can_access_manage_users_and_cannot_create_admin()
    {
        $gm = $this->createAdmin('gm', bizId: 2);
        $this->actingAs($gm, 'admin');
        $this->get(route('admin.manage.users.index'))->assertStatus(200);

        // Attempt to create Admin should fail validation (role not permitted)
        $resp = $this->post(route('admin.manage.users.store'), [
            'fullName' => 'Not Allowed',
            'mail' => 'newadmin@example.com',
            'role' => 'admin',
            'password' => 'secret123',
            'businessId' => 2,
        ]);
        $resp->assertSessionHasErrors(['role']);
    }

    public function test_store_manager_can_only_create_sales_manager()
    {
        $sm = $this->createAdmin('storemanager', bizId: 3);
        $this->actingAs($sm, 'admin');
        $this->get(route('admin.manage.users.index'))->assertStatus(200);

        // Creating Sales Manager should pass (redirect back success)
        $ok = $this->post(route('admin.manage.users.store'), [
            'fullName' => 'Sales Person',
            'mail' => 'sales.new@example.com',
            'role' => 'salesmanager',
            'password' => 'secret123',
            'businessId' => 3,
        ]);
        $ok->assertStatus(302);

        // Creating GM should fail validation
        $fail = $this->post(route('admin.manage.users.store'), [
            'fullName' => 'GM Person',
            'mail' => 'gm.new@example.com',
            'role' => 'gm',
            'password' => 'secret123',
            'businessId' => 3,
        ]);
        $fail->assertSessionHasErrors(['role']);
    }
}
