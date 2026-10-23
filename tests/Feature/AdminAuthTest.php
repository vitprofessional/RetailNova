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

    public function test_logout_preserves_the_active_locale_and_renders_a_translatable_notice(): void
    {
        $admin = AdminUser::create([
            'fullName' => 'Bangla Admin',
            'mail' => 'bangla-admin@example.test',
            'password' => Hash::make('test-password'),
            'businessId' => null,
            'role' => 'superadmin',
            'preferred_locale' => 'bn',
        ]);

        $this->actingAs($admin, 'admin')
            ->from(route('userLogin'))
            ->get(route('logout'))
            ->assertRedirect(route('userLogin'));

        $this->assertFalse(Auth::guard('admin')->check());
        $this->get(route('userLogin'))
            ->assertOk()
            ->assertSee('লগআউট সফল হয়েছে')
            ->assertSee('lang="bn">বাংলা', false);
    }

    public function test_invalid_login_stays_generic_and_is_translated_for_the_guest_locale(): void
    {
        $this->withSession(['locale' => 'bn'])
            ->from(route('userLogin'))
            ->post(route('adminLogin'), [
                'userMail' => 'unknown@example.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('userLogin'));

        $this->get(route('userLogin'))
            ->assertOk()
            ->assertSee(__('common.notification_invalid_credentials', [], 'bn'))
            ->assertDontSee('unknown@example.test');
    }
}
