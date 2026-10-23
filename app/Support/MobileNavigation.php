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
        'dashboard' => 'mobile.home', 'newsale' => 'mobile.sale', 'addPurchase' => 'mobile.purchase', 'purchaseList' => 'mobile.purchase_list',
        'stockProduct' => 'mobile.stock_management', 'expense.list' => 'mobile.expense_list', 'account.transactions' => 'mobile.cashbox',
        'reports.payableReceivable' => 'mobile.due_list', 'reports.business' => 'mobile.business_overview', 'addSupplier' => 'mobile.suppliers',
        'returnSaleList' => 'mobile.returns', 'damageProductList' => 'mobile.damaged_items',
    ];

    /** Home shortcuts in order of preference; the first eight the user may open are shown. */
    private const SHORTCUTS = [
        'newsale', 'addPurchase', 'stockProduct', 'reports.payableReceivable', 'expense.list', 'account.transactions',
        'returnSaleList', 'addSupplier', 'purchaseList', 'damageProductList', 'saleList', 'addCustomer', 'productlist',
    ];

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
            $tab('reports', $this->first(['reports.business', 'reports.sales', 'saleList']), 'ri-bar-chart-2-line', 'mobile.reports', ['reports.*']),
        ]));
    }

    /** Everything else from the user's menu, for the 4-column grid on the "More" page. */
    public function moreItems(): array
    {
        $inTabs = array_column($this->tabs(), 'route');

        return array_values(array_filter($this->items(), fn ($item) => !in_array($item['route'], $inTabs, true) && !$item['settings']));
    }

    /** Settings entries of the user's menu, listed under the grid. */
    public function settingsItems(): array
    {
        return array_values(array_filter($this->items(), fn ($item) => $item['settings']));
    }

    public function shortcuts(int $limit = 8): array
    {
        $byRoute = array_column($this->items(), null, 'route');
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
                    'settings' => $group['id'] === 'settings',
                ];
            }
        }

        return $this->items = array_values($items);
    }
}
