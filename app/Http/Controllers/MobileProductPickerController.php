<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseProduct;
use App\Support\BusinessContext;
use App\Support\BusinessUploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileProductPickerController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(auth('admin')->user(), 401);
        $validated = $request->validate([
            'mode' => 'required|in:sale,purchase,quotation,purchase-return,damage',
            'q' => 'nullable|string|max:120',
            'category' => 'nullable|integer|min:1',
            'brand' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ]);
        $businessId = BusinessContext::defaultForCreate();
        abort_unless($businessId && DB::table('business_setups')->where('id', $businessId)->exists(), 422, __('purchases.business_required'));

        $category = isset($validated['category'])
            ? Category::withoutGlobalScopes()->where('businessId', $businessId)->findOrFail($validated['category'])
            : null;
        $brand = isset($validated['brand'])
            ? Brand::withoutGlobalScopes()->where('businessId', $businessId)->findOrFail($validated['brand'])
            : null;
        $search = trim((string) ($validated['q'] ?? ''));
        $mode = $validated['mode'];

        if ($mode === 'purchase-return') {
            $stock = ProductStock::withoutGlobalScopes()->select('purchaseId')
                ->selectRaw('SUM(COALESCE(currentStock, 0)) AS currentStock')
                ->where('businessId', $businessId)->groupBy('purchaseId');
            $query = PurchaseProduct::withoutGlobalScopes()
                ->join('products', function ($join) use ($businessId) {
                    $join->on('products.id', '=', 'purchase_products.productName')
                        ->where('products.businessId', '=', $businessId);
                })
                ->join('suppliers', function ($join) use ($businessId) {
                    $join->on('suppliers.id', '=', 'purchase_products.supplier')
                        ->where('suppliers.businessId', '=', $businessId);
                })
                ->leftJoin('brands', function ($join) use ($businessId) {
                    $join->on('brands.id', '=', 'products.brand')->where('brands.businessId', '=', $businessId);
                })
                ->leftJoin('product_units', function ($join) use ($businessId) {
                    $join->on('product_units.id', '=', 'products.unitName')->where('product_units.businessId', '=', $businessId);
                })
                ->leftJoinSub($stock, 'picker_stock', fn ($join) => $join->on('picker_stock.purchaseId', '=', 'purchase_products.id'))
                ->where('purchase_products.businessId', $businessId)
                ->whereRaw('COALESCE(picker_stock.currentStock, 0) > 0')
                ->when($brand, fn ($q) => $q->where(function ($match) use ($brand) {
                    $match->where('products.brand', (string) $brand->id)->orWhere('products.brand', $brand->name);
                }))
                ->when($category, fn ($q) => $q->where(function ($match) use ($category) {
                    $match->where('products.category', (string) $category->id)->orWhere('products.category', $category->name);
                }))
                ->when($search !== '', fn ($q) => $q->where(function ($match) use ($search) {
                    $match->where('products.name', 'like', '%'.$search.'%')
                        ->orWhere('products.barCode', 'like', '%'.$search.'%')
                        ->orWhere('brands.name', 'like', '%'.$search.'%')
                        ->orWhere('products.brand', 'like', '%'.$search.'%');
                }))
                ->select('purchase_products.id as item_id', 'products.id as product_id', 'products.name', 'products.barCode',
                    'products.image', 'products.brand as brand_id', 'brands.name as brand_name', 'products.category as category_id', 'product_units.name as unit_name',
                    'purchase_products.buyPrice as buy_price', 'purchase_products.salePriceExVat as sale_price',
                    'picker_stock.currentStock as stock')
                ->orderByDesc('purchase_products.id');
        } else {
            $stock = ProductStock::withoutGlobalScopes()->select('productId')
                ->selectRaw('SUM(COALESCE(currentStock, 0)) AS currentStock')
                ->where('businessId', $businessId)->groupBy('productId');
            $latestPurchaseIds = PurchaseProduct::withoutGlobalScopes()->selectRaw('MAX(id)')
                ->where('businessId', $businessId)->groupBy('productName');
            $query = Product::withoutGlobalScopes()->where('products.businessId', $businessId)
                ->leftJoinSub($stock, 'picker_stock', fn ($join) => $join->on('picker_stock.productId', '=', 'products.id'))
                ->leftJoin('brands', function ($join) use ($businessId) {
                    $join->on('brands.id', '=', 'products.brand')->where('brands.businessId', '=', $businessId);
                })
                ->leftJoin('product_units', function ($join) use ($businessId) {
                    $join->on('product_units.id', '=', 'products.unitName')->where('product_units.businessId', '=', $businessId);
                })
                ->leftJoinSub(PurchaseProduct::withoutGlobalScopes()->where('businessId', $businessId)
                    ->whereIn('id', $latestPurchaseIds)->select('id', 'productName', 'buyPrice', 'salePriceExVat'),
                    'latest_purchase', fn ($join) => $join->on('latest_purchase.productName', '=', 'products.id'))
                ->when($brand, fn ($q) => $q->where(function ($match) use ($brand) {
                    $match->where('products.brand', (string) $brand->id)->orWhere('products.brand', $brand->name);
                }))
                ->when($category, fn ($q) => $q->where(function ($match) use ($category) {
                    $match->where('products.category', (string) $category->id)->orWhere('products.category', $category->name);
                }))
                ->when($search !== '', fn ($q) => $q->where(function ($match) use ($search) {
                    $match->where('products.name', 'like', '%'.$search.'%')
                        ->orWhere('products.barCode', 'like', '%'.$search.'%')
                        ->orWhere('brands.name', 'like', '%'.$search.'%');
                }))
                ->select('products.id as item_id', 'products.id as product_id', 'products.name', 'products.barCode',
                    'products.image', 'products.brand as brand_id', 'brands.name as brand_name', 'products.category as category_id', 'product_units.name as unit_name',
                    'latest_purchase.buyPrice as buy_price', 'latest_purchase.salePriceExVat as sale_price',
                    DB::raw('COALESCE(picker_stock.currentStock, 0) as stock'))
                ->when($mode === 'sale', fn ($q) => $q->orderByRaw('COALESCE(picker_stock.currentStock, 0) > 0 DESC'))
                ->when($mode === 'damage', fn ($q) => $q->whereRaw('COALESCE(picker_stock.currentStock, 0) > 0')->orderBy('products.name'))
                ->orderBy('products.name');
        }

        $page = $query->paginate(20)->withQueryString();
        $uploads = app(BusinessUploads::class);
        $items = collect($page->items())->map(function ($item) use ($uploads, $businessId, $mode) {
            $brandName = $item->brand_name ?: (!ctype_digit((string) $item->brand_id) ? $item->brand_id : null);
            $categoryName = $item->category_id
                ? (ctype_digit((string) $item->category_id)
                    ? Category::withoutGlobalScopes()->where('businessId', $businessId)->whereKey($item->category_id)->value('name')
                    : $item->category_id)
                : null;
            return [
                'item_id' => (int) $item->item_id,
                'product_id' => (int) $item->product_id,
                'name' => (string) $item->name,
                'barcode' => (string) ($item->barCode ?? ''),
                'brand_id' => $item->brand_id,
                'brand' => $brandName,
                'category_id' => $item->category_id,
                'category' => $categoryName,
                'unit' => $item->unit_name,
                'image' => $uploads->url($item->image, true),
                'stock' => (int) ($item->stock ?? 0),
                'buy_price' => in_array($mode, ['purchase', 'purchase-return', 'damage'], true) && $item->buy_price !== null
                    ? (float) $item->buy_price : null,
                'sale_price' => $mode !== 'purchase-return' && $item->sale_price !== null
                    ? (float) $item->sale_price : null,
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'categories' => Category::withoutGlobalScopes()->where('businessId', $businessId)->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::withoutGlobalScopes()->where('businessId', $businessId)->orderBy('name')->get(['id', 'name']),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'has_more' => $page->hasMorePages(),
        ]);
    }
}
