<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductStock;
use App\Models\PurchaseProduct;
use App\Models\SaleProduct;
use App\Models\Supplier;
use App\Services\PurchaseWorkflow;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessOwnershipIntegrityTest extends TestCase
{
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    public function refreshDatabase()
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Ownership tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    private function operator(int $businessId = 1, string $role = 'gm'): AdminUser
    {
        BusinessSetup::forceCreate(['id' => $businessId, 'businessName' => 'Tenant '.$businessId]);
        $user = AdminUser::create([
            'fullName' => 'Tenant Operator', 'mail' => uniqid('owner'). '@example.test',
            'password' => 'unused', 'role' => $role, 'businessId' => $businessId,
        ]);
        $this->actingAs($user, 'admin');
        return $user;
    }

    public function test_product_and_supplier_create_paths_set_owner_and_reject_spoofed_business(): void
    {
        $this->operator();

        $this->post(route('saveProduct'), ['name' => 'Owned Product'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => 'Owned Product', 'businessId' => 1]);
        $this->postJson(route('saveProduct'), ['name' => 'Spoof Product', 'businessId' => 2])
            ->assertForbidden();
        $this->assertDatabaseMissing('products', ['name' => 'Spoof Product']);

        $this->post(route('saveSupplier'), ['fullName' => 'Owned Supplier'])->assertRedirect()->assertSessionHasNoErrors();
        $this->postJson(route('saveSupplier'), ['fullName' => 'Spoof Supplier', 'businessId' => 2])->assertForbidden();
        $this->postJson(route('createSupplier'), ['fullName' => 'Owned AJAX Supplier'])->assertOk();
        $this->assertDatabaseHas('suppliers', ['name' => 'Owned Supplier', 'businessId' => 1]);
        $this->assertDatabaseHas('suppliers', ['name' => 'Owned AJAX Supplier', 'businessId' => 1]);
        $this->postJson(route('createSupplier'), ['fullName' => 'Spoof AJAX Supplier', 'businessId' => 2])
            ->assertForbidden();
        $this->getJson(route('createSupplier', ['fullName' => 'GET Supplier']))->assertStatus(405);
        $this->assertDatabaseMissing('suppliers', ['name' => 'Spoof AJAX Supplier']);
        $this->assertDatabaseMissing('suppliers', ['name' => 'Spoof Supplier']);
    }

    public function test_ajax_product_create_is_post_only_and_uses_authorized_business(): void
    {
        $this->operator();
        $brand = \App\Models\Brand::forceCreate(['name' => 'Shared Brand']);
        $category = \App\Models\Category::forceCreate(['name' => 'Shared Category']);
        $unit = \App\Models\ProductUnit::forceCreate(['name' => 'Each']);
        $payload = ['fullName' => 'AJAX Owned Product', 'brand' => $brand->id,
            'category' => $category->id, 'unitName' => $unit->id];

        $this->postJson(route('createProduct'), $payload)->assertOk();
        $this->assertDatabaseHas('products', ['name' => 'AJAX Owned Product', 'businessId' => 1]);
        $this->postJson(route('createProduct'), array_merge($payload, ['fullName' => 'AJAX Spoof Product', 'businessId' => 2]))
            ->assertForbidden();
        $this->getJson(route('createProduct', $payload))->assertStatus(405);
        $this->assertDatabaseMissing('products', ['name' => 'AJAX Spoof Product']);
    }

    public function test_customer_standard_and_ajax_create_paths_use_actor_business(): void
    {
        $this->operator();
        $this->post(route('saveCustomer'), ['fullName' => 'Owned Customer'])->assertRedirect()->assertSessionHasNoErrors();
        $this->postJson(route('saveCustomer'), ['fullName' => 'Spoof Customer', 'businessId' => 2])->assertForbidden();
        $this->postJson(route('createCustomer'), ['fullName' => 'Owned AJAX Customer'])->assertOk();
        $this->assertDatabaseHas('customers', ['name' => 'Owned Customer', 'businessId' => 1]);
        $this->assertDatabaseHas('customers', ['name' => 'Owned AJAX Customer', 'businessId' => 1]);
        $this->postJson(route('createCustomer'), ['fullName' => 'Spoof AJAX Customer', 'businessId' => 2])
            ->assertForbidden();
        $this->getJson(route('createCustomer', ['fullName' => 'GET Customer']))->assertStatus(405);
        $this->assertDatabaseMissing('customers', ['name' => 'Spoof AJAX Customer']);
        $this->assertDatabaseMissing('customers', ['name' => 'Spoof Customer']);
    }

    public function test_product_supplier_customer_updates_are_business_scoped(): void
    {
        $this->operator(1);
        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Other Tenant']);
        $foreignProduct = Product::forceCreate(['name' => 'Foreign Product', 'businessId' => 2]);
        $foreignSupplier = Supplier::forceCreate(['name' => 'Foreign Supplier', 'businessId' => 2]);
        $foreignCustomer = Customer::forceCreate(['name' => 'Foreign Customer', 'businessId' => 2]);

        $this->get(route('editProduct', $foreignProduct->id))->assertNotFound();
        $this->get(route('editSupplier', $foreignSupplier->id))->assertNotFound();
        $this->get(route('editCustomer', $foreignCustomer->id))->assertNotFound();
        $this->assertDenied($this->postJson(route('saveProduct'), ['profileId' => $foreignProduct->id, 'name' => 'Hijacked']));
        $this->assertDenied($this->postJson(route('saveSupplier'), ['profileId' => $foreignSupplier->id, 'fullName' => 'Hijacked']));
        $this->assertDenied($this->postJson(route('saveCustomer'), ['profileId' => $foreignCustomer->id, 'fullName' => 'Hijacked']));
        $this->assertSame('Foreign Product', $foreignProduct->fresh()->name);
        $this->assertSame('Foreign Supplier', $foreignSupplier->fresh()->name);
        $this->assertSame('Foreign Customer', $foreignCustomer->fresh()->name);
    }

    public function test_related_ajax_product_and_purchase_lookups_reject_foreign_business_records(): void
    {
        $this->operator(1);
        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Other Tenant']);
        $supplier = Supplier::forceCreate(['name' => 'Foreign Supplier', 'businessId' => 2]);
        $product = Product::forceCreate(['name' => 'Foreign Product', 'businessId' => 2]);
        $purchase = PurchaseProduct::forceCreate([
            'productName' => $product->id, 'supplier' => $supplier->id, 'businessId' => 2,
            'qty' => 1, 'buyPrice' => 10, 'salePriceExVat' => 15,
        ]);
        ProductStock::forceCreate([
            'purchaseId' => $purchase->id, 'productId' => $product->id,
            'businessId' => 2, 'currentStock' => 1,
        ]);

        $this->getJson(route('ajax.product.details.public', $product->id))->assertNotFound();
        $this->getJson(route('ajax.purchase.details.public', $purchase->id))->assertNotFound();
    }

    public function test_platform_owner_can_target_an_existing_business_but_missing_target_fails_closed(): void
    {
        $owner = $this->operator(1, 'superadmin');
        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Authorized Tenant']);
        $owner->update(['businessId' => null]);

        $this->post(route('saveProduct'), ['name' => 'Platform Product', 'businessId' => 2])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => 'Platform Product', 'businessId' => 2]);
        $this->postJson(route('saveProduct'), ['name' => 'Ownerless Product'])->assertUnprocessable()
            ->assertJsonValidationErrors('businessId');
        $this->assertDatabaseMissing('products', ['name' => 'Ownerless Product']);

        $this->postJson(route('createSupplier'), ['fullName' => 'Explicit Tenant Supplier', 'businessId' => 2])
            ->assertOk();
        $this->assertDatabaseHas('suppliers', ['name' => 'Explicit Tenant Supplier', 'businessId' => 2]);
        $this->postJson(route('createSupplier'), ['fullName' => 'Unscoped Tenant Supplier'])
            ->assertUnprocessable()->assertJsonValidationErrors('businessId');
        $this->assertDatabaseMissing('suppliers', ['name' => 'Unscoped Tenant Supplier']);
    }

    public function test_product_creation_pages_expose_authorized_platform_business_target_and_purchase_ajax_requires_it(): void
    {
        $owner = $this->operator(1, 'superadmin');
        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Second Authorized Tenant']);
        $owner->update(['businessId' => null]);

        $this->get(route('addProduct'))->assertOk()
            ->assertSee('name="businessId"', false)
            ->assertSee('Second Authorized Tenant')
            ->assertSee(__('inventory.create_product'))
            ->assertDontSee('class="data-tables table mb-0 tbl-server-info rn-table-pro"', false);

        $brand = \App\Models\Brand::forceCreate(['name' => 'Modal Brand']);
        $category = \App\Models\Category::forceCreate(['name' => 'Modal Category']);
        $unit = \App\Models\ProductUnit::forceCreate(['name' => 'Modal Unit']);
        $payload = ['fullName' => 'Purchase Modal Product', 'brand' => $brand->id,
            'category' => $category->id, 'unitName' => $unit->id];

        $this->postJson(route('createProduct'), array_merge($payload, ['businessId' => 2, 'fullName' => 'X']))
            ->assertUnprocessable()->assertJsonValidationErrors('fullName');
        $this->assertDatabaseMissing('products', ['name' => 'X']);
        $this->postJson(route('createProduct'), $payload)->assertUnprocessable()->assertJsonValidationErrors('businessId');
        $this->assertDatabaseMissing('products', ['name' => 'Purchase Modal Product']);
        $this->postJson(route('createProduct'), array_merge($payload, ['businessId' => 2]))->assertOk();
        $this->assertDatabaseHas('products', ['name' => 'Purchase Modal Product', 'businessId' => 2]);

        $this->get(route('addPurchase'))->assertOk()
            ->assertSee('id="productForm"', false)
            ->assertSee('data-purchase-business-context', false)
            ->assertSee('data-target="#productName"', false)
            ->assertSee("if(f.id === 'productForm')", false)
            ->assertSee("#savePurchase [name=\"businessId\"]", false);
    }

    public function test_product_catalog_summary_is_derived_from_only_the_authorized_business_stock(): void
    {
        $this->operator(1, 'gm');
        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Other Tenant']);

        $inStock = Product::forceCreate(['name' => 'Catalog In Stock', 'businessId' => 1, 'quantity' => 3]);
        $lowStock = Product::forceCreate(['name' => 'Catalog Low Stock', 'businessId' => 1, 'quantity' => 5]);
        $outOfStock = Product::forceCreate(['name' => 'Catalog Out Of Stock', 'businessId' => 1, 'quantity' => 2]);
        $foreign = Product::forceCreate(['name' => 'Foreign Catalog Product', 'businessId' => 2, 'quantity' => 1]);

        ProductStock::forceCreate(['productId' => $inStock->id, 'businessId' => 1, 'currentStock' => 10]);
        ProductStock::forceCreate(['productId' => $lowStock->id, 'businessId' => 1, 'currentStock' => 2]);
        ProductStock::forceCreate(['productId' => $foreign->id, 'businessId' => 2, 'currentStock' => 100]);

        $response = $this->get(route('productlist'))->assertOk()
            ->assertSee('Catalog In Stock')
            ->assertSee('Catalog Low Stock')
            ->assertDontSee('Foreign Catalog Product')
            ->assertSeeInOrder([
                __('inventory.catalog_metrics_total'), 'data-stat-value="3"',
                __('inventory.catalog_metrics_in_stock'), 'data-stat-value="1"',
                __('inventory.catalog_metrics_low_stock'), 'data-stat-value="1"',
                __('inventory.catalog_metrics_out_of_stock'), 'data-stat-value="1"',
            ], false);

        $this->assertDatabaseHas('products', ['id' => $outOfStock->id, 'businessId' => 1]);
    }

    public function test_platform_edit_preserves_existing_owner_and_rejects_implicit_tenant_transfer(): void
    {
        $this->operator(1, 'superadmin');
        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Second Tenant']);
        $product = Product::forceCreate(['name' => 'Existing Product', 'businessId' => 2]);
        $supplier = Supplier::forceCreate(['name' => 'Existing Supplier', 'businessId' => 2]);
        $customer = Customer::forceCreate(['name' => 'Existing Customer', 'businessId' => 2]);
        $brand = \App\Models\Brand::forceCreate(['name' => 'Brand']);
        $category = \App\Models\Category::forceCreate(['name' => 'Category']);
        $unit = \App\Models\ProductUnit::forceCreate(['name' => 'Unit']);

        $this->post(route('saveProduct'), ['profileId' => $product->id, 'name' => 'Updated Product',
            'brand' => $brand->id, 'category' => $category->id, 'unitName' => $unit->id])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('saveSupplier'), ['profileId' => $supplier->id, 'fullName' => 'Updated Supplier'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('saveCustomer'), ['profileId' => $customer->id, 'fullName' => 'Updated Customer'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Product', 'businessId' => 2]);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Updated Supplier', 'businessId' => 2]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated Customer', 'businessId' => 2]);
        $this->postJson(route('saveProduct'), ['profileId' => $product->id, 'businessId' => 1,
            'name' => 'Moved Product', 'brand' => $brand->id, 'category' => $category->id, 'unitName' => $unit->id])
            ->assertForbidden();
        $this->assertDatabaseMissing('products', ['id' => $product->id, 'businessId' => 1]);
    }

    public function test_purchase_workflow_propagates_business_to_header_line_and_stock(): void
    {
        $this->operator();
        $supplier = Supplier::create(['name' => 'Purchase Supplier', 'businessId' => 1]);
        $product = Product::create(['name' => 'Purchase Product', 'businessId' => 1]);
        $header = app(PurchaseWorkflow::class)->save([
            'supplierName' => $supplier->id, 'purchaseDate' => now()->toDateString(),
            'invoiceData' => 'OWNERSHIP-PUR-1', 'rows' => [
                'row' => ['product_id' => $product->id, 'qty' => 1, 'buy_price' => 10, 'sale_price' => 15],
            ],
        ]);
        $line = PurchaseProduct::firstOrFail();
        $this->assertSame(1, (int)$header->businessId);
        $this->assertSame(1, (int)$line->businessId);
        $this->assertSame(1, (int)ProductStock::where('purchaseId', $line->id)->value('businessId'));
    }

    public function test_missing_stock_is_created_only_from_verified_purchase_and_product_owner(): void
    {
        $this->operator();
        $supplier = Supplier::create(['name' => 'Stock Supplier', 'businessId' => 1]);
        $product = Product::create(['name' => 'Stock Product', 'businessId' => 1]);
        $purchase = PurchaseProduct::create([
            'productName' => $product->id, 'supplier' => $supplier->id, 'businessId' => 1,
            'qty' => 1, 'buyPrice' => 10, 'salePriceExVat' => 15,
        ]);

        $result = app(StockService::class)->adjustStockForPurchaseQtyChange($purchase->id, 1, 2);
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('product_stocks', [
            'purchaseId' => $purchase->id, 'productId' => $product->id,
            'businessId' => 1, 'currentStock' => 2,
        ]);
    }

    public function test_optional_serial_policy_still_allocates_for_a_new_owned_purchase(): void
    {
        $this->operator();
        $supplier = Supplier::create(['name' => 'Serial Supplier', 'businessId' => 1]);
        $product = Product::create(['name' => 'Serial Product', 'businessId' => 1]);
        $purchase = app(PurchaseWorkflow::class)->save([
            'supplierName' => $supplier->id, 'purchaseDate' => now()->toDateString(),
            'invoiceData' => 'OWNERSHIP-SERIAL-1', 'rows' => [
                'row' => ['product_id' => $product->id, 'qty' => 1, 'buy_price' => 10,
                    'sale_price' => 15, 'serials' => ['OWNED-SERIAL-1']],
            ],
        ]);
        BusinessSetup::findOrFail(1)->update(['sales_serial_selection_required' => false]);
        $customer = Customer::create(['name' => 'Serial Customer', 'businessId' => 1]);

        $this->post(route('saveSale'), [
            'date' => now()->toDateString(), 'customerId' => $customer->id,
            'purchaseData' => [$purchase->items()->first()->id], 'qty' => [1],
            'salePrice' => [15], 'buyPrice' => [10], 'totalSaleAmount' => 15,
            'grandTotal' => 15, 'paidAmount' => 0, 'dueAmount' => 15,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $sale = SaleProduct::firstOrFail();
        $this->assertSame(1, (int)$sale->businessId);
        $this->assertDatabaseHas('product_serials', [
            'purchaseId' => $purchase->items()->first()->id, 'saleId' => $sale->id, 'status' => 'sold',
        ]);
    }

    public function test_invalid_purchase_relationship_rolls_back_header_line_stock_and_serial(): void
    {
        $this->operator();
        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Foreign Tenant']);
        $foreignSupplier = Supplier::forceCreate(['name' => 'Foreign Supplier', 'businessId' => 2]);
        $product = Product::create(['name' => 'Local Product', 'businessId' => 1]);

        try {
            app(PurchaseWorkflow::class)->save([
                'supplierName' => $foreignSupplier->id, 'purchaseDate' => now()->toDateString(),
                'invoiceData' => 'MUST-ROLLBACK', 'rows' => [
                    'row' => ['product_id' => $product->id, 'qty' => 1, 'buy_price' => 10,
                        'sale_price' => 15, 'serials' => ['ROLLBACK-SERIAL']],
                ],
            ]);
            $this->fail('A cross-business supplier should be rejected.');
        } catch (\Illuminate\Validation\ValidationException) {
            $this->assertDatabaseCount('purchase_invoices', 0);
            $this->assertDatabaseCount('purchase_products', 0);
            $this->assertDatabaseCount('product_stocks', 0);
            $this->assertDatabaseCount('product_serials', 0);
        }
    }

    public function test_legacy_invoice_item_inherits_its_sale_business_during_backfill(): void
    {
        $this->operator(1);
        $saleId = DB::table('sale_products')->insertGetId([
            'businessId' => 1, 'invoice' => 'BACKFILL-INV-1', 'date' => '2026-10-26',
            'grandTotal' => 20, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $itemId = DB::table('invoice_items')->insertGetId([
            'saleId' => $saleId, 'qty' => 2, 'salePrice' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_10_26_100000_backfill_missing_tenant_ownership.php');
        $migration->up();

        $this->assertDatabaseHas('invoice_items', ['id' => $itemId, 'saleId' => $saleId, 'businessId' => 1]);
    }
}
