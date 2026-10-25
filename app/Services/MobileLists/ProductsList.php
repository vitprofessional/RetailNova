<?php

namespace App\Services\MobileLists;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\PurchaseWorkflow;
use App\Support\LocalizedNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Products with their stock (desktop: productController@productlist and @stockProduct).
 * Stock status follows Product::is_out_of_stock / is_low_stock: out at 0 or less, low below
 * the product's alert quantity.
 */
class ProductsList extends MobileList
{
    /** Units on hand over all of the product's stock lots. */
    private const STOCK = '(SELECT COALESCE(SUM(product_stocks.currentStock), 0) FROM product_stocks WHERE product_stocks.productId = products.id)';

    private const ALERT = 'CAST(COALESCE(products.quantity, 0) AS DECIMAL(12, 2))';

    /** Selling price of the latest purchase lot, as on the sale screen (PosCatalog). */
    private const PRICE = '(SELECT purchase_products.salePriceExVat FROM purchase_products WHERE purchase_products.productName = products.id ORDER BY purchase_products.id DESC LIMIT 1)';

    public function __construct(private readonly string $route = 'productlist')
    {
    }

    public function key(): string
    {
        return 'products';
    }

    public function route(): string
    {
        return $this->route;
    }

    public function create(): ?array
    {
        return [route('addProduct'), __('mobile.new_product')];
    }

    public function searchPlaceholder(): string
    {
        return __('mobile.search_products_list');
    }

    public function filterFields(): array
    {
        return [
            'stock' => ['label' => __('mobile.stock_status'), 'type' => 'select', 'options' => [
                '' => __('mobile.all_stock'), 'in' => __('inventory.in_stock'), 'low' => __('inventory.low_stock'), 'out' => __('inventory.out_of_stock'),
            ]],
            'category' => ['label' => __('inventory.category'), 'type' => 'select',
                'options' => ['' => __('mobile.all_categories_filter')] + Category::query()->orderBy('name')->pluck('name', 'id')->all()],
            'brand' => ['label' => __('inventory.brand'), 'type' => 'select',
                'options' => ['' => __('mobile.all_brands')] + Brand::query()->orderBy('name')->pluck('name', 'id')->all()],
        ];
    }

    /** Products without the stock-status filter (the summary counts every status). */
    private function base(array $filters): Builder
    {
        // Same scope as the desktop catalog: the user's business, or every business for the platform owner.
        $query = app(PurchaseWorkflow::class)->scoped(Product::withoutGlobalScopes());
        if (isset($filters['q'])) {
            $term = $this->like($filters['q']);
            $query->where(fn (Builder $q) => $q->where('products.name', 'like', $term)->orWhere('products.barCode', 'like', $term));
        }
        if (isset($filters['category'])) {
            $query->where('products.category', $filters['category']);
        }
        if (isset($filters['brand'])) {
            $query->where('products.brand', $filters['brand']);
        }

        return $query;
    }

    protected function query(array $filters): Builder
    {
        $query = $this->base($filters)
            ->with(['brandModel', 'categoryModel', 'unitModel'])
            ->select('products.*')
            ->selectRaw(self::STOCK.' AS stock_on_hand')
            ->selectRaw(self::PRICE.' AS latest_sale_price')
            ->orderByDesc('products.id');
        match ($filters['stock'] ?? null) {
            'out' => $query->whereRaw(self::STOCK.' <= 0'),
            'low' => $query->whereRaw(self::STOCK.' > 0')->whereRaw(self::STOCK.' < '.self::ALERT),
            'in' => $query->whereRaw(self::STOCK.' > 0')->whereRaw(self::STOCK.' >= '.self::ALERT),
            default => null,
        };

        return $query;
    }

    protected function summary(Builder $query, array $filters): array
    {
        $counts = $this->base($filters)->toBase()->selectRaw(
            'COUNT(*) AS total, '
            .'COALESCE(SUM(CASE WHEN '.self::STOCK.' > 0 AND '.self::STOCK.' < '.self::ALERT.' THEN 1 ELSE 0 END), 0) AS low, '
            .'COALESCE(SUM(CASE WHEN '.self::STOCK.' <= 0 THEN 1 ELSE 0 END), 0) AS out_of_stock'
        )->first();

        return [
            ['label' => __('mobile.summary_products'), 'value' => LocalizedNumber::count($counts->total ?? 0)],
            ['label' => __('inventory.low_stock'), 'value' => LocalizedNumber::count($counts->low ?? 0), 'tone' => 'warning'],
            ['label' => __('inventory.out_of_stock'), 'value' => LocalizedNumber::count($counts->out_of_stock ?? 0), 'tone' => 'danger'],
        ];
    }

    public function card(Model $product): array
    {
        /** @var Product $product */
        $stock = (int) $product->stock_on_hand;
        $alert = (float) ($product->quantity ?? 0);
        $status = $stock <= 0 ? 'out_of_stock' : ($stock < $alert ? 'low_stock' : 'in_stock');
        // @canEdit(false): every role except the sales manager may change the catalog.
        $canEdit = strtolower((string) auth('admin')->user()?->role) !== 'salesmanager';
        $unit = $product->unitModel?->name;

        return [
            'id' => $product->id,
            'url' => $canEdit ? route('editProduct', ['id' => $product->id]) : null,
            'avatar' => $this->initial($product->name),
            'avatar_url' => $product->image_thumbnail_url,
            'title' => $product->name,
            'subtitle' => implode(' · ', array_filter([$product->categoryModel?->name, $product->brandModel?->name])),
            'amount' => $product->latest_sale_price !== null ? $this->money($product->latest_sale_price) : '—',
            'note' => __('mobile.in_stock', ['count' => LocalizedNumber::count($stock)]).($unit ? ' '.$unit : ''),
            'note_tone' => ['in_stock' => 'muted', 'low_stock' => 'warning', 'out_of_stock' => 'danger'][$status],
            'badge' => ['label' => __('inventory.'.$status), 'tone' => ['in_stock' => 'success', 'low_stock' => 'warning', 'out_of_stock' => 'danger'][$status]],
            'meta' => array_values(array_filter([$product->barCode ? __('inventory.barcode').': '.$product->barCode : null])),
            'actions' => $canEdit ? [
                $this->link(__('mobile.action_edit'), route('editProduct', ['id' => $product->id]), 'ri-pencil-line'),
                $this->form(__('mobile.action_delete'), route('delProduct', ['id' => $product->id]), 'ri-delete-bin-line', 'DELETE', __('inventory.confirm_delete_product')),
            ] : [],
        ];
    }
}
