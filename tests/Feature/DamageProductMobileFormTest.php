<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DamageProductMobileFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_damage_form_uses_shared_actions_and_only_lists_owned_products(): void
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Damage Mobile Shop']);
        $otherBusiness = BusinessSetup::forceCreate(['businessName' => 'Other Damage Shop']);
        $actor = AdminUser::forceCreate([
            'fullName' => 'Damage Mobile Admin',
            'mail' => 'damage-mobile@example.test',
            'password' => bcrypt('test-only'),
            'businessId' => $business->id,
            'role' => 'admin',
        ]);
        Product::forceCreate(['name' => 'Owned Product', 'businessId' => $business->id]);
        Product::forceCreate(['name' => 'Foreign Product', 'businessId' => $otherBusiness->id]);

        $response = $this->actingAs($actor, 'admin')
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile')
            ->get(route('damageProduct'))
            ->assertOk()
            ->assertSee('class="row rn-m-form rn-m-damage-form"', false)
            ->assertSee('rn-m-damage-table', false)
            ->assertSee('data-label="'.e(__('inventory.product_qty')).'"', false)
            ->assertSee('name="qty"', false)
            ->assertSee('href="'.route('damageProductList').'"', false)
            ->assertSee('action="'.route('damageProductSave').'"', false)
            ->assertSee('Owned Product')
            ->assertDontSee('Foreign Product');

        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(7, $xpath->query('//table[contains(@class,"rn-m-damage-table")]//tbody/tr/td[@data-label]')->length);
    }
}
