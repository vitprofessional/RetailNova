<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Services\BootstrapException;
use App\Services\FirstAdminBootstrap;
use Illuminate\Cache\ArrayStore;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FirstRunSetupTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'businessName' => 'First Light Traders',
            'fullName' => 'First Owner', 'sureName' => 'Owner', 'storeName' => 'Front Counter',
            'mail' => 'first-owner@example.test', 'contactNumber' => '01700000000',
            'password' => 'correct-horse-9', 'password_confirmation' => 'correct-horse-9',
        ], $overrides);
    }

    private function assertNothingProvisioned(): void
    {
        $this->assertSame(0, AdminUser::count());
        $this->assertSame(0, BusinessSetup::count());
    }

    public function test_combined_bootstrap_creates_the_business_and_its_linked_superadmin(): void
    {
        $this->withSession(['locale' => 'bn'])->get(route('superadmin.setup.show'))->assertOk();
        $sessionBeforeSetup = session()->getId();

        $this->post(route('superadmin.setup.store'), $this->payload())->assertRedirect(route('dashboard'));

        $business = BusinessSetup::sole();
        $admin = AdminUser::sole();
        $this->assertSame('First Light Traders', $business->businessName);
        $this->assertSame('bn', $business->default_locale, 'The guest language becomes the business default.');
        $this->assertSame('superadmin', $admin->role);
        $this->assertSame((string) $business->id, (string) $admin->businessId);
        $this->assertSame('first-owner@example.test', $admin->mail);
        $this->assertTrue(Hash::check('correct-horse-9', $admin->password));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertSame((string) $admin->id, (string) session('pos'));
        $this->assertSame((string) $admin->id, (string) session('pos_user_id'));
        $this->assertNotSame($sessionBeforeSetup, session()->getId(), 'Successful bootstrap must regenerate the session ID.');
    }

    public function test_missing_or_unsupported_guest_locale_falls_back_to_english(): void
    {
        $this->withSession(['locale' => 'fr'])
            ->post(route('superadmin.setup.store'), $this->payload())
            ->assertRedirect(route('dashboard'));

        $this->assertSame('en', BusinessSetup::sole()->default_locale);
    }

    public function test_submitted_role_and_business_id_are_ignored(): void
    {
        $this->post(route('superadmin.setup.store'), $this->payload([
            'role' => 'salesmanager', 'businessId' => 999, 'default_locale' => 'bn',
        ]))->assertRedirect(route('dashboard'));

        $admin = AdminUser::sole();
        $this->assertSame('superadmin', $admin->role);
        $this->assertSame((string) BusinessSetup::sole()->id, (string) $admin->businessId);
        $this->assertNotSame('999', (string) $admin->businessId);
        $this->assertSame('en', BusinessSetup::sole()->default_locale, 'Locale comes from the session, not the form.');
    }

    public function test_setup_is_closed_once_any_administrator_exists(): void
    {
        // Any account closes setup, not only a Super Admin.
        AdminUser::forceCreate([
            'fullName' => 'Existing Manager', 'mail' => 'existing@example.test',
            'password' => Hash::make('test-secret'), 'role' => 'gm',
        ]);

        $this->get(route('superadmin.setup.show'))
            ->assertRedirect(route('userLogin'))
            ->assertSessionHas('error', __('authentication.first_run_closed'));
        $this->postJson(route('superadmin.setup.store'), $this->payload())->assertForbidden();

        $this->assertSame(1, AdminUser::count());
        $this->assertSame(0, BusinessSetup::count());
    }

    public function test_setup_is_closed_once_any_business_exists(): void
    {
        BusinessSetup::forceCreate(['id' => 7, 'businessName' => 'Orphan Business']);

        $this->get(route('superadmin.setup.show'))
            ->assertRedirect(route('userLogin'))
            ->assertSessionHas('error', __('authentication.first_run_incomplete'));
        $this->postJson(route('superadmin.setup.store'), $this->payload())->assertForbidden();
        $this->get(route('userLogin'))->assertOk()->assertDontSee(route('superadmin.setup.show'), false);

        $this->assertSame(0, AdminUser::count());
        $this->assertSame(1, BusinessSetup::count());
    }

    public function test_bootstrap_rechecks_the_empty_installation_inside_the_lock(): void
    {
        // Simulates a business created by a concurrent request after the route middleware ran.
        BusinessSetup::forceCreate(['businessName' => 'Created Concurrently']);

        try {
            app(FirstAdminBootstrap::class)->createAdminWithBusiness([
                'fullName' => 'Late Owner', 'mail' => 'late@example.test', 'password' => 'correct-horse-9',
            ], 'Late Shop', 'en');
            $this->fail('The bootstrap must refuse a non-empty installation.');
        } catch (BootstrapException $e) {
            $this->assertSame(BootstrapException::ALREADY_COMPLETED, $e->reason());
        }

        $this->assertSame(0, AdminUser::count());
        $this->assertSame(1, BusinessSetup::count());
    }

    public function test_concurrent_attempt_is_rejected_while_the_lock_is_held(): void
    {
        $lock = Cache::lock('retailnova:first-admin-bootstrap', 300);
        $this->assertTrue($lock->get());

        try {
            $this->postJson(route('superadmin.setup.store'), $this->payload())
                ->assertStatus(409)
                ->assertJson(['message' => __('authentication.first_run_in_progress')]);
        } finally {
            $lock->release();
        }

        $this->assertNothingProvisioned();
    }

    public function test_unavailable_lock_store_fails_closed_with_503(): void
    {
        Exceptions::fake();
        // A working cache whose lock provider is down (e.g. Redis or the cache_locks table unreachable).
        Cache::extend('lock-unavailable', function () {
            return Cache::repository(new class extends ArrayStore {
                public function lock($name, $seconds = 0, $owner = null)
                {
                    throw new \RuntimeException('Lock store unavailable.');
                }
            });
        });
        config([
            'cache.stores.lock-unavailable' => ['driver' => 'lock-unavailable'],
            'cache.default' => 'lock-unavailable',
        ]);

        $this->postJson(route('superadmin.setup.store'), $this->payload())
            ->assertStatus(503)
            ->assertJson(['message' => __('authentication.first_run_unavailable')]);

        Exceptions::assertReported(fn (BootstrapException $e) => $e->reason() === BootstrapException::LOCK_UNAVAILABLE);
        $this->assertNothingProvisioned();
    }

    public function test_configured_setup_token_is_required_and_checked_before_validation(): void
    {
        config(['setup.token' => 'install-secret-42']);

        $this->get(route('superadmin.setup.show'))->assertOk()->assertSee('name="setup_token"', false);
        $this->postJson(route('superadmin.setup.store'), $this->payload())
            ->assertForbidden()
            ->assertJson(['message' => __('authentication.setup_token_invalid')]);
        $this->postJson(route('superadmin.setup.store'), $this->payload(['setup_token' => 'install-secret-41']))
            ->assertForbidden();
        // A wrong token is rejected before validation, so an invalid payload reveals nothing.
        $this->postJson(route('superadmin.setup.store'), ['setup_token' => 'wrong'])->assertForbidden();
        $this->assertNothingProvisioned();

        $this->post(route('superadmin.setup.store'), $this->payload(['setup_token' => 'install-secret-42']))
            ->assertRedirect(route('dashboard'));
        $this->assertSame(1, AdminUser::count());
    }

    public function test_setup_token_field_is_hidden_when_no_token_is_configured(): void
    {
        config(['setup.token' => '']);

        $this->get(route('superadmin.setup.show'))->assertOk()->assertDontSee('name="setup_token"', false);
    }

    public function test_production_without_a_setup_token_fails_closed(): void
    {
        $this->app['env'] = 'production';
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['setup.token' => null]);

        $this->get(route('superadmin.setup.show'))->assertStatus(503);
        $this->postJson(route('superadmin.setup.store'), $this->payload())
            ->assertStatus(503)
            ->assertJson(['message' => __('authentication.setup_token_not_configured')]);
        $this->assertNothingProvisioned();

        config(['setup.token' => 'production-secret']);
        $this->post(route('superadmin.setup.store'), $this->payload(['setup_token' => 'production-secret']))
            ->assertRedirect(route('dashboard'));
        $this->assertSame(1, AdminUser::count());
    }

    public function test_setup_submissions_are_throttled_in_their_own_bucket(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('superadmin.setup.store'), ['businessName' => ''])->assertUnprocessable();
        }

        $this->postJson(route('superadmin.setup.store'), $this->payload())->assertTooManyRequests();
        $this->assertNothingProvisioned();

        // Exhausting the setup limit must not lock people out of signing in.
        $this->post(route('adminLogin'), ['userMail' => 'nobody@example.test', 'password' => 'incorrect'])
            ->assertRedirect()
            ->assertSessionHas('login_notice');
    }

    public function test_password_rules_require_confirmation_and_a_bcrypt_safe_length(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $invalid = [
            'shorter than 8' => ['password' => 'Short7!', 'password_confirmation' => 'Short7!'],
            'longer than 72' => ['password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73)],
            'confirmation mismatch' => ['password' => 'correct-horse-9', 'password_confirmation' => 'correct-horse-8'],
            'confirmation missing' => ['password' => 'correct-horse-9', 'password_confirmation' => null],
        ];
        foreach ($invalid as $override) {
            $this->postJson(route('superadmin.setup.store'), $this->payload($override))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('password');
        }
        $this->assertNothingProvisioned();

        $longest = str_repeat('b', 72);
        $this->post(route('superadmin.setup.store'), $this->payload(['password' => $longest, 'password_confirmation' => $longest]))
            ->assertRedirect(route('dashboard'));
        $this->assertTrue(Hash::check($longest, AdminUser::sole()->password));
    }

    public function test_required_fields_and_unique_mail_are_validated(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->postJson(route('superadmin.setup.store'), $this->payload([
            'businessName' => '', 'fullName' => '', 'mail' => 'not-an-email',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['businessName', 'fullName', 'mail']);
        $this->postJson(route('superadmin.setup.store'), $this->payload(['businessName' => str_repeat('x', 256)]))
            ->assertUnprocessable()->assertJsonValidationErrors('businessName');
        $this->assertNothingProvisioned();
    }

    public function test_legacy_public_setup_routes_are_retired(): void
    {
        $this->get('/business/setup')->assertRedirect(route('superadmin.setup.show'));
        $this->postJson('/business/setup', ['businessName' => 'Legacy Shop', 'businessType' => 'mobile_shop'])
            ->assertStatus(410);
        $this->postJson('/creat/admin', $this->payload(['confirmPass' => 'correct-horse-9']))
            ->assertStatus(410)
            ->assertJson(['message' => __('authentication.setup_endpoint_retired')]);

        $this->assertNothingProvisioned();
        // The authenticated settings page keeps its own, case-distinct URL.
        $this->assertSame(url('/Business/setup'), route('addBusinessSetupPage'));
    }
}
