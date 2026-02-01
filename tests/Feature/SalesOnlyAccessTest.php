<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

class SalesOnlyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(string $role = 'salesmanager')
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

    public function test_sales_manager_can_access_sales_routes()
    {
        $user = $this->createAdmin('salesmanager');
        $this->actingAs($user, 'admin');

        $this->get(route('dashboard'))->assertStatus(200);
        $resp = $this->get(route('newsale'));
        $this->assertNotEquals(403, $resp->getStatusCode(), 'Blocked by SalesOnly');
        $location = $resp->headers->get('Location');
        $this->assertTrue($resp->getStatusCode() === 200 || ($resp->getStatusCode() === 302 && $location !== route('dashboard')));

        $resp2 = $this->get(route('saleList'));
        $this->assertNotEquals(403, $resp2->getStatusCode(), 'Blocked by SalesOnly');
        $location2 = $resp2->headers->get('Location');
        $this->assertTrue($resp2->getStatusCode() === 200 || ($resp2->getStatusCode() === 302 && $location2 !== route('dashboard')));

        $resp3 = $this->get(route('returnSaleList'));
        $this->assertNotEquals(403, $resp3->getStatusCode(), 'Blocked by SalesOnly');
        $location3 = $resp3->headers->get('Location');
        $this->assertTrue($resp3->getStatusCode() === 200 || ($resp3->getStatusCode() === 302 && $location3 !== route('dashboard')));
    }

    public function test_sales_manager_can_access_previously_non_sales_routes()
    {
        $user = $this->createAdmin('salesmanager');
        $this->actingAs($user, 'admin');

        // Product list should now be accessible
        $resp = $this->get(route('productlist'));
        $this->assertNotEquals(403, $resp->getStatusCode());
        $this->assertTrue($resp->getStatusCode() === 200 || $resp->getStatusCode() === 302);

        // Expense list should now be accessible
        $resp = $this->get(route('expense.list'));
        $this->assertNotEquals(403, $resp->getStatusCode());
        $this->assertTrue($resp->getStatusCode() === 200 || $resp->getStatusCode() === 302);

        // Documentation should now be accessible
        $resp = $this->get(route('documentation.index'));
        $this->assertNotEquals(403, $resp->getStatusCode());
        $this->assertTrue($resp->getStatusCode() === 200 || $resp->getStatusCode() === 302);
    }
}
