<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Http\Middleware\SafeBackRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AdminBackfillRecoveryTest extends TestCase
{
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    protected function refreshDatabase(): void
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Admin backfill tests require testing + SQLite :memory:.');
        }

        $this->refreshIsolatedDatabase();
    }

    public function test_single_business_migration_assigns_the_store_and_legacy_roles_but_leaves_superadmin_untouched(): void
    {
        BusinessSetup::forceCreate(['id' => 91, 'businessName' => 'Only Store']);
        $this->admin(11, null, null);
        $this->admin(12, null, '');
        $this->admin(13, null, 'superadmin');

        $migration = require database_path('migrations/2026_10_25_100000_backfill_admin_users_for_single_business.php');
        $migration->up();

        $this->assertDatabaseHas('admin_users', ['id' => 11, 'businessId' => '91', 'role' => 'admin']);
        $this->assertDatabaseHas('admin_users', ['id' => 12, 'businessId' => '91', 'role' => 'sales']);
        $this->assertDatabaseHas('admin_users', ['id' => 13, 'businessId' => null, 'role' => 'superadmin']);

        $migration->down();
        $this->assertDatabaseHas('admin_users', ['id' => 11, 'businessId' => '91', 'role' => 'admin']);
    }

    public function test_multiple_businesses_skip_all_changes_and_log_a_warning(): void
    {
        BusinessSetup::forceCreate(['id' => 91, 'businessName' => 'Store One']);
        BusinessSetup::forceCreate(['id' => 92, 'businessName' => 'Store Two']);
        $this->admin(21, null, null);
        Log::spy();

        $migration = require database_path('migrations/2026_10_25_100000_backfill_admin_users_for_single_business.php');
        $migration->up();

        $this->assertDatabaseHas('admin_users', ['id' => 21, 'businessId' => null, 'role' => null]);
        Log::shouldHaveReceived('warning')->once()->with(
            'Admin business backfill skipped because the installation has multiple businesses.',
            ['business_count' => 2, 'unassigned_admin_count' => 1]
        );
    }

    public function test_unassigned_admin_is_redirected_to_dashboard_with_warning_and_cannot_land_on_service_worker(): void
    {
        $user = $this->admin(31, null, 'admin');
        $this->actingAs($user, 'admin');
        Log::spy();

        $response = $this->withHeader('Referer', 'http://localhost/service-worker.js')
            ->post(route('view.mode'), ['mode' => 'desktop']);
        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('business_context_warning', __('common.admin_account_not_linked_to_business'));
        $this->get(route('dashboard'))->assertOk()
            ->assertSee(__('common.admin_account_not_linked_to_business'));
        Log::shouldHaveReceived('warning')->twice();
    }

    public function test_back_redirect_from_service_worker_referer_is_normalized_to_dashboard(): void
    {
        BusinessSetup::forceCreate(['id' => 81, 'businessName' => 'Assigned Store']);
        $user = $this->admin(41, '81', 'admin');
        $this->actingAs($user, 'admin');

        $this->withHeader('Referer', 'http://localhost/service-worker.js')
            ->post(route('view.mode'), ['mode' => 'desktop'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_previous_static_resource_paths_are_rejected(): void
    {
        foreach (['/app.js', '/app.css', '/app.map', '/logo.png', '/photo.jpg', '/photo.webp',
            '/favicon.ico', '/manifest.json', '/site.webmanifest', '/service-worker'] as $path) {
            $this->assertTrue(SafeBackRedirect::isUnsafe('https://example.test'.$path), $path);
        }

        $this->assertFalse(SafeBackRedirect::isUnsafe('https://example.test/purchase/list'));
    }

    public function test_preflight_shows_ids_for_legacy_admin_rows_needing_backfill(): void
    {
        $this->admin(51, null, null);

        $this->assertSame(1, Artisan::call('retailnova:preflight'));
        $output = Artisan::output();
        $this->assertStringContainsString('Admin role/business associations', $output);
        $this->assertStringContainsString('admin_users id: 51', $output);
    }

    private function admin(int $id, ?string $businessId, ?string $role): AdminUser
    {
        return AdminUser::forceCreate([
            'id' => $id,
            'fullName' => 'Test Admin '.$id,
            'mail' => 'admin'.$id.'@example.test',
            'password' => 'not-used',
            'businessId' => $businessId,
            'role' => $role,
        ]);
    }
}
