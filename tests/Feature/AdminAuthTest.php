<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AdminUser;
use App\Models\User;
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

        $sessionId = session()->getId();
        $response = $this->post(route('adminLogin'), [
            'userMail' => $email,
            'password' => $password,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertNotSame($sessionId, session()->getId());
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
            ->assertSee('আপনি সফলভাবে লগআউট করেছেন।')
            ->assertSee('lang="bn">বাংলা', false);
    }

    public function test_admin_logout_preserves_a_simultaneous_web_user_and_rotates_the_session(): void
    {
        $webUser = User::create([
            'name' => 'Teacher Session', 'email' => 'teacher@example.test', 'password' => 'test-password',
        ]);
        $admin = AdminUser::forceCreate([
            'fullName' => 'Retail Admin', 'mail' => 'retail-admin@example.test',
            'password' => Hash::make('test-password'), 'businessId' => null, 'role' => 'superadmin',
            'preferred_locale' => 'bn',
        ]);

        $this->actingAs($webUser, 'web')->actingAs($admin, 'admin')->withSession([
            'locale' => 'bn', 'pos' => $admin->id, 'pos_user_id' => $admin->id,
            'cultivationAdmin' => 'stale', 'url.intended' => '/old-destination',
        ]);
        $previousId = session()->getId();

        $this->get(route('logout'))->assertRedirect(route('userLogin'));

        $this->assertFalse(Auth::guard('admin')->check());
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertNotSame($previousId, session()->getId());
        $this->assertSame('bn', session('locale'));
        $this->assertNull(session('pos'));
        $this->assertNull(session('pos_user_id'));
        $this->assertNull(session('cultivationAdmin'));
        $this->assertNull(session('url.intended'));
        $this->get(route('userLogin'))->assertOk()->assertSee('আপনি সফলভাবে লগআউট করেছেন।');
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

    public function test_unknown_admin_username_checks_the_fixed_dummy_bcrypt_hash(): void
    {
        Hash::shouldReceive('check')->once()
            ->with('wrong-password', '$2y$12$J6fOuR7YC0Jygc.sMWaOsO5xaaL9XfovV4yWMw1erZ4rNkEsP91qW')
            ->andReturnFalse();

        $this->from(route('userLogin'))->post(route('adminLogin'), [
            'userMail' => 'missing-account@example.test', 'password' => 'wrong-password',
        ])->assertRedirect(route('userLogin'));
    }
}
