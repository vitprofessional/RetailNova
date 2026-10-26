<?php

namespace App\Support;

use App\Services\BusinessNavigation;
use Illuminate\Support\Facades\Route;

/**
 * Builds the mobile app shell (bottom tabs, "More" page, home shortcuts) from the same
 * permission-aware menu as the desktop sidebar. A destination is offered only when the
 * signed-in user's menu contains it and the route exists in this installation.
 */
class MobileNavigation
{
    /** Remix Icon (served locally) for each destination; anything else gets a generic icon. */
    private const ICONS = [
        'dashboard' => 'ri-home-5-line', 'newsale' => 'ri-shopping-cart-2-line', 'saleList' => 'ri-file-list-3-line',
        'quotation.create' => 'ri-file-add-line', 'quotation.list' => 'ri-file-copy-2-line',
        'addPurchase' => 'ri-shopping-bag-3-line', 'purchaseList' => 'ri-bill-line',
        'productlist' => 'ri-price-tag-3-line', 'addProduct' => 'ri-add-box-line', 'stockProduct' => 'ri-archive-line',
        'addBrand' => 'ri-bookmark-line', 'addCategory' => 'ri-folders-line', 'addProductUnit' => 'ri-ruler-line',
        'barcode.index' => 'ri-barcode-line', 'damageProduct' => 'ri-error-warning-line', 'damageProductList' => 'ri-delete-bin-6-line',
        'addCustomer' => 'ri-team-line', 'addSupplier' => 'ri-truck-line',
        'provideService' => 'ri-tools-line', 'serviceProvideList' => 'ri-history-line', 'addServiceName' => 'ri-list-check',
        'returnSaleList' => 'ri-arrow-go-back-line', 'returnPurchaseList' => 'ri-reply-line', 'rma.index' => 'ri-shield-check-line', 'serialList' => 'ri-barcode-box-line',
        'account.chart' => 'ri-book-2-line', 'account.transactions' => 'ri-safe-2-line', 'account.reports' => 'ri-line-chart-line',
        'expense.create' => 'ri-money-dollar-circle-line', 'expense.list' => 'ri-wallet-3-line', 'expense.categories' => 'ri-price-tag-2-line',
        'expense.reports' => 'ri-bar-chart-box-line', 'audits.index' => 'ri-clipboard-line',
        'reports.business' => 'ri-bar-chart-2-line', 'reports.sales' => 'ri-line-chart-line', 'reports.purchases' => 'ri-stock-line',
        'reports.stock' => 'ri-archive-drawer-line', 'reports.topCustomers' => 'ri-user-star-line', 'reports.payableReceivable' => 'ri-hand-coin-line',
        'addBusinessSetupPage' => 'ri-store-2-line', 'business.locations' => 'ri-map-pin-line', 'sales.settings.edit' => 'ri-equalizer-line',
        'language.settings.edit' => 'ri-translate-2', 'admin.manage.users.index' => 'ri-user-settings-line', 'documentation.index' => 'ri-book-open-line',
    ];

    /** Labels from lang/*\/mobile.php where the app's own vocabulary differs from the sidebar. */
    private const LABELS = [
        'dashboard' => 'mobile.home', 'newsale' => 'mobile.sale', 'saleList' => 'mobile.sale', 'addPurchase' => 'mobile.purchase', 'purchaseList' => 'mobile.purchase_list',
        'stockProduct' => 'mobile.stock_management', 'expense.list' => 'mobile.expense_list', 'account.transactions' => 'mobile.cashbox',
        'reports.payableReceivable' => 'mobile.due_list', 'reports.business' => 'mobile.business_overview', 'addSupplier' => 'mobile.suppliers',
        'productlist' => 'mobile.stock_management', 'addCustomer' => 'mobile.customer_and_due',
        'quotation.list' => 'mobile.quotation',
        'returnSaleList' => 'mobile.returns', 'damageProductList' => 'mobile.damaged_items',
        'provideService' => 'mobile.service', 'serviceProvideList' => 'mobile.service_list',
        'addServiceName' => 'mobile.service_types', 'addProductUnit' => 'mobile.unit',
        'serialList' => 'mobile.serial', 'rma.index' => 'mobile.warranty',
    ];

    /** Home shortcuts in order of preference; the first eight the user may open are shown. */
    private const SHORTCUTS = [
        'addPurchase', 'addCustomer', 'expense.list', 'account.transactions', 'addSupplier', 'returnSaleList',
        'quotation.list', 'damageProductList',
    ];

    /** Creation destinations are exposed by their list FABs, not duplicated in "More". */
    private const FAB_DESTINATIONS = ['addPurchase', 'quotation.create', 'addProduct', 'damageProduct', 'provideService', 'expense.create'];

    /** "More" page sections, in display order. */
    private const SECTIONS = ['sale', 'purchase', 'inventory', 'people', 'finance', 'reports', 'settings'];

    /** Section of each desktop menu group; groups not listed go under settings. */
    private const GROUP_SECTIONS = [
        'sale' => 'sale', 'service' => 'sale', 'warranty' => 'sale', 'parchase' => 'purchase', 'category' => 'inventory',
        'product' => 'people', 'account' => 'finance', 'reports' => 'reports',
    ];

