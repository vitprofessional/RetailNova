<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_seeded_credentials()
    {
        // create admin user
        $email = 'virtualitprofessional@gmail.com';
        $password = '11223344';

        AdminUser::create([
            'fullName' => 'Test Admin',
            'sureName' => 'Admin',
            'storeName' => 'RetailNova',
            'mail' => $email,
            'contactNumber' => '0000000000',
            'password' => Hash::make($password),
            'businessId' => 1,
            'role' => 'superadmin'
        ]);

        $response = $this->post(route('adminLogin'), [
            'userMail' => $email,
            'password' => $password,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::guard('admin')->check());
    }
}
