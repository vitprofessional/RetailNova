<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessLocation;
use App\Models\BusinessSetup;
use App\Models\PaymentGateway;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSuperAdminListsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        BusinessSetup::forceCreate(['id' => 1, 'businessName' => 'Platform Shop']);
        $superAdmin = AdminUser::create([
            'fullName' => 'Platform Owner', 'mail' => 'platform-owner@example.test',
            'password' => 'secret', 'role' => 'superadmin', 'businessId' => 1,
        ]);
        AdminUser::create([
            'fullName' => 'Mobile Store User', 'mail' => 'mobile-store-user@example.test',
            'password' => 'secret', 'role' => 'storemanager', 'businessId' => 1,
        ]);

        $this->actingAs($superAdmin, 'admin')->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 14; Mobile) AppleWebKit/537.36 Chrome/129.0 Mobile Safari/537.36',
        ]);
    }

    public function test_super_admin_list_pages_render_mobile_cards_with_existing_actions(): void
    {
        BusinessLocation::forceCreate([
            'businessId' => 1, 'name' => 'Platform Main Branch', 'city' => 'Dhaka', 'phone' => '01700000000', 'is_main_location' => true,
        ]);
        $plan = SubscriptionPlan::create(['name' => 'Mobile Annual', 'price' => 1200, 'duration_days' => 365, 'features' => 'Core features', 'is_active' => true]);
        Subscription::create(['business_id' => 1, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addYear()]);
        PaymentGateway::create(['name' => 'Mobile Gateway', 'provider' => 'Local Provider', 'mode' => 'sandbox', 'is_active' => true]);

        $this->get(route('admin.super.users.index'))
            ->assertOk()->assertSee('rn-superadmin-user-table', false)
            ->assertSee('Mobile Store User')->assertSee('Platform Shop');

        $this->get(route('admin.super.stores.index'))
            ->assertOk()->assertSee('rn-superadmin-mobile-list', false)
            ->assertSee('Platform Main Branch')->assertSee('Dhaka')
            ->assertSee(route('business.locations.edit', 1), false);

        $this->get(route('admin.super.subscriptions.plans'))
            ->assertOk()->assertSee('Mobile Annual')->assertSee('rn-superadmin-card', false)
            ->assertSee(route('admin.super.subscriptions.plans.delete', $plan->id), false);

        $this->get(route('admin.super.subscriptions.index'))
            ->assertOk()->assertSee('Mobile Annual')->assertSee('Platform Shop')
            ->assertSee('name="business_id"', false)->assertSee('>Platform Shop</option>', false)
            ->assertSee(route('admin.super.subscriptions.cancel', 1), false);

        $this->get(route('admin.super.gateways.index'))
            ->assertOk()->assertSee('Mobile Gateway')->assertSee('Local Provider')
            ->assertSee(route('admin.super.gateways.update', 1), false)
            ->assertSee(route('admin.super.gateways.destroy', 1), false);
    }

    public function test_missing_service_admin_page_keeps_existing_ajax_filter_and_export(): void
    {
        $this->get(route('admin.provideServices.missing'))
            ->assertOk()
            ->assertSee('reportContent', false)
            ->assertSee(route('admin.provideServices.missing.export'), false);

        $partial = file_get_contents(resource_path('views/admin/partials/provide_services_table.blade.php'));
        $this->assertStringContainsString('rn-superadmin-mobile-list', $partial);
        $this->assertStringContainsString('$r->customerLabel()', $partial);
    }
}
