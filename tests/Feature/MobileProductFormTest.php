<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\BusinessSetup;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileProductFormTest extends TestCase
{
    use RefreshDatabase;

    private BusinessSetup $ownBusiness;
    private BusinessSetup $otherBusiness;
    private AdminUser $actor;
    private Brand $brand;
    private Category $category;
    private ProductUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ownBusiness = BusinessSetup::forceCreate(['businessName' => 'Product Form Shop']);
        $this->otherBusiness = BusinessSetup::forceCreate(['businessName' => 'Other Product Shop']);
        $this->actor = AdminUser::forceCreate([
            'fullName' => 'Mobile Product Admin',
            'mail' => uniqid('product-form-', true).'@example.test',
            'password' => bcrypt('test-only'),
            'role' => 'admin',
            'businessId' => $this->ownBusiness->id,
        ]);
        $this->brand = Brand::forceCreate(['name' => 'Own Brand', 'businessId' => $this->ownBusiness->id]);
        $this->category = Category::forceCreate(['name' => 'Own Category', 'businessId' => $this->ownBusiness->id]);
        $this->unit = ProductUnit::forceCreate(['name' => 'pcs', 'businessId' => $this->ownBusiness->id]);
    }

    public function test_mobile_create_form_renders_single_csrf_protected_workflow_and_quick_create_controls(): void
    {
        $response = $this->actingAs($this->actor, 'admin')
            ->withCookie('rn_view', 'mobile')
            ->get(route('addProduct'))
            ->assertOk();

        $html = $response->getContent();
        $response->assertSee('name="_token"', false)
            ->assertSee('name="profileId"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="businessId"', false)
            ->assertSee('name="barCode"', false)
            ->assertSee('name="brand"', false)
            ->assertSee('name="category"', false)
            ->assertSee('name="unitName"', false)
            ->assertSee('name="quantity"', false)
            ->assertSee('name="details"', false)
            ->assertSee('ri-barcode-line', false)
            ->assertSee('ri-add-line', false)
            ->assertDontSee('line-awesome.min.css')
            ->assertSee('data-target="#createBrand"', false)
            ->assertSee('data-target="#categoryModal"', false)
            ->assertSee('data-target="#productUnitModal"', false)
            ->assertSee('action="'.route('saveProduct').'"', false);

        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//form[@data-rn-product-form]//*[@name="name"]')->length);
        $this->assertStringNotContainsString('rn-product-form-grid', $html);
        $this->assertStringContainsString('class="rn-m-form"', $html);
        $this->assertStringNotContainsString('product-mobile-form.css', $html);
        $this->assertStringContainsString('rn-m-bottom-sheet-modal', $html);
    }

    public function test_desktop_choice_keeps_the_existing_product_form(): void
    {
        $this->actingAs($this->actor, 'admin')->withCookie('rn_view', 'desktop')
            ->get(route('addProduct'))
            ->assertOk()->assertSee('rn-product-form-grid')->assertDontSee('rn-m-product-form-page');
    }

    public function test_platform_admin_can_choose_a_business_on_mobile(): void
    {
        $platformAdmin = AdminUser::forceCreate([
            'fullName' => 'Platform Product Admin',
            'mail' => uniqid('platform-product-', true).'@example.test',
            'password' => bcrypt('test-only'),
            'role' => 'superadmin',
        ]);

        $this->actingAs($platformAdmin, 'admin')->withCookie('rn_view', 'mobile')
            ->get(route('addProduct'))
            ->assertOk()
            ->assertSee('id="businessId" name="businessId"', false)
            ->assertSee('Product Form Shop')
            ->assertSee('Other Product Shop');
    }

    public function test_more_information_heading_uses_bangla_translation(): void
    {
        $this->actor->preferred_locale = 'bn';
        $this->actor->save();

        $this->actingAs($this->actor, 'admin')->withCookie('rn_view', 'mobile')
            ->get(route('addProduct'))
            ->assertOk()
            ->assertSee('আরও তথ্য');
    }

    public function test_product_name_validation_and_old_input_remain_active_on_mobile(): void
    {
        $this->actingAs($this->actor, 'admin')->withCookie('rn_view', 'mobile');
        $this->from(route('addProduct'))->post(route('saveProduct'), [
            'businessId' => $this->ownBusiness->id,
            'name' => '',
            'details' => 'Keep this note',
        ])->assertRedirect(route('addProduct'))->assertSessionHasErrors('name')->assertSessionHas('_old_input.details', 'Keep this note');

        $this->get(route('addProduct'))->assertOk()->assertSee('Keep this note');
    }

    public function test_edit_prefills_owned_product_and_rejects_foreign_product(): void
    {
        $owned = Product::forceCreate([
            'name' => 'Owned Item', 'businessId' => $this->ownBusiness->id,
            'brand' => $this->brand->id, 'category' => $this->category->id, 'unitName' => $this->unit->id,
            'quantity' => 4, 'details' => 'Existing details', 'barCode' => 'OWN-1',
        ]);
        $foreign = Product::forceCreate(['name' => 'Foreign Item', 'businessId' => $this->otherBusiness->id]);

        $this->actingAs($this->actor, 'admin')->withCookie('rn_view', 'mobile')
            ->get(route('editProduct', $owned->id))->assertOk()->assertSee('Owned Item')->assertSee('Existing details')->assertSee('OWN-1');
        $this->get(route('editProduct', $foreign->id))->assertNotFound();
    }

    public function test_product_form_catalogs_and_quick_create_endpoints_stay_tenant_scoped(): void
    {
        $foreignBrand = Brand::forceCreate(['name' => 'Foreign Brand', 'businessId' => $this->otherBusiness->id]);
        $foreignCategory = Category::forceCreate(['name' => 'Foreign Category', 'businessId' => $this->otherBusiness->id]);
        $foreignUnit = ProductUnit::forceCreate(['name' => 'Foreign Unit', 'businessId' => $this->otherBusiness->id]);

        $this->actingAs($this->actor, 'admin')->withCookie('rn_view', 'mobile')
            ->get(route('addProduct'))->assertOk()
            ->assertSee('Own Brand')->assertDontSee('Foreign Brand')
            ->assertSee('Own Category')->assertDontSee('Foreign Category')
            ->assertSee('pcs')->assertDontSee('Foreign Unit');

        $this->get(route('createBrand', ['name' => 'Quick Brand']))->assertOk();
        $this->get(route('createCategory', ['name' => 'Quick Category']))->assertOk();
        $this->get(route('createProductUnit', ['name' => 'box']))->assertOk();
        $this->assertDatabaseHas('brands', ['name' => 'Quick Brand', 'businessId' => $this->ownBusiness->id]);
        $this->assertDatabaseHas('categories', ['name' => 'Quick Category', 'businessId' => $this->ownBusiness->id]);
        $this->assertDatabaseHas('product_units', ['name' => 'box', 'businessId' => $this->ownBusiness->id]);
    }

    public function test_foreign_catalog_values_and_business_selection_are_rejected(): void
    {
        $foreignBrand = Brand::forceCreate(['name' => 'Foreign Brand', 'businessId' => $this->otherBusiness->id]);
        $foreignCategory = Category::forceCreate(['name' => 'Foreign Category', 'businessId' => $this->otherBusiness->id]);
        $foreignUnit = ProductUnit::forceCreate(['name' => 'Foreign Unit', 'businessId' => $this->otherBusiness->id]);

        $this->actingAs($this->actor, 'admin')->withCookie('rn_view', 'mobile');
        $this->from(route('addProduct'))->post(route('saveProduct'), [
            'name' => 'Isolation Test Item', 'businessId' => $this->ownBusiness->id,
            'brand' => $foreignBrand->id, 'category' => $foreignCategory->id, 'unitName' => $foreignUnit->id,
        ])->assertRedirect(route('addProduct'))
            ->assertSessionHasErrors(['brand', 'category', 'unitName']);

        $this->post(route('saveProduct'), [
            'name' => 'Foreign Business Item', 'businessId' => $this->otherBusiness->id,
        ])->assertForbidden();
    }
}
