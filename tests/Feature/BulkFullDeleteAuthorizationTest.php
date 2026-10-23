<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\DamageProduct;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProvideService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsSaleFixtures;
use Tests\TestCase;

/**
 * Every bulk-delete dialog offers "Delete All Data" (deleteType=fullDelete). Only admins and
 * superadmins may use it: the server answers 403 and the dialog hides the option for others.
 */
class BulkFullDeleteAuthorizationTest extends TestCase
{
    use BuildsSaleFixtures;
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    // Never migrate or seed a configured development/production database.
    public function refreshDatabase()
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Bulk full-delete tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    /**
     * [type, list page route, bulk-delete route, page uses the shared bulk dialog].
     * The live purchase list is the purchase-invoice list with its own bulk form (no
     * "Delete All Data"); the old purchases.bulkDelete endpoint is still guarded.
     */
    public static function bulkDeleteTypes(): array
    {
        return [
            'products' => ['products', 'productlist', 'products.bulkDelete', true],
            'brands' => ['brands', 'addBrand', 'brands.bulkDelete', true],
            'categories' => ['categories', 'addCategory', 'categories.bulkDelete', true],
            'units' => ['units', 'addProductUnit', 'units.bulkDelete', true],
            'damage products' => ['damage', 'damageProductList', 'damageProducts.bulkDelete', true],
            'purchases' => ['purchases', 'purchaseList', 'purchases.bulkDelete', false],
            'provided services' => ['services', 'serviceProvideList', 'providedServices.bulkDelete', true],
        ];
    }

    private function record(string $type): Model
    {
        $inventory = $this->inventory();

        return match ($type) {
            'products' => $inventory['product'],
            'brands' => Brand::forceCreate(['name' => 'Wipe Brand', 'businessId' => 1]),
            'categories' => Category::forceCreate(['name' => 'Wipe Category', 'businessId' => 1]),
            'units' => ProductUnit::forceCreate(['name' => 'Wipe Unit', 'businessId' => 1]),
            'damage' => DamageProduct::forceCreate(['product_id' => $inventory['product']->id, 'qty' => 1, 'businessId' => 1]),
            'purchases' => $inventory['purchase'],
            'services' => ProvideService::forceCreate(['customerName' => 'Wipe Customer', 'serviceName' => 'Repair', 'amount' => '10', 'businessId' => 1]),
        };
    }

    #[DataProvider('bulkDeleteTypes')]
    public function test_non_admin_cannot_use_delete_all_data(string $type, string $pageRoute, string $bulkRoute, bool $sharedDialog): void
    {
        $record = $this->record($type);
        $this->actingAs($this->actor('gm'), 'admin');

        $page = $this->get(route($pageRoute))->assertOk()
            ->assertDontSee('data-full-delete="allowed"', false)
            ->assertDontSee('data-full-delete-warning="', false)
            ->assertDontSee(__('common.bulk_delete_all_data_warning'));
        if ($sharedDialog) {
            $page->assertSee('data-full-delete="denied"', false);
        }

        $this->post(route($bulkRoute), [
            'deleteType' => 'fullDelete', 'selected' => [$record->getKey()], 'ids' => [$record->getKey()],
        ])->assertForbidden();

        $this->assertNotNull($record->fresh(), ucfirst($type).' must not be deleted.');
    }

    public function test_admins_see_delete_all_data_with_a_permanent_warning_in_both_languages(): void
    {
        foreach (self::bulkDeleteTypes() as [$type]) {
            $this->record($type);
        }
        $admin = $this->actor('admin');
        $this->actingAs($admin, 'admin');

        foreach (['en', 'bn'] as $locale) {
            $admin->update(['preferred_locale' => $locale]);
            foreach (self::bulkDeleteTypes() as [, $pageRoute, , $sharedDialog]) {
                if (!$sharedDialog) {
                    continue;
                }
                $this->get(route($pageRoute))->assertOk()
                    ->assertSee('lang="'.$locale.'"', false)
                    ->assertSee('data-full-delete="allowed"', false)
                    ->assertSee(__('common.bulk_delete_all_data_warning'))
                    ->assertSee(__('common.bulk_delete_all_data_confirm'));
            }
        }
        $this->assertStringContainsString('স্থায়ীভাবে', __('common.bulk_delete_all_data_warning', [], 'bn'));
    }
}
