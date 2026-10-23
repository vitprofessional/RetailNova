<?php

namespace Tests\Feature;

use App\Models\{AdminUser, BusinessSetup};
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SalesSettingsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function actor(?string $role, ?int $business = 1, ?int $id = null): AdminUser
    {
        return AdminUser::forceCreate(array_filter([
            'id' => $id, 'fullName' => 'Settings Tester', 'sureName' => 'Test',
            'storeName' => 'Test', 'mail' => uniqid().'@example.test',
            'contactNumber' => '000', 'password' => 'unused-test-password',
            'role' => $role, 'businessId' => $business,
        ], fn ($value, $key) => $key !== 'id' || $value !== null, ARRAY_FILTER_USE_BOTH));
    }

    private function businesses(): void
    {
        foreach ([1, 2] as $id) {
            BusinessSetup::forceCreate(['id' => $id, 'businessName' => 'Settings Business '.$id]);
        }
    }

    public static function roles(): array
    {
        return [
            'business admin' => ['admin', true, false],
            'platform superadmin' => ['superadmin', true, true],
            'legacy spaced super admin' => ['super admin', true, true],
            'ordinary GM' => ['GM', false, false],
            'ordinary storemanager' => ['storemanager', false, false],
            'ordinary salesmanager' => ['salesmanager', false, false],
        ];
    }

    #[DataProvider('roles')]
    public function test_role_access_and_sidebar_share_the_same_decision(string $role, bool $allowed, bool $platform): void
    {
        $this->businesses();
        $this->actor('superadmin'); // Tested accounts are not the legacy first-created owner.
        $user = $this->actor($role);
        $this->actingAs($user, 'admin');
        $this->assertSame($allowed, $user->canManageSalesSettings());
        foreach (['en', 'bn'] as $locale) {
            $user->update(['preferred_locale' => $locale]);
            $page = $this->get(route('admin.profile.show'))->assertOk();
            $this->assertSidebar($page->getContent(), $allowed);
            if (!$allowed) {
                $this->getJson(route('sales.settings.edit'))->assertForbidden();
                $this->postJson(route('sales.settings.update'), ['businessId' => 1,
                    'sales_serial_selection_required' => false])->assertForbidden();
                $this->assertTrue(BusinessSetup::find(1)->sales_serial_selection_required);
                continue;
            }
            $this->get(route('sales.settings.edit'))->assertOk()->assertSee('id="salesSerialRequired"', false);
            foreach ([false, true] as $required) {
                $this->post(route('sales.settings.update'), ['businessId' => 1,
                    'sales_serial_selection_required' => (int) $required])->assertRedirect();
                $response = $this->get(route('sales.settings.edit'))->assertOk();
                $this->assertSame($required, BusinessSetup::find(1)->sales_serial_selection_required);
                $this->assertCheckbox($response->getContent(), $required);
            }
        }
        $target = route('sales.settings.edit', ['businessId' => 2]);
        if ($allowed && $platform) {
            $this->get($target)->assertOk();
            $this->post(route('sales.settings.update'), ['businessId' => 2,
                'sales_serial_selection_required' => false])->assertRedirect();
            $this->assertFalse(BusinessSetup::find(2)->sales_serial_selection_required);
        } elseif ($allowed) {
            $this->getJson($target)->assertNotFound();
            $this->postJson(route('sales.settings.update'), ['businessId' => 2,
                'sales_serial_selection_required' => false])->assertNotFound();
            $this->assertTrue(BusinessSetup::find(2)->sales_serial_selection_required);
        }
    }

    private function assertSidebar(string $html, bool $visible): void
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $links = (new \DOMXPath($document))->query('//*[@id="settings"]//a[@href="'.route('sales.settings.edit').'"]');
        $this->assertCount($visible ? 1 : 0, $links);
    }

    private function assertCheckbox(string $html, bool $required): void
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $checkbox = (new \DOMXPath($document))->query('//*[@id="salesSerialRequired"]')->item(0);
        $this->assertNotNull($checkbox);
        $this->assertSame($required, $checkbox->hasAttribute('checked'));
    }

    public function test_first_account_with_null_role_does_not_receive_platform_privileges(): void
    {
        $this->businesses();
        $owner = $this->actor(null, null, 17);
        $this->actingAs($owner, 'admin');
        $this->assertFalse($owner->hasSuperAdminPrivileges());
        $this->assertFalse($owner->canManageSalesSettings());
        $this->getJson(route('sales.settings.edit'))->assertForbidden();
        $this->postJson(route('sales.settings.update'), ['businessId' => 1,
            'sales_serial_selection_required' => false])->assertForbidden();
        $this->assertNull($owner->fresh()->role);
        $this->assertNull($owner->fresh()->businessId);
    }

    public function test_nonfirst_null_role_does_not_inherit_owner_privileges(): void
    {
        $this->businesses();
        $this->actor('superadmin');
        $user = $this->actor(null);
        $this->actingAs($user, 'admin');
        $this->assertFalse($user->canManageSalesSettings());
        $this->getJson(route('sales.settings.edit'))->assertForbidden();
        $this->postJson(route('sales.settings.update'), ['businessId' => 1,
            'sales_serial_selection_required' => false])->assertForbidden();
    }

    public function test_first_created_gm_does_not_receive_superadmin_privileges(): void
    {
        $this->businesses();
        $owner = $this->actor('GM', 1, 23);
        $this->actingAs($owner, 'admin');
        $this->assertFalse($owner->hasSuperAdminPrivileges());
        $this->assertFalse($owner->canAccessAllSalesBusinesses());
        $this->getJson(route('sales.settings.edit', ['businessId' => 2]))->assertForbidden();
    }
}
