<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SaleProduct;
use App\Support\LocalizedNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsSaleFixtures;
use Tests\TestCase;

/** UI-1: mobile app shell, home figures, mobile sale screen and Bangla/English pages. */
class MobileShellTest extends TestCase
{
    use BuildsSaleFixtures;
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    // Never migrate or seed a configured development/production database.
    public function refreshDatabase()
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Mobile shell tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();
        // UI-2: the mobile shell is only sent to phones and tablets (App\Support\MobileView).
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1');
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new \DOMXPath($document);
    }

    private function hrefs(\DOMXPath $xpath, string $query): array
    {
        return array_map(fn ($node) => $node->getAttribute('href'), iterator_to_array($xpath->query($query)));
    }

    public function test_bottom_navigation_and_more_page_are_rendered(): void
    {
        $this->inventory();
        \App\Models\BusinessSetup::whereKey(1)->update(['businessType' => 'computer_shop']);
        $this->actingAs($this->actor('admin'), 'admin');

        $xpath = $this->xpath($this->get(route('dashboard'))->assertOk()->getContent());

        $tabs = $xpath->query('//nav[@data-rn-tabbar]//a[@data-rn-tab]');
        $this->assertSame(['home', 'stock', 'sale', 'reports', 'more'], array_map(fn ($a) => $a->getAttribute('data-rn-tab'), iterator_to_array($tabs)));
        $this->assertSame(route('newsale'), $xpath->query('//a[@data-rn-tab="sale" and contains(@class,"rn-m-tab--sale")]')->item(0)->getAttribute('href'));
        $this->assertSame(route('reports.index'), $xpath->query('//a[@data-rn-tab="reports"]')->item(0)->getAttribute('href'));
        $this->assertSame('page', $xpath->query('//a[@data-rn-tab="home"]')->item(0)->getAttribute('aria-current'));
        $this->assertSame('#rn-more', $xpath->query('//a[@data-rn-tab="more"]')->item(0)->getAttribute('href'));

        $this->assertCount(1, $xpath->query('//*[@id="rn-more" and @role="dialog"]'));
        $grid = $this->hrefs($xpath, '//*[@id="rn-more"]//ul[contains(@class,"rn-m-grid")]//a');
        $this->assertNotEmpty($grid);
        $this->assertNotContains(route('newsale'), $grid, 'Destinations in the tab bar are not repeated on the More page.');
        $this->assertCount(count(array_unique($grid)), $grid, 'Each destination appears once.');
        $tabHrefs = $this->hrefs($xpath, '//nav[@data-rn-tabbar]//a[@data-rn-tab and @href!="#rn-more"]');
        $shortcutHrefs = $this->hrefs($xpath, '//*[@data-rn-home]//a[contains(@class,"rn-m-grid-item")]');
        foreach ($tabHrefs as $href) {
            $this->assertNotContains($href, $grid, 'Bottom-tab destinations are not repeated in More.');
            $this->assertNotContains($href, $shortcutHrefs, 'Bottom-tab destinations are not repeated in Home shortcuts.');
        }
        $this->assertNotContains(route('stockProduct'), $grid, 'Stock stays in the bottom tab, not the More page.');
        $this->assertNotContains(route('productlist'), $grid, 'The product-list alias does not duplicate the Stock tab.');
        $expectedHomeRoutes = ['addPurchase', 'addCustomer', 'expense.list', 'account.transactions', 'addSupplier', 'returnSaleList', 'quotation.list', 'damageProductList'];
        $this->assertSame(array_map(fn ($name) => route($name), $expectedHomeRoutes), $shortcutHrefs, 'Home presents the approved eight destinations in order.');
        foreach ($expectedHomeRoutes as $routeName) {
            $this->assertNotContains(route($routeName), $tabHrefs, "$routeName is not duplicated from the bottom navigation.");
        }
        foreach (['addPurchase', 'quotation.create', 'addProduct', 'damageProduct', 'provideService', 'expense.create'] as $routeName) {
            $this->assertNotContains(route($routeName), $grid, "$routeName is exposed by a list FAB rather than duplicated in More.");
        }
        $this->assertCount(2, $xpath->query('//*[@id="rn-more"]//form[@action="'.route('language.preference').'"]//button[@name="preferred_locale"]'));
        $this->assertCount(1, $xpath->query('//*[@id="rn-more"]//a[@href="'.route('logout').'"]'));

        // Only destinations that exist in this installation are offered.
        foreach ($grid as $href) {
            $route = Route::getRoutes()->match(Request::create($href, 'GET'));
            $this->assertNotNull($route->getName(), $href);
        }
    }

    public function test_more_page_is_grouped_into_sections(): void
    {
        $this->inventory();
        $admin = $this->actor('admin');
        $admin->update(['preferred_locale' => 'bn']);
        $this->actingAs($admin, 'admin');
        $xpath = $this->xpath($this->get(route('dashboard'))->assertOk()->getContent());

        $sections = iterator_to_array($xpath->query('//*[@id="rn-more"]//section[@data-rn-more-section]'));
        $this->assertSame(['sale', 'purchase', 'inventory', 'people', 'finance', 'settings'], array_map(fn ($s) => $s->getAttribute('data-rn-more-section'), $sections));
        $this->assertSame(
            ['বিক্রি', 'কেনা', 'পণ্য ও স্টক', 'কাস্টমার ও মহাজন', 'হিসাব ও খরচ', 'সেটিংস'],
            array_map(fn ($s) => trim($xpath->query('.//h3', $s)->item(0)->textContent), $sections),
        );
        foreach ($sections as $section) {
            $this->assertCount(1, $xpath->query('./ul[contains(@class,"rn-m-grid")]', $section), 'Each section is a title and one grid.');
            $this->assertGreaterThan(0, $xpath->query('./ul/li/a', $section)->length);
        }

        $in = fn (string $key, string $route) => $xpath->query('//section[@data-rn-more-section="'.$key.'"]//a[@href="'.route($route).'"]')->length;
        foreach ([
            'sale' => ['saleList', 'returnSaleList'], 'purchase' => ['purchaseList', 'returnPurchaseList'],
            'inventory' => ['addCategory', 'damageProductList'], 'people' => ['addCustomer', 'addSupplier'],
            'finance' => ['account.chart', 'expense.list'],
            'settings' => ['addBusinessSetupPage'],
        ] as $key => $routes) {
            foreach ($routes as $route) {
                $this->assertSame(1, $in($key, $route), "$route belongs under $key");
            }
        }
        $this->assertSame(0, $in('people', 'reports.payableReceivable'), 'Due reporting is presented from the customer filter and report hub, not a duplicate More route.');
        $this->assertSame(0, $in('inventory', 'productlist'), 'Stock catalog alias is not repeated in More.');
        $this->assertSame('কাস্টমার ও বাকি', trim($xpath->query('//section[@data-rn-more-section="people"]//a[@href="'.route('addCustomer').'"]')->item(0)->textContent));
    }

    public function test_reports_tab_is_the_single_mobile_entry_for_report_destinations(): void
    {
        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $xpath = $this->xpath($this->get(route('dashboard'))->assertOk()->getContent());
        $this->assertSame(1, $xpath->query('//nav[@data-rn-tabbar]//a[@data-rn-tab="reports" and @href="'.route('reports.index').'"]')->length);
        $grid = $this->hrefs($xpath, '//*[@id="rn-more"]//ul[contains(@class,"rn-m-grid")]//a');
        foreach (['reports.business', 'reports.sales', 'reports.purchases', 'reports.stock', 'reports.topCustomers', 'reports.payableReceivable', 'account.reports', 'expense.reports'] as $name) {
            $this->assertNotContains(route($name), $grid, "$name belongs under Reports.");
        }
        $hub = $this->get(route('reports.index'))->assertOk();
        foreach (['reports.business', 'reports.sales', 'reports.purchases', 'reports.stock', 'reports.topCustomers', 'reports.payableReceivable', 'account.reports', 'expense.reports'] as $name) {
            $hub->assertSee(route($name), false);
        }
        $mobileHub = $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile')
            ->get(route('reports.index'))->assertOk()->assertSee('rn-m-report-hub-row', false);
        $mobileXpath = $this->xpath($mobileHub->getContent());
        $this->assertGreaterThan(0, $mobileXpath->query('//main[contains(@class,"rn-m-report-hub")]//a[contains(@class,"rn-m-report-hub-row")]')->length);
        $this->assertSame(0, $mobileXpath->query('//main[contains(@class,"rn-m-report-hub")]//table')->length);
    }

    public function test_home_header_shows_brand_date_and_notifications(): void
    {
        $inventory = $this->inventory();
        $admin = $this->actor('admin');
        $admin->update(['preferred_locale' => 'bn']);
        $this->actingAs($admin, 'admin');

        $header = function () {
            $xpath = $this->xpath($this->get(route('dashboard'))->assertOk()->getContent());

            return [$xpath, $xpath->query('//header[@data-rn-mobile-header]')->item(0)];
        };

        // No logo uploaded: the first letter of the shop name stands in.
        [$xpath, $head] = $header();
        $this->assertSame('T', trim($xpath->query('.//*[contains(@class,"rn-m-brand")]', $head)->item(0)->textContent));
        $this->assertSame('Test Business 1', trim($xpath->query('.//h1', $head)->item(0)->textContent));
        $date = $xpath->query('.//*[@data-rn-header-date]', $head)->item(0)->textContent;
        $this->assertStringContainsString(\App\Support\LocalizedNumber::digits(now()->format('Y'), 'bn'), $date);
        $this->assertStringContainsString(now()->locale('bn')->translatedFormat('F'), $date);
        $bell = $xpath->query('.//button[@data-rn-notifications-toggle]', $head)->item(0);
        $this->assertSame('rnNotifications', $bell->getAttribute('aria-controls'));
        $this->assertSame(0, $xpath->query('.//*[contains(@class,"rn-m-bell-badge")]', $head)->length);
        $this->assertStringContainsString(__('mobile.no_notifications', [], 'bn'), $xpath->query('//*[@id="rnNotifications"]')->item(0)->textContent);
        $this->assertCount(0, $xpath->query('.//a[@data-rn-back]', $head), 'Home has no back button.');

        // A stock warning shows up behind the bell.
        $inventory['product']->forceFill(['quantity' => 5])->save();
        [$xpath, $head] = $header();
        $this->assertSame('১', trim($xpath->query('.//*[contains(@class,"rn-m-bell-badge")]', $head)->item(0)->textContent));
        $this->assertCount(1, $xpath->query('//*[@id="rnNotifications"]//a[@href="'.route('stockProduct').'"]'));

        // An uploaded logo replaces the letter.
        $logo = 'rn-test-logo-'.uniqid().'.png';
        @mkdir(public_path('uploads/business'), 0777, true);
        file_put_contents(public_path('uploads/business/'.$logo), base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        try {
            \App\Models\BusinessSetup::query()->whereKey(1)->update(['businessLogo' => $logo]);
            [$xpath, $head] = $header();
            $this->assertStringEndsWith($logo, strtok($xpath->query('.//img[contains(@class,"rn-m-brand-logo")]', $head)->item(0)->getAttribute('src'), '?'));
        } finally {
            @unlink(public_path('uploads/business/'.$logo));
        }

        // Other pages keep the back button and page title.
        $page = $this->xpath($this->get(route('saleList'))->getContent());
        $this->assertCount(1, $page->query('//header[@data-rn-mobile-header]//a[@data-rn-back]'));
        $this->assertCount(0, $page->query('//*[@data-rn-notifications-toggle]'));
    }

    public function test_menus_follow_the_users_permissions(): void
    {
        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $admin = $this->xpath($this->get(route('dashboard'))->getContent());
        $this->assertCount(1, $admin->query('//*[@id="rn-more"]//a[@href="'.route('account.chart').'"]'));

        $this->actingAs($this->actor('salesmanager'), 'admin');
        $seller = $this->xpath($this->get(route('dashboard'))->getContent());
        $this->assertCount(0, $seller->query('//*[@id="rn-more"]//a[@href="'.route('account.chart').'"]'), 'Finance menus stay hidden.');
        $this->assertCount(0, $seller->query('//a[@data-rn-tab="reports" and @href="'.route('reports.business').'"]'));
        $this->assertCount(0, $seller->query('//*[@data-rn-home]//a[@href="'.route('expense.list').'"]'), 'Shortcuts follow permissions too.');
    }

    public function test_home_shows_only_the_users_own_business(): void
    {
        $this->inventory();
        $this->inventory(2);
        $foreignCustomer = Customer::forceCreate(['name' => 'Foreign Buyer', 'businessId' => 2]);
        SaleProduct::forceCreate([
            'invoice' => 'INV-FOREIGN-1', 'customerId' => $foreignCustomer->id, 'businessId' => 2, 'date' => now()->toDateString(),
            'totalSale' => 7777, 'grandTotal' => 7777, 'paidAmount' => 7000, 'invoiceDue' => 777, 'curDue' => 777,
        ]);

        foreach (['admin', 'superadmin'] as $role) {
            $this->actingAs($this->actor($role), 'admin');
            $own = $this->inventory();
            $customer = Customer::create(['name' => 'Own Buyer '.$role, 'businessId' => 1]);
            $this->sell($own, 1, false, $customer);

            $page = $this->get(route('dashboard'))->assertOk();
            $home = $this->xpath($page->getContent())->query('//*[@data-rn-home]')->item(0);
            $this->assertNotNull($home);
            $text = $home->textContent;
            $this->assertStringContainsString('Own Buyer '.$role, $text);
            $this->assertStringNotContainsString('Foreign Buyer', $text, $role.' must not see another business.');
            $this->assertStringNotContainsString('INV-FOREIGN-1', $text);
            $this->assertStringNotContainsString('7,777', $text);
        }
    }

    public function test_pages_open_in_bangla_and_english(): void
    {
        $this->inventory();
        $admin = $this->actor('admin');
        $this->actingAs($admin, 'admin');
        $pages = ['dashboard', 'newsale', 'saleList', 'productlist', 'stockProduct', 'purchaseList', 'addCustomer', 'addSupplier', 'expense.list', 'returnSaleList'];

        foreach (['bn' => ['বিক্রি', 'আরও', 'হোম'], 'en' => ['Sale', 'More', 'Home']] as $locale => $labels) {
            $admin->update(['preferred_locale' => $locale]);
            foreach ($pages as $page) {
                $response = $this->get(route($page))->assertOk()->assertSee('lang="'.$locale.'"', false);
                foreach ($labels as $label) {
                    $response->assertSee($label);
                }
                if ($locale === 'bn') {
                    $this->assertStringNotContainsString('ক্রয়', $response->getContent(), "$page uses the agreed Bangla words");
                    $this->assertStringNotContainsString('বেচা', $response->getContent(), $page);
                }
            }
        }
    }

    public function test_bangla_home_and_sale_screen_show_bengali_digits(): void
    {
        $inventory = $this->inventory();
        $admin = $this->actor('admin');
        $this->actingAs($admin, 'admin');
        $this->sell($inventory, 1);
        $admin->update(['preferred_locale' => 'bn']);

        $home = $this->xpath($this->get(route('dashboard'))->getContent())->query('//*[@data-rn-home]//p[contains(@class,"rn-m-hero-amount")]')->item(0)->textContent;
        $this->assertMatchesRegularExpression('/[০-৯]/u', $home);
        $this->assertDoesNotMatchRegularExpression('/[0-9]/', $home);

        $stock = $this->xpath($this->get(route('newsale'))->getContent())->query('//*[@data-rn-pos]//*[contains(@class,"rn-m-product-stock")]')->item(0)->textContent;
        $this->assertStringContainsString('২', $stock);
        $this->assertSame('১২৩', LocalizedNumber::digits('123', 'bn'));
        $this->assertSame('123', LocalizedNumber::digits('123', 'en'));
    }

    public function test_mobile_sale_screen_adds_no_post_fields(): void
    {
        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $xpath = $this->xpath($this->get(route('newsale'))->assertOk()->getContent());

        $this->assertCount(1, $xpath->query('//*[@data-rn-pos]'));
        $this->assertCount(0, $xpath->query('//*[@id="saveSaleForm"]//*[@data-rn-pos]'), 'The mobile layer sits outside the sale form.');
        $this->assertCount(0, $xpath->query('//*[@data-rn-pos]//*[@name and @name!="rnPaymentMethod"]'), 'Mobile controls carry no field names.');
        $this->assertCount(0, $xpath->query('//*[@data-rn-pos]//form'));
        $this->assertGreaterThan(0, $xpath->query('//*[@data-rn-pos]//button[@data-product-id]')->length);
        $this->assertCount(1, $xpath->query('//*[@data-rn-pos-bar]//button[@data-rn-pos-checkout]'));
        $this->assertCount(4, $xpath->query('//*[@data-rn-checkout]//input[@data-rn-checkout-method]'));
        $this->assertCount(1, $xpath->query('//*[@data-rn-checkout]//button[@data-rn-checkout-complete]'));
    }

    public function test_sale_bar_is_one_button_and_payment_method_goes_through_the_form(): void
    {
        $this->inventory();
        $admin = $this->actor('admin');
        $admin->update(['preferred_locale' => 'bn']);
        $this->actingAs($admin, 'admin');
        $xpath = $this->xpath($this->get(route('newsale'))->assertOk()->getContent());

        // "Nটি · ৳মোট · টাকা নিন →": the whole bar is the button, disabled while the cart is empty.
        $bar = $xpath->query('//*[@data-rn-pos-bar]')->item(0);
        $this->assertSame(1, $xpath->query('./*', $bar)->length);
        $button = $xpath->query('./button[@data-rn-pos-checkout]', $bar)->item(0);
        $this->assertTrue($button->hasAttribute('disabled'));
        foreach (['data-rn-pos-count', 'data-rn-pos-total'] as $part) {
            $this->assertCount(1, $xpath->query('.//*[@'.$part.']', $button));
        }
        $this->assertStringContainsString('০টি', $button->textContent);
        $this->assertStringContainsString('টাকা নিন', $button->textContent);
        $this->assertCount(1, $xpath->query('.//i[contains(@class,"ri-arrow-right-line")]', $button));

        // One hidden payment_method field (default cash), written by the desktop select and the mobile segment.
        $this->assertCount(1, $xpath->query('//*[@name="payment_method"]'));
        $this->assertCount(1, $xpath->query('//form[@id="saveSaleForm"]//input[@type="hidden" and @id="paymentMethod" and @name="payment_method" and @value="cash"]'));
        $select = $xpath->query('//form[@id="saveSaleForm"]//select[@id="paymentMethodSelect"]')->item(0);
        $this->assertFalse($select->hasAttribute('name'), 'The select only mirrors into the hidden field.');
        $this->assertSame(['cash', 'bkash', 'nagad', 'due'], array_map(fn ($o) => $o->getAttribute('value'), iterator_to_array($xpath->query('./option', $select))));
        $this->assertSame('cash', $xpath->query('./option[@selected]', $select)->item(0)->getAttribute('value'));
        $this->assertSame(['ক্যাশ', 'বিকাশ', 'নগদ', 'বাকি'], array_map(fn ($l) => trim($l->textContent), iterator_to_array($xpath->query('//*[@data-rn-checkout]//label[contains(@class,"rn-m-segment-btn")]'))));
        $this->assertStringContainsString('পুরো টাকা', $xpath->query('//*[@data-rn-checkout]//button[@data-rn-quick="exact"]')->item(0)->textContent);
    }

    public function test_bangla_wording_is_consistent(): void
    {
        // One word per concept: কাস্টমার, কোটেশন, ক্যাটাগরি, নষ্ট পণ্য, পুরো টাকা, পেমেন্টের ধরন.
        $retired = ['/(?<!বি)ক্রেতা/u', '/গ্রাহক/u', '/মূল্যপ্রস্তাব/u', '/বিভাগ/u', '/ক্ষতির নথি/u', '/ক্ষতিগ্রস্ত/u', '/ঠিক টাকা/u', '/পরিশোধ পদ্ধতি/u'];
        foreach (glob(lang_path('bn/*.php')) as $file) {
            $text = file_get_contents($file);
            foreach ($retired as $pattern) {
                $this->assertDoesNotMatchRegularExpression($pattern, $text, basename($file));
            }
        }
        $this->assertSame('বিক্রেতা', __('sales.salesperson', [], 'bn'), 'Salesperson keeps its own word.');
        $this->assertSame('নষ্ট পণ্য যোগ করুন', __('navigation.record_damage', [], 'bn'));
        $this->assertSame('কোটেশন', __('common.quotation', [], 'bn'));
        $this->assertSame('ক্যাটাগরি', __('common.category', [], 'bn'));
    }

    public function test_mobile_money_uses_grouped_localized_digits_and_only_keeps_real_fractional_digits(): void
    {
        $this->assertSame('৳1,640', LocalizedNumber::money(1640, 'en'));
        $this->assertSame('৳১,৬৪০', LocalizedNumber::money(1640, 'bn'));
        $this->assertSame('৳১,৬৪০.৫', LocalizedNumber::money('1640.50', 'bn'));
        $this->assertSame('৳1,640.25', LocalizedNumber::money('1640.25', 'en'));
    }

    public function test_fonts_and_libraries_are_served_locally(): void
    {
        // Every @font-face source in the tokens resolves to a file in public/fonts.
        $tokens = file_get_contents(public_path('css/rn-tokens.css'));
        preg_match_all('~url\("\.\./(fonts/[^"]+)"\)~', $tokens, $fonts);
        $this->assertNotEmpty($fonts[1]);
        foreach ($fonts[1] as $font) {
            $this->assertFileExists(public_path($font));
        }
        foreach (['Regular', 'Medium', 'SemiBold', 'Bold'] as $weight) {
            $this->assertContains('fonts/hind-siliguri/HindSiliguri-'.$weight.'.woff2', $fonts[1]);
        }
        $this->assertFileExists(public_path('fonts/hind-siliguri/OFL.txt'));

        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');
        $html = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString(asset('vendor/jquery/jquery-3.6.0.min.js').'?v=', $html);
        $this->assertStringContainsString(asset('vendor/sweetalert/sweetalert.all.js').'?v=', $html);
        foreach (['ajax.googleapis.com', 'cdn.jsdelivr.net/npm/sweetalert2', 'unpkg.com'] as $cdn) {
            $this->assertStringNotContainsString($cdn, $html);
        }
    }

    public function test_tokens_and_manifest_use_the_new_palette(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertSame('#14736F', $manifest['theme_color']);

        $tokens = file_get_contents(public_path('css/rn-tokens.css'));
        foreach (['--rn-color-primary: #14736F', '--rn-color-primary-tint: #E2F2F1', '--rn-color-bg: #F4F5F7', '--rn-color-ink: #1B1F27', 'Hind Siliguri'] as $token) {
            $this->assertStringContainsString($token, $tokens);
        }
        foreach ([
            '--rn-type-page-title: 21px', '--rn-type-summary-amount: 30px', '--rn-type-card-amount: 18px',
            '--rn-type-card-name: 17px', '--rn-type-body: 16px', '--rn-type-label: 15px', '--rn-type-helper: 14px',
            '--rn-type-tag: 13.5px', '--rn-type-bottom-label: 13px', '--rn-type-button: 17px',
            '--rn-icon-header: 24px', '--rn-icon-tab: 26px', '--rn-icon-menu: 28px', '--rn-icon-avatar: 44px',
        ] as $token) {
            $this->assertStringContainsString($token, $tokens);
        }
        foreach (['rn-mobile.css', 'rn-m-components.css'] as $file) {
            $mobileCss = file_get_contents(public_path('css/'.$file));
            $this->assertDoesNotMatchRegularExpression('/font-size:\s*(?:[0-9]|1[0-2])(?:\.\d+)?(?:px|rem|em)\b/i', $mobileCss, "$file keeps mobile text at 13px or larger.");
        }
        // Components use the tokens, not raw colors.
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', file_get_contents(public_path('css/rn-mobile.css')));
    }
}
