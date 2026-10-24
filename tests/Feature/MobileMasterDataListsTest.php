<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\BusinessSetup;
use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\ProductUnit;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileMasterDataListsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        BusinessSetup::forceCreate(['id' => 1, 'businessName' => 'Mobile Test Shop']);
        $user = AdminUser::create([
            'fullName' => 'Mobile Owner', 'mail' => 'mobile-master@example.test',
            'password' => 'secret', 'role' => 'admin', 'businessId' => 1,
        ]);
        $this->actingAs($user, 'admin')->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile)']);
    }

    public function test_inventory_and_service_master_lists_render_mobile_cards_with_existing_actions(): void
    {
        Brand::forceCreate(['name' => 'Mobile Brand', 'businessId' => 1]);
        Category::forceCreate(['name' => 'Mobile Category', 'businessId' => 1]);
        ProductUnit::forceCreate(['name' => 'Mobile Unit', 'businessId' => 1]);
        Service::forceCreate(['serviceName' => 'Mobile Service', 'rate' => 450, 'businessId' => 1]);

        foreach ([
            ['addBrand', 'Mobile Brand', 'editBrand', 'delBrand'],
            ['addCategory', 'Mobile Category', 'editCategory', 'delCategory'],
            ['addProductUnit', 'Mobile Unit', 'editProductUnit', 'delProductUnit'],
            ['addServiceName', 'Mobile Service', 'editService', 'delService'],
        ] as [$route, $label, $editRoute, $deleteRoute]) {
            $response = $this->get(route($route))->assertOk()->assertSee('rn-m-master-cards', false)->assertSee($label);
            if ($route==='addBrand') $response->assertSee(__('inventory.brand'));
            $response->assertSee(route($editRoute, ['id' => 1]), false);
            $response->assertSee(route($deleteRoute, ['id' => 1]), false);
        }
    }

    public function test_mobile_master_form_opens_from_fab_and_desktop_list_remains_in_markup(): void
    {
        $mobile = file_get_contents(resource_path('views/product/brand.blade.php'));
        $css = file_get_contents(public_path('css/rn-m-components.css'));

        $this->assertStringContainsString('rn-m-master-fab', $mobile);
        $this->assertStringContainsString('rnMobileMasterForm', $mobile);
        $this->assertStringContainsString("form.classList.add('is-open')", $mobile);
        $this->assertStringContainsString('rn-m-master-desktop-list', $mobile);
        $this->assertStringContainsString('.rn-m-master-desktop-list { display: none !important; }', $css);
    }

    public function test_expense_category_cards_keep_edit_delete_and_add_actions(): void
    {
        ExpenseCategory::forceCreate(['name' => 'Transport', 'description' => 'Travel costs', 'is_active' => true, 'businessId' => 1]);

        $this->get(route('expense.categories'))->assertOk()
            ->assertSee('rn-m-master-cards', false)
            ->assertSee('Transport')
            ->assertSee(route('expense.categories.edit', 1), false)
            ->assertSee(route('expense.categories.delete', 1), false)
            ->assertSee(route('expense.categories.create'), false);
    }
}