    /** Items filed under a different section than their menu group. */
    private const ROUTE_SECTIONS = ['returnPurchaseList' => 'purchase', 'serialList' => 'inventory'];

    private ?array $items = null;

    public function __construct(private readonly BusinessNavigation $navigation)
    {
    }

    /** Bottom tabs: Home · Stock · [Sale] · Reports · More (each only when allowed). */
    public function tabs(): array
    {
        $tab = fn (string $key, ?string $route, string $icon, string $label, array $active = []) => $route === null ? null : [
            'key' => $key, 'route' => $route, 'url' => route($route), 'icon' => $icon, 'label' => __($label),
            'active' => request()->routeIs(...($active ?: [$route])),
        ];

        return array_values(array_filter([
            $tab('home', $this->first(['dashboard']), 'ri-home-5-line', 'mobile.home'),
            $tab('stock', $this->first(['stockProduct', 'productlist']), 'ri-archive-line', 'mobile.stock', ['stockProduct', 'productlist', 'editProduct']),
            $tab('sale', $this->first(['newsale']), 'ri-shopping-cart-2-line', 'mobile.sale', ['newsale']),
            // The reports tab is a hub so every permitted report is reached from one place.
            $tab('reports', $this->hasAny(['reports.business', 'reports.sales', 'reports.purchases', 'reports.stock', 'reports.topCustomers', 'reports.payableReceivable', 'account.reports', 'expense.reports']) ? 'reports.index' : null, 'ri-bar-chart-2-line', 'mobile.reports', ['reports.*', 'account.reports', 'expense.reports']),
        ]));
    }

    /**
     * Everything else from the user's menu, grouped for the "More" page (a title and a
     * 4-column grid per section). Empty sections are left out.
     */
    public function moreSections(): array
    {
        $inTabs = array_column($this->tabs(), 'route');
        $reportRoutes = ['reports.business', 'reports.sales', 'reports.purchases', 'reports.stock', 'reports.topCustomers', 'reports.payableReceivable', 'account.reports', 'expense.reports'];
        $sections = array_fill_keys(self::SECTIONS, []);
        foreach ($this->items() as $item) {
            if (!in_array($item['route'], $inTabs, true)
                && $item['route'] !== 'productlist'
                && !in_array($item['route'], self::FAB_DESTINATIONS, true)
                && !in_array($item['route'], $reportRoutes, true)) {
                if ($item['route'] === 'addCustomer') {
                    $item['label'] = __('mobile.customer_and_due');
                }
                $sections[$item['section']][] = $item;
            }
        }

        $result = [];
        foreach (array_filter($sections) as $key => $items) {
            $result[] = ['key' => $key, 'title' => __('mobile.section_'.$key), 'items' => $items];
        }

        return $result;
    }

    public function shortcuts(int $limit = 6): array
    {
        $limit = min(6, max(0, $limit));
        $tabRoutes = array_column($this->tabs(), 'route');
        $reportRoutes = ['reports.business', 'reports.sales', 'reports.purchases', 'reports.stock', 'reports.topCustomers', 'reports.payableReceivable', 'account.reports', 'expense.reports'];
        $homeFabDestinations = array_values(array_diff(self::FAB_DESTINATIONS, ['addPurchase']));
        $byRoute = array_column(array_filter($this->items(), fn (array $item) => !in_array($item['route'], $tabRoutes, true)
            && $item['route'] !== 'productlist'
            && !in_array($item['route'], $homeFabDestinations, true) && !in_array($item['route'], $reportRoutes, true)), null, 'route');
        $shortcuts = [];
        foreach (self::SHORTCUTS as $route) {
            if (isset($byRoute[$route]) && count($shortcuts) < $limit) {
                $shortcuts[] = $byRoute[$route];
            }
        }

        return $shortcuts;
    }

    public function allows(string $route): bool
    {
        return in_array($route, array_column($this->items(), 'route'), true);
    }

    private function first(array $routes): ?string
    {
        foreach ($routes as $route) {
            if ($this->allows($route)) {
                return $route;
            }
        }

        return null;
    }

    private function hasAny(array $routes): bool
    {
        foreach ($routes as $route) {
            if ($this->allows($route)) {
                return true;
            }
        }

        return false;
    }

    /** Flattened, de-duplicated menu items the user may open. */
    private function items(): array
    {
        if ($this->items !== null) {
            return $this->items;
        }

        $items = [];
        foreach ($this->navigation->groups() as $group) {
            foreach ($group['items'] as $item) {
                $route = $item['route'];
                if (isset($items[$route]) || !Route::has($route)) {
                    continue;
                }
                $items[$route] = [
                    'route' => $route,
                    'url' => route($route),
                    'icon' => self::ICONS[$route] ?? 'ri-apps-2-line',
                    'label' => __(self::LABELS[$route] ?? 'navigation.'.$item['key']),
                    // The dashboard is always a tab, so its section is never shown.
                    'section' => self::ROUTE_SECTIONS[$route] ?? self::GROUP_SECTIONS[$group['id']] ?? 'settings',
                ];
            }
        }

        return $this->items = array_values($items);
    }
}
