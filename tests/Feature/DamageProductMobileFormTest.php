<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\DamageProduct;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DamageProductMobileFormTest extends TestCase
{
    use RefreshDatabase;

    private function mobileAdmin(): array
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Damage Mobile Shop']);
        $otherBusiness = BusinessSetup::forceCreate(['businessName' => 'Other Damage Shop']);
        $actor = AdminUser::forceCreate([
            'fullName' => 'Damage Mobile Admin', 'mail' => 'damage-mobile@example.test',
            'password' => bcrypt('test-only'), 'businessId' => $business->id, 'role' => 'admin', 'preferred_locale' => 'bn',
        ]);
        return [$business, $otherBusiness, $actor];
    }

    public function test_mobile_damage_page_uses_shared_stock_picker_date_sheet_and_empty_state(): void
    {
        [$business, $otherBusiness, $actor] = $this->mobileAdmin();
        $owned = Product::forceCreate(['name' => 'Owned Product', 'businessId' => $business->id]);
        $foreign = Product::forceCreate(['name' => 'Foreign Product', 'businessId' => $otherBusiness->id]);
        ProductStock::forceCreate(['productId' => $owned->id, 'currentStock' => 4, 'businessId' => $business->id]);
        ProductStock::forceCreate(['productId' => $foreign->id, 'currentStock' => 9, 'businessId' => $otherBusiness->id]);

        $response = $this->actingAs($actor, 'admin')
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) Mobile')
            ->get(route('damageProduct'))
            ->assertOk()
            ->assertSee('data-rn-damage-form', false)
            ->assertSee('data-rn-product-picker', false)
            ->assertSee('data-mode="damage"', false)
            ->assertSee('data-damage-open-date', false)
            ->assertSee('data-damage-empty', false)
            ->assertDontSee('rn-m-damage-table', false);

        $picker = $this->actingAs($actor, 'admin')->get(route('m.product-picker', ['mode' => 'damage']))
            ->assertOk()->assertJsonCount(1, 'items');
        $this->assertSame($owned->id, $picker->json('items.0.product_id'));
    }

    public function test_mobile_damage_save_deducts_fifo_stock_and_records_each_line(): void
    {
        app()->setLocale('bn');
        [$business, , $actor] = $this->mobileAdmin();
        $first = Product::forceCreate(['name' => 'প্রথম পণ্য', 'businessId' => $business->id]);
        $second = Product::forceCreate(['name' => 'দ্বিতীয় পণ্য', 'businessId' => $business->id]);
        ProductStock::forceCreate(['productId' => $first->id, 'currentStock' => 2, 'businessId' => $business->id]);
        ProductStock::forceCreate(['productId' => $first->id, 'currentStock' => 3, 'businessId' => $business->id]);
        ProductStock::forceCreate(['productId' => $second->id, 'currentStock' => 4, 'businessId' => $business->id]);

        $this->actingAs($actor, 'admin')->post(route('damageProductSave'), [
            'purchaseDate' => '2026-10-28', 'refData' => 'DMG-1',
            'items' => [
                $first->id => ['product_id' => $first->id, 'qty' => 3, 'buy_price' => 60, 'sale_price' => 100],
                $second->id => ['product_id' => $second->id, 'qty' => 2, 'buy_price' => 40, 'sale_price' => 70],
            ],
        ])->assertRedirect(route('damageProductList'))
            ->assertSessionHas('success', 'নষ্ট পণ্য সংরক্ষিত — ২টি পণ্য');

        $this->assertSame(2, ProductStock::withoutGlobalScopes()->where('businessId', $business->id)->where('productId', $first->id)->sum('currentStock'));
        $this->assertSame(2, ProductStock::withoutGlobalScopes()->where('businessId', $business->id)->where('productId', $second->id)->sum('currentStock'));
        $this->assertSame(2, DamageProduct::withoutGlobalScopes()->where('businessId', $business->id)->count());
        $this->assertSame(300.0, (float) DamageProduct::withoutGlobalScopes()->where('product_id', $first->id)->value('total'));
    }

    public function test_mobile_damage_save_rejects_foreign_products_and_quantities_over_stock(): void
    {
        [$business, $otherBusiness, $actor] = $this->mobileAdmin();
        $owned = Product::forceCreate(['name' => 'Owned Product', 'businessId' => $business->id]);
        $foreign = Product::forceCreate(['name' => 'Foreign Product', 'businessId' => $otherBusiness->id]);
        ProductStock::forceCreate(['productId' => $owned->id, 'currentStock' => 2, 'businessId' => $business->id]);
        ProductStock::forceCreate(['productId' => $foreign->id, 'currentStock' => 9, 'businessId' => $otherBusiness->id]);

        $this->actingAs($actor, 'admin')->from(route('damageProduct'))
            ->post(route('damageProductSave'), ['purchaseDate' => '2026-10-28', 'items' => [
                $foreign->id => ['product_id' => $foreign->id, 'qty' => 1],
            ]])->assertRedirect(route('damageProduct'))->assertSessionHasErrors('items');
        $this->assertSame(2, ProductStock::withoutGlobalScopes()->where('businessId', $business->id)->where('productId', $owned->id)->sum('currentStock'));

        $this->post(route('damageProductSave'), ['purchaseDate' => '2026-10-28', 'items' => [
            $owned->id => ['product_id' => $owned->id, 'qty' => 3],
        ]])->assertRedirect(route('damageProduct'))->assertSessionHasErrors('items');
        $this->assertSame(2, ProductStock::withoutGlobalScopes()->where('businessId', $business->id)->where('productId', $owned->id)->sum('currentStock'));
        $this->assertSame(0, DamageProduct::withoutGlobalScopes()->where('businessId', $business->id)->count());
    }
}
