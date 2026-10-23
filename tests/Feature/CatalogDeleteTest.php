<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\DamageProduct;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProvideService;
use App\Models\ReturnPurchaseItem;
use App\Models\ReturnSaleItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsSaleFixtures;
use Tests\TestCase;

/**
 * Catalog records that are still referenced cannot be deleted, singly or in bulk:
 * a product used by a purchase/sale/return/damage, and a brand/category/unit used by a
 * product. Bulk delete removes the unused ones and names the rest. Checks only count rows
 * of the record's own business. These lists offer a single "Delete" in the bulk dialog.
 */
class CatalogDeleteTest extends TestCase
{
    use BuildsSaleFixtures;
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    // Never migrate or seed a configured development/production database.
    public function refreshDatabase()
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Catalog delete tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    private function alert(): array
    {
        return json_decode($this->alertText(), true) ?? [];
    }

    private function bareProduct(string $name, int $businessId = 1, array $attributes = []): Product
    {
        return Product::forceCreate(['name' => $name, 'businessId' => $businessId] + $attributes);
    }

    /** [single delete route, bulk delete route, model, product column] per catalog type. */
    public static function catalogTypes(): array
    {
        return [
            'brand' => ['delBrand', 'brands.bulkDelete', Brand::class, 'brand'],
            'category' => ['delCategory', 'categories.bulkDelete', Category::class, 'category'],
            'unit' => ['delProductUnit', 'units.bulkDelete', ProductUnit::class, 'unitName'],
        ];
    }

    /** Ways a product is "in use". */
    public static function productUses(): array
    {
        return [
            'purchase' => ['purchase'],
            'sale return' => ['sale return'],
            'purchase return' => ['purchase return'],
            'damage' => ['damage'],
        ];
    }

    private function productUsedBy(string $use): Product
    {
        if ($use === 'purchase') {
            return $this->inventory()['product'];
        }
        $product = $this->bareProduct('Used by '.$use);
        match ($use) {
            'sale return' => ReturnSaleItem::forceCreate(['productId' => $product->id, 'qty' => 1, 'businessId' => 1]),
            'purchase return' => ReturnPurchaseItem::forceCreate(['productId' => $product->id, 'qty' => 1, 'businessId' => 1]),
            'damage' => DamageProduct::forceCreate(['product_id' => $product->id, 'qty' => 1, 'businessId' => 1]),
        };
        return $product;
    }

    #[DataProvider('productUses')]
    public function test_product_in_use_cannot_be_deleted(string $use): void
    {
        $this->inventory();
        $product = $this->productUsedBy($use);
        $this->actingAs($this->actor('admin'), 'admin');

        $this->delete(route('delProduct', $product->id))->assertRedirect();

        $this->assertNotNull($product->fresh(), "A product used by a $use must stay.");
        $this->assertSame(__('common.in_use_cannot_be_deleted'), $this->alert()['text'] ?? null);
    }

    public function test_unused_product_is_deleted(): void
    {
        $this->inventory();
        $product = $this->bareProduct('Never used');
        $this->actingAs($this->actor('admin'), 'admin');

        $this->delete(route('delProduct', $product->id))->assertRedirect();

        $this->assertNull($product->fresh());
    }

    #[DataProvider('catalogTypes')]
    public function test_used_catalog_entry_is_kept_and_unused_one_is_deleted(string $single, string $bulk, string $model, string $column): void
    {
        $this->inventory();
        $used = $model::forceCreate(['name' => 'Used '.$column, 'businessId' => 1]);
        $unused = $model::forceCreate(['name' => 'Unused '.$column, 'businessId' => 1]);
        $this->bareProduct('Uses it', 1, [$column => $used->id]);
        $this->actingAs($this->actor('admin'), 'admin');

        $this->delete(route($single, $used->id))->assertRedirect();
        $this->assertNotNull($used->fresh());
        $this->assertSame(__('common.in_use_cannot_be_deleted'), $this->alert()['text'] ?? null);

        $this->delete(route($single, $unused->id))->assertRedirect();
        $this->assertNull($unused->fresh());
    }

    /** Bulk delete of a mixed selection: unused ones go, used ones stay and are named. */
    public static function bulkTypes(): array
    {
        return ['product' => ['product']] + array_map(fn ($row) => [$row[3]], self::catalogTypes());
    }

