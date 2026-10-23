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
        $this->actingAs($this->actor('admin'), 'admin');

        $xpath = $this->xpath($this->get(route('dashboard'))->assertOk()->getContent());

        $tabs = $xpath->query('//nav[@data-rn-tabbar]//a[@data-rn-tab]');
        $this->assertSame(['home', 'stock', 'sale', 'reports', 'more'], array_map(fn ($a) => $a->getAttribute('data-rn-tab'), iterator_to_array($tabs)));
        $this->assertSame(route('newsale'), $xpath->query('//a[@data-rn-tab="sale" and contains(@class,"rn-m-tab--sale")]')->item(0)->getAttribute('href'));
        $this->assertSame('page', $xpath->query('//a[@data-rn-tab="home"]')->item(0)->getAttribute('aria-current'));
        $this->assertSame('#rn-more', $xpath->query('//a[@data-rn-tab="more"]')->item(0)->getAttribute('href'));

        $this->assertCount(1, $xpath->query('//*[@id="rn-more" and @role="dialog"]'));
        $grid = $this->hrefs($xpath, '//*[@id="rn-more"]//ul[contains(@class,"rn-m-grid")]//a');
        $this->assertNotEmpty($grid);
        $this->assertNotContains(route('newsale'), $grid, 'Destinations in the tab bar are not repeated on the More page.');
        $this->assertCount(2, $xpath->query('//*[@id="rn-more"]//form[@action="'.route('language.preference').'"]//button[@name="preferred_locale"]'));
        $this->assertCount(1, $xpath->query('//*[@id="rn-more"]//a[@href="'.route('logout').'"]'));

        // Only destinations that exist in this installation are offered.
        foreach (array_merge($grid, $this->hrefs($xpath, '//*[@id="rn-more"]//ul[contains(@class,"rn-m-list")]//a')) as $href) {
            $route = Route::getRoutes()->match(Request::create($href, 'GET'));
            $this->assertNotNull($route->getName(), $href);
        }
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

    public function test_tokens_and_manifest_use_the_new_palette(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertSame('#14736F', $manifest['theme_color']);

        $tokens = file_get_contents(public_path('css/rn-tokens.css'));
        foreach (['--rn-color-primary: #14736F', '--rn-color-primary-tint: #E2F2F1', '--rn-color-bg: #F4F5F7', '--rn-color-ink: #1B1F27', 'Hind Siliguri'] as $token) {
            $this->assertStringContainsString($token, $tokens);
        }
        // Components use the tokens, not raw colors.
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', file_get_contents(public_path('css/rn-mobile.css')));
    }
}
