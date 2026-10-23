<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** First-run bootstrap coverage lives in FirstRunSetupTest; this file covers settings scope and sign-in. */
class AuthenticationSetupHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function business(int $id, string $name = 'Business'): BusinessSetup
    {
        return BusinessSetup::forceCreate(['id' => $id, 'businessName' => $name]);
    }

    private function actor(string $role, ?int $businessId = null): AdminUser
    {
        return AdminUser::forceCreate([
            'fullName' => 'Security Test', 'mail' => uniqid('admin-', true).'@example.test',
            'password' => Hash::make('test-secret'), 'role' => $role, 'businessId' => $businessId,
        ]);
    }

    public function test_platform_configuration_routes_require_explicit_role_and_exact_business_scope(): void
    {
        $this->business(1, 'One');
        $this->business(2, 'Two');

        $admin = $this->actor('admin', 1);
        $this->actingAs($admin, 'admin');
        $this->get(route('addBusinessSetupPage'))->assertOk()->assertDontSee('name="hideAckWalkin"', false);
        $this->post(route('saveBusiness'), ['businessId' => 1, 'businessName' => 'Updated One'])
            ->assertRedirect();
        $this->assertSame('Updated One', BusinessSetup::find(1)->businessName);
        $this->postJson(route('saveBusiness'), [
            'businessId' => 1, 'businessName' => 'Updated One', 'hideAckWalkin' => 1,
        ])->assertForbidden();
        $this->getJson(route('addBusinessSetupPage', ['businessId' => 2]))->assertForbidden();
        $this->postJson(route('saveBusiness'), ['businessId' => 2, 'businessName' => 'Spoofed'])
            ->assertForbidden();
        $this->postJson(route('saveBusinessLogo'), ['businessId' => 2])->assertForbidden();
        $this->deleteJson(route('delBusinessLogo', ['id' => 2]))->assertForbidden();
        $this->assertSame('Two', BusinessSetup::find(2)->businessName);

        $platform = $this->actor('superadmin', 1);
        $this->actingAs($platform, 'admin');
        $this->get(route('addBusinessSetupPage', ['businessId' => 2]))->assertOk();
    }

    public function test_sales_manager_cannot_write_business_configuration(): void
    {
        $this->business(1, 'One');
        $this->actingAs($this->actor('salesmanager', 1), 'admin');

        $this->get(route('addBusinessSetupPage'))->assertOk();
        $this->postJson(route('saveBusiness'), ['businessId' => 1, 'businessName' => 'Spoofed'])
            ->assertForbidden();
        $this->assertSame('One', BusinessSetup::find(1)->businessName);
    }

    public function test_admin_login_is_throttled_without_changing_successful_login_behavior(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('adminLogin'), [
                'userMail' => 'not-an-account@example.test', 'password' => 'incorrect',
            ])->assertRedirect()->assertSessionHas('login_notice', [
                'type' => 'error',
                'key' => 'invalid_credentials',
            ]);
        }

        $this->post(route('adminLogin'), [
            'userMail' => 'not-an-account@example.test', 'password' => 'incorrect',
        ])->assertTooManyRequests();
    }
}
