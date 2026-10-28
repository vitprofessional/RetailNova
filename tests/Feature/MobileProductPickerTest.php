<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\BusinessSetup;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileProductPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_picker_is_tenant_scoped_and_searches_name_brand_or_barcode_with_combined_filters(): void
    {
        [$business, $actor] = $this->businessAndActor();
        $category = Category::forceCreate(['name' => 'Cables', 'businessId' => $business->id]);
        $otherCategory = Category::forceCreate(['name' => 'Adapters', 'businessId' => $business->id]);
        $brand = Brand::forceCreate(['name' => 'Northstar', 'businessId' => $business->id]);
        $otherBrand = Brand::forceCreate(['name' => 'Southwind', 'businessId' => $business->id]);
        $matched = Product::forceCreate([
            'name' => 'USB Data Cable', 'barCode' => 'PICKER-USB-1', 'brand' => (string) $brand->id,
            'category' => (string) $category->id, 'businessId' => $business->id,
        ]);
        Product::forceCreate([
            'name' => 'USB Adapter', 'barCode' => 'PICKER-USB-2', 'brand' => (string) $brand->id,
            'category' => (string) $otherCategory->id, 'businessId' => $business->id,
        ]);
        Product::forceCreate([
            'name' => 'Northstar Cable', 'barCode' => 'PICKER-USB-3', 'brand' => (string) $otherBrand->id,
            'category' => (string) $category->id, 'businessId' => $business->id,
        ]);
        Product::forceCreate([
            'name' => 'Private USB Cable', 'barCode' => 'PRIVATE-USB', 'brand' => (string) $brand->id,
            'category' => (string) $category->id, 'businessId' => $this->business('Other shop')->id,
        ]);

        $this->actingAs($actor, 'admin');
        $response = $this->getJson(route('m.product-picker', [
            'mode' => 'quotation', 'q' => 'USB', 'category' => $category->id, 'brand' => $brand->id,
        ]))->assertOk()->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.product_id', $matched->id);
        $response->assertJsonPath('items.0.brand', 'Northstar')->assertJsonPath('items.0.category', 'Cables');
        $this->getJson(route('m.product-picker', ['mode' => 'sale']))
            ->assertOk()->assertJsonPath('items.0.buy_price', null);

        $foreignCategory = Category::forceCreate(['name' => 'Private category', 'businessId' => $this->business('Foreign category shop')->id]);
        $this->getJson(route('m.product-picker', ['mode' => 'purchase', 'category' => $foreignCategory->id]))->assertNotFound();

        $this->getJson(route('m.product-picker', ['mode' => 'purchase', 'q' => 'Northstar']))
            ->assertOk()->assertJsonFragment(['name' => 'USB Data Cable']);
        $this->getJson(route('m.product-picker', ['mode' => 'purchase', 'q' => 'PICKER-USB-2']))
            ->assertOk()->assertJsonFragment(['name' => 'USB Adapter']);
        $this->getJson(route('m.product-picker', ['mode' => 'purchase', 'q' => 'PRIVATE-USB']))
            ->assertOk()->assertJsonCount(0, 'items');
    }

    public function test_same_named_products_from_other_businesses_never_leak_into_picker_results(): void
    {
        [$business, $actor] = $this->businessAndActor();
        $local = Product::forceCreate([
            'name' => 'USB-C Adapter', 'barCode' => 'LOCAL-SAME-NAME', 'businessId' => $business->id,
        ]);
        $foreignBusiness = $this->business('Same-name private shop');
        $foreign = Product::forceCreate([
            'name' => 'USB-C Adapter', 'barCode' => 'PRIVATE-SAME-NAME', 'businessId' => $foreignBusiness->id,
        ]);

        $this->actingAs($actor, 'admin');
        $response = $this->getJson(route('m.product-picker', ['mode' => 'sale', 'q' => 'USB-C Adapter']))
            ->assertOk()->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.product_id', $local->id);

        $this->assertNotSame($foreign->id, $response->json('items.0.product_id'));
        $this->assertSame('LOCAL-SAME-NAME', $response->json('items.0.barcode'));
    }

    public function test_picker_paginates_at_twenty_and_return_mode_selects_business_owned_stock_lots(): void
    {
        [$business, $actor] = $this->businessAndActor();
        $category = Category::forceCreate(['name' => 'Adapters', 'businessId' => $business->id]);
        $brand = Brand::forceCreate(['name' => 'RetailNova Labs', 'businessId' => $business->id]);
        for ($i = 1; $i <= 22; $i++) {
            Product::forceCreate([
                'name' => sprintf('Adapter %02d', $i), 'barCode' => 'ADAPTER-'.$i,
                'brand' => (string) $brand->id, 'category' => (string) $category->id,
                'businessId' => $business->id,
            ]);
        }
        $product = Product::query()->where('name', 'Adapter 01')->firstOrFail();
        $supplierId = \App\Models\Supplier::forceCreate(['name' => 'Picker Supplier', 'businessId' => $business->id])->id;
        $lot = PurchaseProduct::forceCreate([
            'businessId' => $business->id, 'productName' => $product->id, 'supplier' => (string) $supplierId,
            'invoice' => 'PICKER-RETURN-1', 'purchase_date' => now()->toDateString(), 'qty' => 3,
            'buyPrice' => 10, 'salePriceExVat' => 15, 'totalAmount' => 30,
        ]);
        ProductStock::forceCreate(['businessId' => $business->id, 'purchaseId' => $lot->id, 'productId' => $product->id, 'currentStock' => 2]);
        $emptyLot = PurchaseProduct::forceCreate([
            'businessId' => $business->id, 'productName' => $product->id, 'supplier' => (string) $supplierId,
            'invoice' => 'PICKER-RETURN-EMPTY', 'purchase_date' => now()->toDateString(), 'qty' => 1,
            'buyPrice' => 10, 'salePriceExVat' => 15, 'totalAmount' => 10,
        ]);
        ProductStock::forceCreate(['businessId' => $business->id, 'purchaseId' => $emptyLot->id, 'productId' => $product->id, 'currentStock' => 0]);

        $this->actingAs($actor, 'admin');
        $first = $this->getJson(route('m.product-picker', ['mode' => 'quotation']))->assertOk();
        $first->assertJsonCount(20, 'items')->assertJsonPath('has_more', true);
        $this->getJson(route('m.product-picker', ['mode' => 'quotation', 'page' => 2]))
            ->assertOk()->assertJsonCount(2, 'items')->assertJsonPath('has_more', false);
        $returns = $this->getJson(route('m.product-picker', ['mode' => 'purchase-return']))->assertOk()->assertJsonPath('has_more', false);
        $returns->assertJsonFragment(['item_id' => $lot->id, 'product_id' => $product->id, 'stock' => 2]);
        $this->assertNotContains($emptyLot->id, collect($returns->json('items'))->pluck('item_id')->all());
    }

    public function test_shared_picker_markup_keeps_transaction_field_names_and_mobile_views(): void
    {
        [$business, $actor] = $this->businessAndActor();
        $supplier = \App\Models\Supplier::forceCreate(['name' => 'Picker supplier', 'businessId' => $business->id]);
        $product = Product::forceCreate(['name' => 'Picker product', 'businessId' => $business->id]);
        $purchase = PurchaseProduct::forceCreate([
            'businessId' => $business->id, 'productName' => $product->id, 'supplier' => (string) $supplier->id,
            'invoice' => 'PICKER-EDIT-1', 'purchase_date' => now()->toDateString(), 'qty' => 2,
            'buyPrice' => 10, 'salePriceExVat' => 15, 'totalAmount' => 20,
        ]);
        ProductStock::forceCreate(['businessId' => $business->id, 'purchaseId' => $purchase->id, 'productId' => $product->id, 'currentStock' => 2]);
        $quote = \App\Models\Quotation::forceCreate([
            'quote_number' => 'PICKER-Q-1', 'date' => now()->toDateString(), 'status' => 'draft',
        ]);
        $this->actingAs($actor, 'admin')->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) Mobile');
        $this->get(route('newsale'))->assertOk()->assertSee('data-rn-product-picker', false)->assertSee('data-mode="sale"', false);
        $this->get(route('newsale'))->assertSee('data-cart-key="rn-cart-sale-'.$actor->id.'-'.$business->id.'"', false);
        $this->get(route('addPurchase'))->assertOk()->assertSee('data-mode="purchase"', false)
            ->assertSee('name="productAdd"', false)->assertSee('name="productName[__IDX__]"', false)
            ->assertDontSee('data-picker-sheet', false)->assertDontSee('data-picker-qty', false)
            ->assertSee('data-q6-sheet="line"', false)
            ->assertSee('data-q6-picker-done', false)
            ->assertSee('data-q6-supplier-post', false)
            ->assertSee('data-reason-products=', false)
            ->assertSee(__('mobile.done'));
        $this->get(route('quotation.create'))->assertOk()->assertSee('data-mode="quotation"', false);
        $this->get(route('quotation.edit', $quote->id))->assertOk()->assertSee('data-mode="quotation"', false);
        $this->get(route('editPurchase', $purchase->id))->assertOk()->assertSee('data-mode="purchase"', false)
            ->assertSee('name="productName_select"', false);
        $this->get(route('returnPurchase.select'))->assertOk()->assertSee('data-mode="purchase-return"', false);
        $this->get(route('returnPurchase', $purchase->id))->assertOk()->assertDontSee('data-rn-product-picker', false)
            ->assertSee('name="purchaseId"', false)->assertSee('name="productId"', false)->assertSee('name="supplierId"', false);
        $this->assertSame((int) $business->id, (int) $actor->businessId);
    }

    private function businessAndActor(): array
    {
        $business = $this->business('Picker shop');
        $actor = AdminUser::forceCreate([
            'fullName' => 'Picker Admin', 'mail' => uniqid('picker-', true).'@example.test',
            'password' => bcrypt('test-only'), 'role' => 'admin', 'businessId' => $business->id,
        ]);
        return [$business, $actor];
    }

    private function business(string $name): BusinessSetup
    {
        return BusinessSetup::forceCreate(['businessName' => $name, 'businessType' => 'computer_shop']);
    }
}
