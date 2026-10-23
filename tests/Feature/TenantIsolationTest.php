<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\BusinessSetup;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Quotation;
use App\Support\BusinessContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Every business sees and changes only its own data (SaaS isolation, audit item C7). */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        BusinessSetup::forceCreate(['id' => 1, 'businessName' => 'Shop One']);
        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Shop Two']);
    }

    protected function tearDown(): void
    {
        BusinessContext::$enforceWrites = false;
        parent::tearDown();
    }

    private function user(string $role, ?int $businessId = 1): AdminUser
    {
        return AdminUser::create([
            'fullName' => 'User '.$role, 'mail' => uniqid($role).'@example.test',
            'password' => 'x', 'role' => $role, 'businessId' => $businessId,
        ]);
    }

    public static function businessRoles(): array
    {
        return [['admin'], ['gm'], ['storemanager'], ['salesmanager']];
    }

    /** @dataProvider businessRoles */
    public function test_every_business_role_sees_only_its_own_master_lists(string $role): void
    {
        Brand::forceCreate(['name' => 'Own Brand', 'businessId' => 1]);
        Brand::forceCreate(['name' => 'Other Brand', 'businessId' => 2]);
        Category::forceCreate(['name' => 'Own Category', 'businessId' => 1]);
        Category::forceCreate(['name' => 'Other Category', 'businessId' => 2]);
        ProductUnit::forceCreate(['name' => 'Own Unit', 'businessId' => 1]);
        ProductUnit::forceCreate(['name' => 'Other Unit', 'businessId' => 2]);

        $this->actingAs($this->user($role, 1), 'admin');
        $this->get(route('addBrand'))->assertOk()->assertSee('Own Brand')->assertDontSee('Other Brand');
        $this->get(route('addCategory'))->assertOk()->assertSee('Own Category')->assertDontSee('Other Category');
        $this->get(route('addProductUnit'))->assertOk()->assertSee('Own Unit')->assertDontSee('Other Unit');
        $this->assertSame(['Own Brand'], Brand::pluck('name')->all());
    }

    public function test_finance_records_are_isolated(): void
    {
        Account::forceCreate(['account_code' => '1000', 'account_name' => 'Cash One', 'account_type' => 'asset', 'businessId' => 1]);
        Account::forceCreate(['account_code' => '1000', 'account_name' => 'Cash Two', 'account_type' => 'asset', 'businessId' => 2]);
        ExpenseCategory::forceCreate(['name' => 'Rent', 'businessId' => 1]);
        ExpenseCategory::forceCreate(['name' => 'Rent', 'businessId' => 2]);

        $this->actingAs($this->user('admin', 1), 'admin');
        $this->get(route('account.chart'))->assertOk()->assertSee('Cash One')->assertDontSee('Cash Two');
        $this->assertSame(1, Account::count());
        $this->assertSame(1, ExpenseCategory::count());
    }

    public function test_a_transaction_cannot_point_at_another_business_account(): void
    {
        $own = Account::forceCreate(['account_code' => '1000', 'account_name' => 'Cash', 'account_type' => 'asset', 'businessId' => 1]);
        $foreign = Account::forceCreate(['account_code' => '4000', 'account_name' => 'Sales', 'account_type' => 'revenue', 'businessId' => 2]);

        $this->actingAs($this->user('admin', 1), 'admin');
        $this->post(route('account.transactions.store'), [
            'transaction_date' => '2026-10-23', 'transaction_type' => 'journal', 'reference_no' => 'J-1',
            'debit_account_id' => $own->id, 'credit_account_id' => $foreign->id, 'amount' => 100,
        ])->assertSessionHasErrors('credit_account_id');
        $this->assertSame('0.00', number_format((float) $foreign->fresh()->current_balance, 2));
    }

    public function test_bulk_product_delete_cannot_touch_another_business(): void
    {
        $foreign = Product::forceCreate(['name' => 'Foreign', 'businessId' => 2]);
        ProductStock::forceCreate(['productId' => $foreign->id, 'businessId' => 2, 'currentStock' => 5]);

        $this->actingAs($this->user('salesmanager', 1), 'admin');
        $this->post(route('products.bulkDelete'), ['ids' => [$foreign->id]]);
        $this->assertNotNull(Product::withoutGlobalScopes()->find($foreign->id));
        $this->assertSame(1, ProductStock::withoutGlobalScopes()->where('businessId', 2)->count());
    }

    public function test_new_records_are_stamped_with_the_users_business(): void
    {
        $this->actingAs($this->user('gm', 2), 'admin');
        $this->post(route('saveBrand'), ['name' => 'Fresh Brand'])->assertRedirect();
        $this->post(route('saveCategory'), ['name' => 'Fresh Category'])->assertRedirect();
        $this->assertDatabaseHas('brands', ['name' => 'Fresh Brand', 'businessId' => 2]);
        $this->assertDatabaseHas('categories', ['name' => 'Fresh Category', 'businessId' => 2]);
    }

    public function test_the_same_customer_and_barcode_can_exist_in_two_shops(): void
    {
        $this->actingAs($this->user('gm', 1), 'admin');
        $this->post(route('saveCustomer'), ['fullName' => 'Karim', 'mobile' => '01711000000'])->assertSessionHasNoErrors();
        $this->post(route('saveProduct'), ['name' => 'Cola 250ml', 'barCode' => '8901234567890'])->assertSessionHasNoErrors();

        $this->actingAs($this->user('gm', 2), 'admin');
        $this->post(route('saveCustomer'), ['fullName' => 'Karim', 'mobile' => '01711000000'])->assertSessionHasNoErrors();
        $this->post(route('saveProduct'), ['name' => 'Cola 250ml', 'barCode' => '8901234567890'])->assertSessionHasNoErrors();

        $this->assertSame(2, Customer::withoutGlobalScopes()->where('mobile', '01711000000')->count());
        $this->assertSame(2, Product::withoutGlobalScopes()->where('barCode', '8901234567890')->count());

        // Within one shop the rules still hold.
        $this->post(route('saveCustomer'), ['fullName' => 'Karim 2', 'mobile' => '01711000000'])->assertSessionHasErrors('mobile');
    }

    public function test_daily_quote_numbers_run_per_business_without_collisions(): void
    {
        Quotation::forceCreate(['quote_number' => 'Q'.date('Ymd').'-001', 'date' => now()->toDateString(), 'businessId' => 1]);
        $this->actingAs($this->user('gm', 2), 'admin');
        Quotation::create(['quote_number' => 'Q'.date('Ymd').'-001', 'date' => now()->toDateString()]);
        $this->assertSame(2, Quotation::withoutGlobalScopes()->count());
    }

    public function test_a_user_without_a_business_sees_nothing(): void
    {
        Brand::forceCreate(['name' => 'Any Brand', 'businessId' => 1]);
        Product::forceCreate(['name' => 'Any Product', 'businessId' => 1]);
        $this->actingAs($this->user('gm', null), 'admin');
        $this->assertSame(0, Brand::count());
        $this->assertSame(0, Product::count());
    }

    public function test_platform_owner_sees_every_business(): void
    {
        Brand::forceCreate(['name' => 'Brand One', 'businessId' => 1]);
        Brand::forceCreate(['name' => 'Brand Two', 'businessId' => 2]);
        $this->actingAs($this->user('superadmin', null), 'admin');
        $this->assertSame(2, Brand::count());
    }

    public function test_a_business_user_cannot_write_into_another_business(): void
    {
        $this->actingAs($this->user('gm', 1), 'admin');
        BusinessContext::$enforceWrites = true; // as during a web request
        $this->expectException(HttpException::class);
        Brand::forceCreate(['name' => 'Smuggled', 'businessId' => 2]);
    }

    public function test_migration_splits_shared_lists_and_inherits_owners(): void
    {
        $migration = 'database/migrations/2026_10_23_100000_isolate_tenant_data_by_business.php';
        Artisan::call('migrate:rollback', ['--path' => $migration, '--force' => true]);

        // Legacy data: one shared brand used by products of both shops, one unused unit,
        // and a serial whose owner is only known through its purchase.
        $brand = DB::table('brands')->insertGetId(['name' => 'Shared Brand']);
        DB::table('product_units')->insert(['name' => 'Unused Unit']);
        $p1 = DB::table('products')->insertGetId(['name' => 'P1', 'brand' => (string) $brand, 'businessId' => 1]);
        $p2 = DB::table('products')->insertGetId(['name' => 'P2', 'brand' => (string) $brand, 'businessId' => 2]);
        $purchase = DB::table('purchase_products')->insertGetId(['productName' => $p2, 'businessId' => 2, 'qty' => 1]);
        DB::table('product_serials')->insert(['productId' => $p2, 'purchaseId' => $purchase, 'serialNumber' => 'SN-1']);

        Artisan::call('migrate', ['--path' => $migration, '--force' => true]);

        $brands = DB::table('brands')->where('name', 'Shared Brand')->orderBy('businessId')->get();
        $this->assertSame([1, 2], $brands->pluck('businessId')->map(fn ($id) => (int) $id)->all());
        $this->assertSame((string) $brands[0]->id, DB::table('products')->where('id', $p1)->value('brand'));
        $this->assertSame((string) $brands[1]->id, DB::table('products')->where('id', $p2)->value('brand'));
        $this->assertSame(2, DB::table('product_units')->where('name', 'Unused Unit')->count());
        $this->assertSame(2, (int) DB::table('product_serials')->where('serialNumber', 'SN-1')->value('businessId'));
    }
}
