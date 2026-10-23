<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_login_page_uses_product_branding_and_the_real_csrf_protected_login_form(): void
    {
        BusinessSetup::create(['businessName' => 'Tenant Shop Branding', 'default_locale' => 'en']);

        $response = $this->get(route('userLogin'))
            ->assertOk()
            ->assertSee('RetailNova')
            ->assertSee(asset('logo.png'))
            ->assertSee(asset('images/login/retailnova-desktop-scene.svg'))
            ->assertSee('rn-card-brand', false)
            ->assertSee('rn-login-benefits', false)
            ->assertSee(__('authentication.benefit_secure'))
            ->assertSee(__('authentication.benefit_sales'))
            ->assertSee(__('authentication.benefit_inventory'))
            ->assertSee(__('authentication.benefit_reports'))
            ->assertSee('action="'.route('adminLogin').'"', false)
            ->assertSee('name="userMail"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('rn-password-toggle', false)
            // A business already exists, so first-run setup is closed and not offered.
            ->assertDontSee(route('superadmin.setup.show'), false)
            ->assertDontSee('Tenant Shop Branding')
            ->assertDontSee(route('creatAdmin'), false)
            ->assertDontSee('Google', false)
            ->assertDontSee('Forgot password', false)
            ->assertDontSee('Remember me', false);

        $page = $response->getContent();
        $this->assertMatchesRegularExpression('/<ul class="rn-capabilities"[^>]*>(.*?)<\/ul>/s', $page);
        preg_match('/<ul class="rn-capabilities"[^>]*>(.*?)<\/ul>/s', $page, $capabilityList);
        foreach ([
            __('authentication.feature_sales'),
            __('authentication.feature_inventory'),
            __('authentication.feature_customers'),
            __('authentication.feature_reports'),
        ] as $featureLabel) {
            $this->assertSame(1, substr_count($capabilityList[1], $featureLabel), 'Each capability should appear once in the capability list.');
        }

        $this->assertSame('en', app()->getLocale());
        $sceneAsset = file_get_contents(public_path('images/login/retailnova-desktop-scene.svg'));
        $sourceArtwork = file_get_contents(public_path('images/login/retailnova-pos-premium.webp'));
        $this->assertNotFalse($sceneAsset);
        $this->assertNotFalse($sourceArtwork);
        $this->assertStringContainsString('data:image/webp;base64,'.base64_encode($sourceArtwork), $sceneAsset);
    }

    public function test_login_does_not_guess_a_business_logo_from_the_latest_business_record(): void
    {
        BusinessSetup::create([
            'businessName' => 'Northwind Retail',
            'businessLogo' => 'northwind-logo.png',
            'default_locale' => 'en',
        ]);
        BusinessSetup::create([
            'businessName' => 'Southport Traders',
            'businessLogo' => 'southport-logo.png',
            'default_locale' => 'en',
        ]);

        $this->get(route('userLogin'))
            ->assertOk()
            ->assertSee('class="rn-card-brand"', false)
            ->assertSee('src="'.asset('logo.png').'"', false)
            ->assertDontSee('Northwind Retail')
            ->assertDontSee('Southport Traders')
            ->assertDontSee('northwind-logo.png')
            ->assertDontSee('southport-logo.png');
    }

    public function test_guest_language_switch_is_session_scoped_and_supports_english_and_bangla(): void
    {
        $this->get(route('userLogin'))
            ->assertOk()
            ->assertSee('Welcome Back')
            ->assertSee('data-language-picker', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('name="locale" value="en"', false)
            ->assertSee('name="locale" value="bn"', false)
            ->assertSee('lang="en"', false)
            ->assertSee('aria-current="true"', false)
            ->assertSee('class="is-selected"', false);

        $this->from(route('userLogin'))->post(route('locale.guest'), ['locale' => 'bn'])
            ->assertRedirect(route('userLogin'));
        $this->get(route('userLogin'))
            ->assertOk()
            ->assertSee('স্বাগতম')
            ->assertSee('পাসওয়ার্ড')
            ->assertSee('lang="bn"', false)
            ->assertSee('aria-current="true"', false)
            ->assertSee('class="is-selected"', false);

        $this->from(route('userLogin'))->post(route('locale.guest'), ['locale' => 'en'])
            ->assertRedirect(route('userLogin'));
        $this->get(route('userLogin'))->assertOk()->assertSee('Welcome Back')->assertSee('Password');

        $this->postJson(route('locale.guest'), ['locale' => 'fr'])->assertUnprocessable();
        $this->from('https://attacker.example/')->post(route('locale.guest'), ['locale' => 'en'])
            ->assertRedirect(route('userLogin'));
    }

    public function test_guest_language_endpoint_does_not_change_authenticated_preferences(): void
    {
        $admin = AdminUser::create([
            'fullName' => 'Locale Admin',
            'mail' => 'locale-admin@example.test',
            'password' => Hash::make('test-password'),
            'businessId' => null,
            'role' => 'superadmin',
            'preferred_locale' => 'en',
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('locale.guest'), ['locale' => 'bn'])
            ->assertForbidden();

        $this->assertSame('en', $admin->fresh()->preferred_locale);
        $this->get(route('userLogin'))->assertOk()->assertDontSee('data-language-picker', false);
    }

    public function test_login_and_one_time_admin_bootstrap_are_separate_screens(): void
    {
        $this->get(route('userLogin'))
            ->assertOk()
            ->assertSee('action="'.route('adminLogin').'"', false)
            ->assertSee(route('superadmin.setup.show'), false)
            ->assertDontSee('action="'.route('creatAdmin').'"', false);

        $this->get(route('superadmin.setup.show'))
            ->assertOk()
            ->assertSee('action="'.route('superadmin.setup.store').'"', false);

        AdminUser::create([
            'fullName' => 'Existing Admin',
            'mail' => 'existing-admin@example.test',
            'password' => Hash::make('test-password'),
            'role' => 'superadmin',
        ]);

        $this->get(route('userLogin'))
            ->assertOk()
            ->assertSee('action="'.route('adminLogin').'"', false)
            ->assertDontSee(route('creatAdmin'), false)
            ->assertDontSee(route('superadmin.setup.show'), false)
            ->assertDontSee('Create an account', false);
        $this->get(route('superadmin.setup.show'))->assertRedirect(route('userLogin'));
    }

    public function test_legacy_literal_login_flash_messages_remain_escaped(): void
    {
        $this->withSession(['success' => '<script>alert("x")</script>'])
            ->get(route('userLogin'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("x")</script>', false);
    }

    public function test_login_notice_translation_uses_the_active_guest_locale(): void
    {
        $this->withSession([
            'locale' => 'bn',
            'login_notice' => ['type' => 'success', 'key' => 'logout_successful'],
        ])->get(route('userLogin'))
            ->assertOk()
            ->assertSee(__('authentication.logout_successful', [], 'bn'));
    }
}
