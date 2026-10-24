<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\PurchaseInvoice;
use App\Models\SaleProduct;
use App\Models\Supplier;
use App\Services\MobileLists\MobileList;
use App\Support\MobileView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSaleFixtures;
use Tests\TestCase;

/** UI-2: mobile/desktop version switch, mobile components and the six list pages as cards. */
class MobileListPagesTest extends TestCase
{
    use BuildsSaleFixtures;
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    private const PHONE = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Mobile Safari/537.36';
    private const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36';

    /** The six lists (products/stock has two routes) and the text each fixture row shows. */
    private const PAGES = [
        'saleList' => 'INV-1', 'productlist' => 'Product', 'stockProduct' => 'Product', 'addCustomer' => 'Customer',
        'addSupplier' => 'Supplier', 'purchaseList' => 'PUR-1', 'expense.list' => 'EXP-1',
    ];

    // Never migrate or seed a configured development/production database.
    public function refreshDatabase()
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Mobile list tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new \DOMXPath($document);
    }

    private function onPhone(): static
    {
        return $this->withHeader('User-Agent', self::PHONE);
    }

    private function onDesktop(): static
    {
        return $this->withHeader('User-Agent', self::DESKTOP);
    }

    /** One row for each list in $businessId, every text prefixed with $tag. */
    private function seedLists(int $businessId, string $tag, AdminUser $seller): array
    {
        $inventory = $this->inventory($businessId);
        $inventory['product']->forceFill(['name' => $tag.' Product', 'quantity' => 5])->save();
        $customer = Customer::forceCreate(['name' => $tag.' Customer', 'mobile' => '0171100000'.$businessId, 'businessId' => $businessId]);
        $supplier = Supplier::forceCreate(['name' => $tag.' Supplier', 'businessId' => $businessId]);
        $sale = SaleProduct::forceCreate([
            'invoice' => $tag.'-INV-1', 'customerId' => $customer->id, 'businessId' => $businessId, 'salespersonId' => $seller->id,
            'date' => now()->toDateString(), 'totalSale' => 300, 'grandTotal' => 300, 'paidAmount' => 100,
            'invoiceDue' => 200, 'curDue' => 200, 'payment_method' => 'bkash',
        ]);
        $purchase = PurchaseInvoice::forceCreate([
            'businessId' => $businessId, 'supplier_id' => $supplier->id, 'invoice' => $tag.'-PUR-1', 'purchase_date' => now()->toDateString(),
            'total_amount' => 500, 'grand_total' => 500, 'paid_amount' => 200, 'due_amount' => 300,
        ]);
        $category = ExpenseCategory::forceCreate(['name' => $tag.' Rent', 'businessId' => $businessId, 'is_active' => true]);
        $expense = ExpenseEntry::forceCreate([
            'businessId' => $businessId, 'expense_date' => now()->toDateString(), 'category_id' => $category->id, 'amount' => 150,
            'payment_method' => 'cash', 'reference_no' => $tag.'-EXP-1', 'created_by' => $seller->id, 'created_by_type' => 'admin',
        ]);

        return compact('inventory', 'customer', 'supplier', 'sale', 'purchase', 'category', 'expense');
    }

    public function test_the_device_decides_and_the_cookie_overrides(): void
    {
        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');

        $desktop = $this->xpath($this->onDesktop()->get(route('dashboard'))->assertOk()->getContent());
        $this->assertCount(1, $desktop->query('//*[@id="rn-sidebar"]'));
        $this->assertCount(0, $desktop->query('//*[@data-rn-tabbar]'), 'No mobile HTML on the desktop.');
        $this->assertCount(0, $desktop->query('//link[contains(@href,"css/rn-mobile.css")]'));
        $this->assertCount(0, $desktop->query('//*[@data-rn-home]'));
        $this->assertCount(1, $desktop->query('//main[contains(@class,"rn-dashboard")]'));

        $phone = $this->xpath($this->onPhone()->get(route('dashboard'))->assertOk()->getContent());
        $this->assertCount(1, $phone->query('//*[@data-rn-tabbar]'));
        $this->assertCount(1, $phone->query('//*[@data-rn-home]'));
        $this->assertCount(0, $phone->query('//*[@id="rn-sidebar"]'), 'No desktop HTML on the phone.');
        $this->assertCount(0, $phone->query('//main[contains(@class,"rn-dashboard")]'));
        $this->assertCount(0, $phone->query('//script[contains(@src,"chart.umd")]'));
        $this->assertCount(1, $phone->query('//link[contains(@href,"css/rn-mobile.css")]'));
        $this->assertCount(1, $phone->query('//link[contains(@href,"css/rn-m-components.css")]'));

        // The switch on the "More" page stores the choice in a cookie.
        $this->assertCount(1, $phone->query('//*[@id="rn-more"]//form[@action="'.route('view.mode').'"]//button[@name="mode" and @value="desktop"]'));
        $this->onPhone()->from(route('saleList'))->post(route('view.mode'), ['mode' => 'desktop'])
            ->assertRedirect(route('saleList'))->assertCookie(MobileView::COOKIE, 'desktop');
        $this->post(route('view.mode'), ['mode' => 'tablet'])->assertSessionHasErrors('mode');

        $forcedDesktop = $this->xpath($this->onPhone()->withCookie(MobileView::COOKIE, 'desktop')->get(route('dashboard'))->getContent());
        $this->assertCount(1, $forcedDesktop->query('//*[@id="rn-sidebar"]'));
        $this->assertCount(0, $forcedDesktop->query('//*[@data-rn-tabbar]'));
        $this->assertCount(1, $forcedDesktop->query('//form[@action="'.route('view.mode').'"]//button[@value="mobile"]'), 'A phone in desktop mode can switch back.');

        $forcedMobile = $this->xpath($this->onDesktop()->withCookie(MobileView::COOKIE, 'mobile')->get(route('dashboard'))->getContent());
        $this->assertCount(1, $forcedMobile->query('//*[@data-rn-tabbar]'));
        $this->assertCount(0, $forcedMobile->query('//*[@id="rn-sidebar"]'));

        $plainDesktop = $this->xpath($this->onDesktop()->withCookie(MobileView::COOKIE, 'desktop')->get(route('dashboard'))->getContent());
        $this->assertCount(0, $plainDesktop->query('//form[@action="'.route('view.mode').'"]'), 'Real desktops never see the switch.');
    }

    public function test_list_pages_are_cards_on_the_phone_and_tables_on_the_desktop(): void
    {
        $admin = $this->actor('admin');
        $this->seedLists(1, 'Own', $admin);
        $this->actingAs($admin, 'admin');

        foreach (self::PAGES as $route => $text) {
            $phone = $this->onPhone()->get(route($route))->assertOk()->getContent();
            $xpath = $this->xpath($phone);
            $this->assertStringNotContainsString('<table', $phone, "$route sends no table to the phone");
            $this->assertGreaterThan(0, $xpath->query('//ul[@data-rn-cards]/li[contains(@class,"rn-m-card")]')->length, $route);
            $this->assertStringContainsString($text, $xpath->query('//ul[@data-rn-cards]')->item(0)->textContent, $route);
            $this->assertCount(1, $xpath->query('//*[@data-rn-list]//form[@role="search"]//input[@name="q"]'), $route);
            $this->assertCount(3, $xpath->query('//dl[contains(@class,"rn-m-summary")]/div'), "$route has three summary figures");
            $this->assertCount(1, $xpath->query('//a[contains(@class,"rn-m-fab")]'), $route);
            $this->assertCount(0, $xpath->query('//*[contains(@class,"bulk-select") or @id="bulkDeleteForm" or contains(@class,"rn-check-col")]'), "$route has no bulk selection");

            $desktop = $this->onDesktop()->get(route($route))->assertOk()->getContent();
            $this->assertStringContainsString('<table', $desktop, "$route keeps its desktop table");
            $this->assertStringNotContainsString('rn-m-card', $desktop, $route);
            $this->assertStringNotContainsString('data-rn-tabbar', $desktop, $route);
        }
    }

    public function test_empty_lists_show_the_empty_state(): void
    {
        $this->inventory();
        \App\Models\Product::query()->delete();
        \App\Models\ProductStock::query()->delete();
        \App\Models\PurchaseProduct::query()->delete();
        Supplier::query()->delete();
        $this->actingAs($this->actor('admin'), 'admin');

        foreach (array_keys(self::PAGES) as $route) {
            $html = $this->onPhone()->get(route($route))->assertOk()->getContent();
            $xpath = $this->xpath($html);
            $this->assertStringNotContainsString('<table', $html, $route);
            $this->assertCount(0, $xpath->query('//li[contains(@class,"rn-m-card")]'), $route);
            $this->assertCount(1, $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " rn-m-empty-state ")]'), $route);
            $this->assertCount(1, $xpath->query('//*[@data-rn-load-more and @hidden]'), "$route has nothing more to load");
        }
    }

    public function test_load_more_returns_twenty_cards_at_a_time_from_json(): void
    {
        $admin = $this->actor('admin');
        $own = $this->seedLists(1, 'Own', $admin);
        for ($i = 2; $i <= 25; $i++) {
            SaleProduct::forceCreate([
                'invoice' => 'Own-INV-'.$i, 'customerId' => $own['customer']->id, 'businessId' => 1, 'salespersonId' => $admin->id,
                'date' => now()->toDateString(), 'grandTotal' => 100, 'paidAmount' => 100, 'curDue' => 0, 'invoiceDue' => 0,
            ]);
        }
        $this->actingAs($admin, 'admin');

        $page = $this->xpath($this->onPhone()->get(route('saleList'))->getContent());
        $this->assertCount(MobileList::PER_PAGE, $page->query('//ul[@data-rn-cards]/li'));
        $more = $page->query('//*[@data-rn-load-more]//button[@data-rn-load-more-button]')->item(0)->getAttribute('data-url');
        $this->assertStringStartsWith(route('m.lists', ['list' => 'sales']), $more);

        $json = $this->getJson($more)->assertOk()->assertJson(['list' => 'sales', 'page' => 2, 'next_page' => null, 'total' => 25, 'shown' => 25]);
        $this->assertCount(5, $json->json('items'));
        $this->assertSame(5, substr_count($json->json('html'), 'class="rn-m-card"'));
        $this->assertNotContains('Own-INV-25', array_column($json->json('items'), 'subtitle'), 'Newest first: page 2 holds the oldest.');

        // Filters travel with the endpoint, like the page.
        $paid = $this->getJson(route('m.lists', ['list' => 'sales', 'status' => 'paid']))->assertOk();
        $this->assertSame(24, $paid->json('total'));
    }

    public function test_json_never_contains_another_business(): void
    {
        $admin = $this->actor('admin');
        $other = $this->actor('admin', 2);
        $this->seedLists(1, 'Own', $admin);
        $this->seedLists(2, 'Foreign', $other);
        $this->actingAs($admin, 'admin');

        foreach (['sales', 'products', 'customers', 'suppliers', 'purchases', 'purchase-lines', 'expenses'] as $list) {
            $json = $this->getJson(route('m.lists', ['list' => $list]))->assertOk();
            $this->assertGreaterThan(0, $json->json('total'), $list);
            $this->assertStringNotContainsString('Foreign', json_encode($json->json('items'), JSON_UNESCAPED_UNICODE), "$list stays in the user's business");
            $this->assertStringNotContainsString('Foreign', (string) $json->json('html'), $list);
        }
        foreach (array_keys(self::PAGES) as $route) {
            $this->assertStringNotContainsString('Foreign', $this->onPhone()->get(route($route))->getContent(), $route);
        }
        $this->getJson(route('m.lists', ['list' => 'unknown']))->assertNotFound();

        auth('admin')->logout();
        $this->getJson(route('m.lists', ['list' => 'sales']))->assertUnauthorized();
    }

    public function test_filters_work_like_the_desktop(): void
    {
        $admin = $this->actor('admin');
        $own = $this->seedLists(1, 'Own', $admin);
        $this->actingAs($admin, 'admin');
        $total = fn (string $list, array $query) => $this->getJson(route('m.lists', ['list' => $list] + $query))->assertOk()->json('total');

        // Sales: search, payment status, payment method and dates.
        $this->assertSame(1, $total('sales', ['q' => 'Own-INV']));
        $this->assertSame(1, $total('sales', ['q' => 'Own Customer']));
        $this->assertSame(0, $total('sales', ['q' => 'nobody']));
        $this->assertSame(1, $total('sales', ['status' => 'partial']));
        $this->assertSame(0, $total('sales', ['status' => 'paid']));
        $this->assertSame(1, $total('sales', ['method' => 'bkash']));
        $this->assertSame(0, $total('sales', ['method' => 'nagad']));
        $this->assertSame(1, $total('sales', ['from' => now()->toDateString(), 'to' => now()->toDateString()]));
        $this->assertSame(0, $total('sales', ['from' => now()->addDay()->toDateString()]));
        $this->assertSame(1, $total('sales', ['method' => 'not-a-method']), 'Unknown values are ignored.');

        // Products: stock status against the alert quantity (fixture: 3 in stock, alert 5).
        $this->assertSame(1, $total('products', ['stock' => 'low']));
        $this->assertSame(0, $total('products', ['stock' => 'out']));
        $this->assertSame(0, $total('products', ['stock' => 'in']));
        $own['inventory']['stock']->update(['currentStock' => 0]);
        $this->assertSame(1, $total('products', ['stock' => 'out']));

        // Customers and suppliers: due.
        $this->assertSame(1, $total('customers', ['due' => 'with']));
        Customer::forceCreate(['name' => 'Settled Customer', 'businessId' => 1]);
        $this->assertSame(1, $total('customers', ['due' => 'none']));
        $this->assertSame(1, $total('suppliers', ['due' => 'with']));

        // Purchases and expenses.
        $this->assertSame(1, $total('purchases', ['status' => 'partial']));
        $this->assertSame(0, $total('purchases', ['status' => 'paid']));
        $this->assertSame(1, $total('purchases', ['supplier' => $own['supplier']->id]));
        $this->assertSame(1, $total('expenses', ['method' => 'cash', 'category' => $own['category']->id]));
        $this->assertSame(0, $total('expenses', ['method' => 'card']));

        // The page reads the same query: chips and the filter sheet show the current values.
        $page = $this->xpath($this->onPhone()->get(route('saleList', ['method' => 'bkash', 'from' => now()->toDateString(), 'to' => now()->toDateString()]))->getContent());
        $this->assertCount(1, $page->query('//select[@name="method"]/option[@value="bkash" and @selected]'));
        $this->assertCount(1, $page->query('//nav[contains(@class,"rn-m-periods")]/a[@data-rn-period="today" and @aria-current="true"]'));
        $this->assertSame('1', trim($page->query('//*[contains(@class,"rn-m-filter-count")]')->item(0)->textContent));
        $this->assertCount(4, $page->query('//nav[contains(@class,"rn-m-periods")]/a'));
        $this->assertCount(1, $page->query('//*[@data-rn-sheet and @role="dialog"]//form[@method="GET"]'));
    }

    public function test_card_menus_hold_single_actions_by_permission(): void
    {
        $admin = $this->actor('admin');
        $own = $this->seedLists(1, 'Own', $admin);
        $this->actingAs($admin, 'admin');

        $sales = $this->xpath($this->onPhone()->get(route('saleList'))->getContent());
        $card = $sales->query('//li[contains(@class,"rn-m-card")]')->item(0);
        $this->assertSame(route('invoiceGenerate', ['id' => $own['sale']->id]), $sales->query('./a[contains(@class,"rn-m-card-main")]', $card)->item(0)->getAttribute('href'), 'Tapping the card opens the sale.');
        $this->assertCount(1, $sales->query('.//button[@data-rn-card-menu and @aria-haspopup="menu"]', $card));
        $this->assertCount(1, $sales->query('.//a[@role="menuitem" and @href="'.route('sale.edit', ['id' => $own['sale']->id]).'"]', $card));
        $cancel = $sales->query('.//form[@data-cancel-sale-form and @action="'.route('delSale', ['id' => $own['sale']->id]).'"]', $card)->item(0);
        $this->assertNotNull($cancel, 'Admins may cancel a sale.');
        $this->assertCount(1, $sales->query('.//input[@name="_method" and @value="DELETE"]', $cancel));
        $this->assertCount(1, $sales->query('.//input[@name="reason"]', $cancel));
        $this->assertCount(1, $sales->query('//script[contains(.,"rnAskSaleCancelReason")]'), 'The reason prompt comes with the page.');

        $customers = $this->xpath($this->onPhone()->get(route('addCustomer'))->getContent());
        $this->assertCount(1, $customers->query('//a[@role="menuitem" and starts-with(@href,"tel:")]'));
        $this->assertCount(1, $customers->query('//form[@action="'.route('delCustomer', ['id' => $own['customer']->id]).'"]//input[@name="deleteType" and @value="fullDelete"]'));

        // A sales manager may sell but not change the catalog or cancel sales.
        $seller = $this->actor('salesmanager');
        SaleProduct::query()->update(['salespersonId' => $seller->id]);
        $this->actingAs($seller, 'admin');
        $sellerSales = $this->xpath($this->onPhone()->get(route('saleList'))->getContent());
        $this->assertCount(1, $sellerSales->query('//li[contains(@class,"rn-m-card")]'));
        $this->assertCount(0, $sellerSales->query('//form[@data-cancel-sale-form]'));
        $products = $this->xpath($this->onPhone()->get(route('productlist'))->getContent());
        $this->assertCount(0, $products->query('//li[contains(@class,"rn-m-card")]//button[@data-rn-card-menu]'));
        $this->assertCount(0, $products->query('//li[contains(@class,"rn-m-card")]/a'), 'No product form to open.');
        $customersForSeller = $this->xpath($this->onPhone()->get(route('addCustomer'))->getContent());
        $this->assertCount(0, $customersForSeller->query('//input[@name="deleteType" and @value="fullDelete"]'));

        // Only the platform owner sees other salespeople's bills, as on the desktop.
        $this->actingAs($admin, 'admin');
        $this->assertSame(0, $this->getJson(route('m.lists', ['list' => 'sales']))->json('total'));

        // Store managers have no expenses, on either version.
        $this->actingAs($this->actor('storemanager'), 'admin');
        $this->onPhone()->get(route('expense.list'))->assertForbidden();
        $this->getJson(route('m.lists', ['list' => 'expenses']))->assertForbidden();
    }

    public function test_mobile_contact_form_uses_the_desktop_route(): void
    {
        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');

        $new = $this->xpath($this->onPhone()->get(route('addCustomer', ['new' => 1]))->assertOk()->getContent());
        $form = $new->query('//form[@data-rn-contact-form="customers"]')->item(0);
        $this->assertSame(route('saveCustomer'), $form->getAttribute('action'));
        foreach (['_token', 'profileId', 'fullName', 'mobile', 'mail', 'openingBalance', 'area', 'city', 'state', 'country'] as $field) {
            $this->assertCount(1, $new->query('.//*[@name="'.$field.'"]', $form), $field);
        }
        $this->assertCount(2, $new->query('.//section[contains(@class,"rn-m-form-section")]', $form));
        $this->assertCount(1, $new->query('.//*[contains(@class,"rn-m-sticky-actions")]//button[@type="submit"]', $form));
        $this->assertCount(1, $new->query('.//*[contains(@class,"rn-m-sticky-actions")]//a[@href="'.route('addCustomer').'"]', $form));

        $this->onPhone()->post(route('saveCustomer'), ['fullName' => 'Phone Customer', 'mobile' => '01812345678'])->assertRedirect(route('addCustomer'));
        $customer = Customer::query()->where('name', 'Phone Customer')->firstOrFail();
        $this->assertSame(1, (int) $customer->businessId);

        $edit = $this->xpath($this->onPhone()->get(route('editCustomer', ['id' => $customer->id]))->assertOk()->getContent());
        $this->assertSame('Phone Customer', $edit->query('//input[@name="fullName"]')->item(0)->getAttribute('value'));
        $this->assertSame((string) $customer->id, $edit->query('//input[@name="profileId"]')->item(0)->getAttribute('value'));

        // Validation errors come back to the mobile form.
        $errors = $this->xpath($this->onPhone()->from(route('addSupplier', ['new' => 1]))->followingRedirects()
            ->post(route('saveSupplier'), ['fullName' => ''])->getContent());
        $this->assertCount(1, $errors->query('//form[@data-rn-contact-form="suppliers"]//*[contains(@class,"rn-m-field-error")]'));
    }

    public function test_components_use_tokens_only(): void
    {
        $css = file_get_contents(public_path('css/rn-m-components.css'));
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $css);
        $this->assertStringContainsString('var(--rn-color-primary)', $css);
        foreach (['list-card', 'summary-bar', 'period-chips', 'filter-sheet', 'empty-state', 'load-more', 'fab', 'form-section', 'field', 'sticky-actions'] as $component) {
            $this->assertFileExists(resource_path('views/components/m/'.$component.'.blade.php'));
        }
    }
}