    private function usedAndUnused(string $kind): array
    {
        if ($kind === 'product') {
            return [$this->productUsedBy('purchase'), $this->bareProduct('Unused product'), 'products.bulkDelete'];
        }
        foreach (self::catalogTypes() as [, $bulk, $model, $column]) {
            if ($column === $kind) {
                $used = $model::forceCreate(['name' => 'Used '.$column, 'businessId' => 1]);
                $unused = $model::forceCreate(['name' => 'Unused '.$column, 'businessId' => 1]);
                $this->bareProduct('Uses it', 1, [$column => $used->id]);
                return [$used, $unused, $bulk];
            }
        }
        throw new \InvalidArgumentException($kind);
    }

    #[DataProvider('bulkTypes')]
    public function test_bulk_delete_removes_unused_and_reports_used(string $kind): void
    {
        $this->inventory();
        [$used, $unused, $bulkRoute] = $this->usedAndUnused($kind);
        $this->actingAs($this->actor('admin'), 'admin');

        $this->post(route($bulkRoute), ['selected' => [$used->getKey(), $unused->getKey()]])->assertRedirect();

        $this->assertNull($unused->fresh(), 'The unused record is deleted.');
        $this->assertNotNull($used->fresh(), 'The used record is kept.');
        $alert = $this->alert();
        $this->assertSame('warning', $alert['icon'] ?? null);
        $this->assertSame(__('common.bulk_delete_skipped_in_use', ['deleted' => 1, 'count' => 1, 'names' => $used->name]), $alert['text'] ?? null);
    }

    public function test_bulk_delete_of_only_used_records_deletes_nothing(): void
    {
        $this->inventory();
        $brands = collect(['First', 'Second'])->map(fn ($name) => Brand::forceCreate(['name' => $name.' Brand', 'businessId' => 1]));
        $brands->each(fn ($brand) => $this->bareProduct('Uses '.$brand->name, 1, ['brand' => $brand->id]));
        $this->actingAs($this->actor('admin'), 'admin');

        $this->post(route('brands.bulkDelete'), ['selected' => $brands->pluck('id')->all()])->assertRedirect();

        $this->assertSame(2, Brand::whereIn('id', $brands->pluck('id'))->count());
        $this->assertSame('error', $this->alert()['icon'] ?? null);
        $this->assertSame(__('common.bulk_delete_skipped_in_use', ['deleted' => 0, 'count' => 2, 'names' => 'First Brand, Second Brand']), $this->alert()['text'] ?? null);
    }

    public function test_usage_is_checked_in_the_records_own_business_only(): void
    {
        $this->inventory();
        $this->inventory(2);
        $brand = Brand::forceCreate(['name' => 'Own Brand', 'businessId' => 1]);
        // Only another business's product points at this id: that does not make it "in use" here.
        $this->bareProduct('Foreign product', 2, ['brand' => $brand->id]);
        $this->actingAs($this->actor('admin'), 'admin');

        $this->delete(route('delBrand', $brand->id))->assertRedirect();

        $this->assertNull($brand->fresh());
    }

    public function test_bulk_dialogs_offer_a_single_delete_except_for_customers_and_suppliers(): void
    {
        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');

        foreach (['productlist', 'addBrand', 'addCategory', 'addProductUnit', 'damageProductList', 'serviceProvideList'] as $page) {
            $this->get(route($page))->assertOk()
                ->assertSee('data-delete-mode="single"', false)
                ->assertSee('data-delete-title="'.e(__('common.bulk_delete_confirm_title')).'"', false)
                ->assertDontSee('data-full-delete=', false)
                ->assertDontSee('name="deleteType"', false);
        }
        foreach (['addCustomer', 'addSupplier'] as $page) {
            $this->get(route($page))->assertOk()
                ->assertSee('data-delete-mode="choice"', false)
                ->assertSee('data-full-delete="allowed"', false);
        }
    }

    public function test_damage_and_provided_service_bulk_delete_no_longer_needs_admin(): void
    {
        $inventory = $this->inventory();
        $damage = DamageProduct::forceCreate(['product_id' => $inventory['product']->id, 'qty' => 1, 'businessId' => 1]);
        $service = ProvideService::forceCreate(['customerName' => 'Walk-in', 'serviceName' => 'Repair', 'amount' => '10', 'businessId' => 1]);
        $this->actingAs($this->actor('gm'), 'admin');

        // The removed "Delete All Data" option is simply a delete now.
        $this->post(route('damageProducts.bulkDelete'), ['selected' => [$damage->id], 'deleteType' => 'fullDelete'])->assertRedirect();
        $this->post(route('providedServices.bulkDelete'), ['selected' => [$service->id], 'deleteType' => 'fullDelete'])->assertRedirect();

        $this->assertNull($damage->fresh());
        $this->assertNull($service->fresh());
    }

    public function test_purchase_bulk_delete_route_is_gone(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('purchases.bulkDelete'));
        $this->assertFalse(view()->exists('purchase.purchaseList'));
    }
}
