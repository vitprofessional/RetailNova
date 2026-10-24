<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductUnit;
use App\Models\PurchaseProduct;
use App\Models\ProductStock;
use App\Models\ProductSerial;
use App\Models\ReturnPurchaseItem;
use App\Models\SaleProduct;
use App\Models\InvoiceItem;
use App\Models\Service;
use Alert;
use Illuminate\Support\Facades\Schema;
use App\Services\InvoiceService;
use App\Services\StockService;
use App\Http\Requests\SaleStoreRequest;
use App\Http\Requests\PurchaseSaveRequest;
use Illuminate\Support\Facades\DB;

class JqueryController extends Controller
{
    private function salesBusinessId(Request $request): int
    {
        $workflow = app(\App\Services\PurchaseWorkflow::class);
        $requestedBusinessId = $request->input('businessId');
        if ($workflow->platform() && ($requestedBusinessId === null || $requestedBusinessId === '')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'businessId' => __('purchases.business_required'),
            ]);
        }

        return $workflow->resolveBusinessId($requestedBusinessId);
    }

    private function salesCustomer(int $customerId, int $businessId): \App\Models\Customer
    {
        abort_unless(auth('admin')->user(), 401);
        $customer = \App\Models\Customer::withoutGlobalScope('business')->find($customerId);
        // The one intentionally shared legacy customer is virtual at tenant level;
        // its product and due data are still constrained by the resolved business.
        $legacyWalking = $customer && $customer->businessId === null && $customer->isWalkIn();
        abort_if(!$customer || (!$legacyWalking && (int)$customer->businessId !== $businessId), 404);
        return $customer;
    }

    /**
     * Validate the product-to-lot allocation created by the aggregate New Sale
     * selector. The request still carries lot-level rows for pricing and serials;
     * this check makes non-serialized FIFO order authoritative while allowing a
     * serialized line to retain the exact purchase batch selected for its serials.
     */
    private function validateSaleBatchAllocation(int $businessId, array $items, array $purchasesByRow, array $backorders): void
    {
        $backorderIds = array_fill_keys(array_map('intval', $backorders), true);
        $actual = [];
        $purchaseById = [];
        foreach ($items as $index => $quantity) {
            $purchase = $purchasesByRow[$index] ?? null;
            if (!$purchase || isset($backorderIds[(int)$purchase->id])) {
                continue;
            }
            $productId = (int)$purchase->productName;
            $purchaseId = (int)$purchase->id;
            $actual[$productId][$purchaseId] = ($actual[$productId][$purchaseId] ?? 0) + (int)$quantity;
            $purchaseById[$purchaseId] = $purchase;
        }
        if (!$actual) {
            return;
        }

        ksort($actual, SORT_NUMERIC);
        foreach ($actual as $productId => $actualLots) {
            $product = Product::withoutGlobalScopes()->whereKey($productId)
                ->where('businessId', $businessId)->first();
            if (!$product) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'purchaseData' => __('sales.the_purchase_is_unavailable_for_this_business'),
                ]);
            }

            $lines = PurchaseProduct::withoutGlobalScopes()
                ->where('businessId', $businessId)->where('productName', $productId)
                ->orderBy('id')->lockForUpdate()->get();
            $remaining = array_sum($actualLots);
            $expected = [];
            $eligibleStock = [];
            $serializedProduct = false;
            foreach ($actualLots as $purchaseId => $_quantity) {
                $selectedPurchase = $purchaseById[(int)$purchaseId] ?? null;
                if ($selectedPurchase && app(\App\Services\SalesSerialPolicy::class)->serialized($selectedPurchase)) {
                    $serializedProduct = true;
                    break;
                }
            }
            foreach ($lines as $line) {
                $supplier = \App\Models\Supplier::withoutGlobalScopes()->whereKey($line->supplier)
                    ->where('businessId', $businessId)->exists();
                if (!$supplier) {
                    continue;
                }
                $stock = ProductStock::withoutGlobalScopes()->where('purchaseId', $line->id)
                    ->where('businessId', $businessId)->where('productId', $productId)
                    ->lockForUpdate()->first();
                $available = max(0, (int)($stock->currentStock ?? 0));
                if ($available > 0 && app(\App\Services\SalesSerialPolicy::class)->serialized($line)) {
                    $serials = app(\App\Services\SalesSerialPolicy::class)->available(
                        ProductSerial::where('purchaseId', $line->id)->where('productId', $productId)
                    )->count();
                    $available = min($available, $serials);
                }
                if (!$stock || $available === 0 || $remaining === 0) {
                    $eligibleStock[(int)$line->id] = $available;
                    continue;
                }
                $eligibleStock[(int)$line->id] = $available;
                $allocated = min($available, $remaining);
                $expected[(int)$line->id] = $allocated;
                $remaining -= $allocated;
            }

            if ($remaining > 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'purchaseData' => __('sales.insufficient_stock'),
                ]);
            }
            ksort($actualLots, SORT_NUMERIC);
            if ($serializedProduct) {
                foreach ($actualLots as $purchaseId => $quantity) {
                    if ((int)$quantity > (int)($eligibleStock[(int)$purchaseId] ?? 0)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'purchaseData' => __('sales.insufficient_stock'),
                        ]);
                    }
                }
            } elseif ($actualLots !== $expected) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'purchaseData' => __('sales.purchase_batch_selection_changed'),
                ]);
            }
        }
    }

    private function saleMoneyCents($value): int
    {
        $raw = trim((string)$value);
        if (!preg_match('/^([+-]?)(\d+)(?:\.(\d{1,2}))?$/D', $raw, $parts)) {
            throw new \InvalidArgumentException('Invalid monetary value');
        }
        $whole = ltrim($parts[2], '0');
        $whole = $whole === '' ? '0' : $whole;
        if (strlen($whole) > 10 || (strlen($whole) === 10 && strcmp($whole, '9999999999') > 0)) {
            throw new \OverflowException('Monetary value exceeds supported precision');
        }
        $cents = ((int)$whole * 100) + (int)str_pad($parts[3] ?? '', 2, '0');
        return ($parts[1] ?? '') === '-' ? -$cents : $cents;
    }

    private function formatSaleMoney(int $cents): string
    {
        $absolute = abs($cents);
        return ($cents < 0 ? '-' : '') . intdiv($absolute, 100) . '.' . str_pad((string)($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
    public function getProductDetails($id){
        $workflow = app(\App\Services\PurchaseWorkflow::class);
        $getData = $workflow->scoped(Product::withoutGlobalScopes())->find($id);

        if ($getData) {
            // total current stock across all stock records for this product
            $stockHistory = ProductStock::where(['productId' => $getData->id])->get();
            $currentStock = $stockHistory ? $stockHistory->sum('currentStock') : 0;

            // brand information
            $brand = Brand::find($getData->brand);
            $brandName = $brand ? $brand->name : '';
            $productName = $getData->name;
            $productWithBrand = $productName . ($brandName ? ' - ' . $brandName : '');

            // Attempt to return the most-recent purchase row for this product so the frontend
            // can pre-fill buy/sale prices and VAT status. Fall back to empty values when
            // no purchase rows exist.
            $lastPurchase = PurchaseProduct::where('productName', $getData->id)->orderBy('id', 'desc')->first();

            $buyPrice = $lastPurchase ? ($lastPurchase->buyPrice ?? '') : '';
            $salePrice = $lastPurchase ? ($lastPurchase->salePriceExVat ?? '') : '';
            $vatStatus = $lastPurchase ? ($lastPurchase->vatStatus ?? '') : '';

            return [
                'productName'  => $productWithBrand,
                'currentStock' => $currentStock,
                'buyPrice'     => $buyPrice,
                'salePrice'    => $salePrice,
                'vatStatus'    => $vatStatus,
                'message'      => __('common.api_success'),
                'id'           => $getData->id
            ];
        }

        return ['productName' => '', 'currentStock' => 0, 'buyPrice' => '', 'salePrice' => '', 'vatStatus' => '', 'message' => __('common.api_not_found'), 'id' => ''];
    }

    public function getSaleProductDetails($id){
        try {
            $getData = PurchaseProduct::where('productId', $id)
                ->join('suppliers','purchase_products.supplier','suppliers.id')
                ->join('product_stocks','product_stocks.purchaseId','purchase_products.id')
                ->select(
                    'purchase_products.id as purchaseId',
                    'purchase_products.supplier',
                    'purchase_products.buyPrice',
                    'purchase_products.salePriceExVat',
                    'purchase_products.salePriceInVat',
                    'purchase_products.vatStatus',
                    'purchase_products.created_at',
                    'purchase_products.created_at as purchaseDate',
                    'suppliers.name as supplierName',
                    'suppliers.mail as supplierMail',
                    'suppliers.mobile as supplierMobile',
                    'product_stocks.currentStock'
                )->orderBy('purchaseId','desc')->get();

            if($getData->count() > 0){
                $product = Product::find($id);
                $productName = $product ? $product->name : '';
                $first = $getData->first();
                $salePrice = isset($first->salePriceExVat) ? $first->salePriceExVat : '';
                $buyPrice = isset($first->buyPrice) ? $first->buyPrice : '';

                return ['productName' => $productName, 'id' => $first->purchaseId, 'getData' => $getData, 'buyPrice'=> $buyPrice, 'salePrice'=>$salePrice];
            }

            return ['productName' => "", 'id'=>$id, 'getData' => null, 'buyPrice'=>'', 'salePrice'=>''];
        } catch (\Exception $e) {
            \Log::error('getSaleProductDetails error: '.$e->getMessage(), ['id' => $id, 'trace' => $e->getTraceAsString()]);
            return ['productName' => "", 'id'=>$id, 'getData' => null, 'buyPrice'=>'', 'salePrice'=>''];
        }
    }

    /**
     * Public variant of getSaleProductDetails returning a simplified JSON payload.
     * Exposed to avoid auth guard redirects breaking the new sale page dynamic load.
     */
    public function getSaleProductDetailsPublic($id, Request $request)
    {
        $businessId = $this->salesBusinessId($request);
        try {
            $rows = $this->salesProductStockRows($businessId, null, null, (int)$id)
                ->filter(fn ($row) => (int)$row->currentStock > 0)->values();
            if($rows->count() === 0){
                return response()->json(['status'=>'ok','getData'=>[]]);
            }
            $pname = (string)($rows->first()->productName ?? '');
            $latest = $this->latestEligibleSalesBatch($rows);
            // Ensure each row includes productName for UI table rendering
            $rows = $rows->map(function($r) use ($pname){
                $r->productName = $pname;
                return $r;
            });
            return response()->json([
                'status' => 'ok',
                'productName' => $pname,
                'getData' => $rows,
                'defaultSalePrice' => (string)($latest->salePriceExVat ?? '0.00'),
                'defaultPricePurchaseId' => (int)$latest->purchaseId,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('getSaleProductDetailsPublic error: '.$e->getMessage(), ['id'=>$id]);
            return response()->json(['status'=>'error','getData'=>[]], 500);
        }
    }

    /**
     * Return product <option> HTML for a customer. For now this returns all products
     * (customer-specific filtering can be added later). This endpoint is consumed
     * by the sale page when a customer is selected so the product dropdown is
     * populated dynamically.
     */
    public function getProductsForCustomer($customerId)
    {
        // TODO: filter products by customer if needed. For now return all active products.
        $products = Product::orderBy('name','asc')->get();

        $options = '<option value="">'.__('sales.select').'</option>';
        foreach ($products as $p) {
            $options .= '<option value="'.$p->id.'">'.htmlspecialchars($p->name).'</option>';
        }

        return response()->json(['data' => $options]);
    }

    /**
     * Public variant of getProductsForCustomer used by AJAX on the sale page.
     * This same-origin AJAX endpoint uses the selected, server-authorized
     * business context before returning its option HTML payload.
     */
    public function getProductsForCustomerPublic($customerId, Request $request)
    {
        $businessId = $this->salesBusinessId($request);
        $this->salesCustomer((int)$customerId, $businessId);
        $rows = $this->salesProductStockRows($businessId, $request->input('categoryId'), $request->input('brandId'));

        return $this->salesProductOptionsResponse($rows);
    }

    /** Return one current-stock row per purchase lot, scoped to a verified business. */
    private function salesProductStockRows(int $businessId, $categoryId = null, $brandId = null, $productId = null)
    {
        $stocks = DB::table('product_stocks')
            ->select('purchaseId', 'productId')
            ->selectRaw('SUM(COALESCE(currentStock, 0)) AS currentStock')
            ->where('businessId', $businessId)
            ->groupBy('purchaseId', 'productId');

        $rows = PurchaseProduct::withoutGlobalScopes()->where('purchase_products.businessId', $businessId)
            ->join('suppliers','purchase_products.supplier','suppliers.id')
            ->join('products','purchase_products.productName','products.id')
            ->leftJoinSub($stocks, 'sale_product_stock', function ($join) use ($businessId) {
                $join->on('sale_product_stock.purchaseId', '=', 'purchase_products.id')
                    ->on('sale_product_stock.productId', '=', 'purchase_products.productName');
            })
            ->where('suppliers.businessId', $businessId)
            ->where('products.businessId', $businessId)
            ->when($categoryId !== null && $categoryId !== '', function ($query) use ($categoryId) {
                $category = Category::find($categoryId);
                $query->where(function ($match) use ($categoryId, $category) {
                    $match->where('products.category', (string)$categoryId);
                    if ($category && $category->name !== null) {
                        $match->orWhere('products.category', $category->name);
                    }
                });
            })
            ->when($brandId !== null && $brandId !== '', function ($query) use ($brandId) {
                $query->where('products.brand', (string)$brandId);
            })
            ->when($productId !== null, function ($query) use ($productId) {
                $query->where('products.id', (int)$productId);
            })
            ->select(
                'purchase_products.id as purchaseId',
                'products.id as productId',
                'products.name as productName',
                'products.brand as brandId',
                'products.category as categoryId',
                'purchase_products.purchase_date as purchaseDate',
                'purchase_products.created_at as purchaseCreatedAt',
                'suppliers.name as supplierName',
                'purchase_products.buyPrice',
                'purchase_products.salePriceExVat',
                'purchase_products.vatPercent',
                'purchase_products.vatStatus',
                'sale_product_stock.currentStock as currentStock')
            ->orderBy('purchase_products.id','asc')
            ->get();
        return $this->applySaleableSerialStock($rows);
    }

    /** Latest eligible price uses purchase date, then purchase row ID as deterministic tie-breaker. */
    private function latestEligibleSalesBatch($rows)
    {
        return $rows->sort(function ($left, $right) {
            $leftDate = $this->salesPurchaseChronology($left);
            $rightDate = $this->salesPurchaseChronology($right);
            if ($leftDate !== $rightDate) {
                return $rightDate <=> $leftDate;
            }
            return (int)$right->purchaseId <=> (int)$left->purchaseId;
        })->first();
    }

    private function salesPurchaseChronology($batch): int
    {
        $date = trim((string)($batch->purchaseDate ?? ''));
        if ($date === '') {
            $date = (string)($batch->purchaseCreatedAt ?? '');
        }
        try {
            return $date !== '' ? \Carbon\CarbonImmutable::parse($date)->getTimestamp() : PHP_INT_MIN;
        } catch (\Throwable $e) {
            return PHP_INT_MIN;
        }
    }

    /** Cap serialized lot availability by the serials the existing policy can actually allocate. */
    private function applySaleableSerialStock($rows)
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $purchaseIds = $rows->pluck('purchaseId')->map(fn ($id) => (int)$id)->unique()->values();
        $productIds = $rows->pluck('productId')->map(fn ($id) => (int)$id)->unique()->values();
        $serialRows = ProductSerial::query()
            ->where(function ($query) use ($purchaseIds, $productIds) {
                $query->whereIn('purchaseId', $purchaseIds)->orWhereIn('productId', $productIds);
            })
            ->get(['purchaseId', 'productId', 'saleId', 'status']);

        $serializedProducts = $serialRows->pluck('productId')->map(fn ($id) => (int)$id)->unique()->all();
        $serializedPurchases = $serialRows->pluck('purchaseId')->map(fn ($id) => (int)$id)->unique()->all();
        $availableByLot = $serialRows
            ->filter(fn ($serial) => $serial->saleId === null && in_array($serial->status, [null, 'available'], true))
            ->groupBy(fn ($serial) => (int)$serial->purchaseId.':'.(int)$serial->productId)
            ->map->count();

        return $rows->map(function ($row) use ($serializedProducts, $serializedPurchases, $availableByLot) {
            $purchaseId = (int)$row->purchaseId;
            $productId = (int)$row->productId;
            if (in_array($productId, $serializedProducts, true) || in_array($purchaseId, $serializedPurchases, true)) {
                $serialAvailability = (int)($availableByLot[$purchaseId.':'.$productId] ?? 0);
                $row->currentStock = min(max(0, (int)($row->currentStock ?? 0)), $serialAvailability);
            }
            return $row;
        });
    }

    /** Keep the selector unique by Product ID while retaining lot-level stock details for allocation. */
    private function salesProductOptionsResponse($rows)
    {
        $products = $rows->groupBy('productId');
        $options = '<option value="">'.__('sales.select').'</option>';
        $outOfStock = [];
        foreach ($products as $productId => $lots) {
            $product = $lots->first();
            $totalStock = (int)$lots->sum(fn ($lot) => max(0, (int)($lot->currentStock ?? 0)));
            if ($totalStock <= 0) {
                foreach ($lots as $lot) {
                    $outOfStock[] = [
                        'purchaseId' => $lot->purchaseId,
                        'productId' => $lot->productId,
                        'productName' => $lot->productName,
                        'supplierName' => $lot->supplierName,
                        'currentStock' => max(0, (int)($lot->currentStock ?? 0)),
                        'purchaseDate' => $lot->purchaseDate,
                    ];
                }
                continue;
            }
            $label = htmlspecialchars((string)$product->productName, ENT_QUOTES, 'UTF-8');
            $stockLabel = htmlspecialchars(__('common.stock'), ENT_QUOTES, 'UTF-8');
            $options .= '<option value="'.(int)$productId.'" data-product-id="'.(int)$productId.'" data-current-stock="'.$totalStock.'" title="'.$stockLabel.': '.$totalStock.'">'.$label.' — '.$stockLabel.': '.$totalStock.'</option>';
        }

        return response()->json([
            'data' => $options,
            'outOfStock' => $outOfStock,
        ]);
    }

    /**
     * Public endpoint to return a single purchase row by purchase id.
     * Used when the product dropdown option targets a specific purchase record.
     */
    public function getPurchaseDetailsPublic($id)
    {
        $workflow = app(\App\Services\PurchaseWorkflow::class);
        $purchase = $workflow->line((int)$id);
        $businessId = (int)$purchase->businessId;
        try{
            $p = PurchaseProduct::withoutGlobalScopes()->where('purchase_products.id', $id)
                ->where('purchase_products.businessId', $businessId)
                ->join('products','purchase_products.productName','products.id')
                ->join('suppliers','purchase_products.supplier','suppliers.id')
                ->leftJoin('product_stocks',function($join) use ($businessId){
                    $join->on('product_stocks.purchaseId','=','purchase_products.id')
                        ->where('product_stocks.businessId','=',$businessId);
                })
                ->where('products.businessId', $businessId)
                ->where('suppliers.businessId', $businessId)
                ->select(
                    'purchase_products.id as purchaseId',
                    'products.id as productId',
                    'products.name as productName',
                    'purchase_products.purchase_date as purchaseDate',
                    'purchase_products.buyPrice',
                    'purchase_products.salePriceExVat',
                    'purchase_products.vatStatus',
                    'suppliers.name as supplierName',
                    'product_stocks.currentStock as currentStock'
                )->first();

            if(!$p) return response()->json(['status'=>'ok','getData'=>[]]);
            return response()->json(['status'=>'ok','getData'=>[$p]]);
        }catch(\Throwable $e){
            \Log::warning('getPurchaseDetailsPublic error: '.$e->getMessage(), ['id'=>$id]);
            return response()->json(['status'=>'error','getData'=>[]],500);
        }
    }
    /**
     * Public variant of getProductDetails used by sale/damage pages to avoid auth redirects.
     */
    public function getProductDetailsPublic($id)
    {
        $workflow = app(\App\Services\PurchaseWorkflow::class);
        $businessId = $workflow->resolveBusinessId();
        $getData = $workflow->scoped(Product::withoutGlobalScopes())->find($id);

        if ($getData) {
            $stockHistory = ProductStock::withoutGlobalScopes()->where(['productId' => $getData->id, 'businessId' => $businessId])->get();
            $currentStock = $stockHistory ? $stockHistory->sum('currentStock') : 0;

            $brand = Brand::find($getData->brand);
            $brandName = $brand ? $brand->name : '';
            $productName = $getData->name;
            $productWithBrand = $productName . ($brandName ? ' - ' . $brandName : '');

            $lastPurchase = PurchaseProduct::withoutGlobalScopes()->where('productName', $getData->id)
                ->where('businessId', $businessId)->orderBy('id', 'desc')->first();

            $buyPrice = $lastPurchase ? ($lastPurchase->buyPrice ?? '') : '';
            $salePrice = $lastPurchase ? ($lastPurchase->salePriceExVat ?? '') : '';
            $purchaseDate = optional($lastPurchase)->created_at;

            return response()->json([
                'productName'  => $productWithBrand,
                'currentStock' => $currentStock,
                'buyPrice'     => $buyPrice,
                'salePrice'    => $salePrice,
                'purchaseDate' => $purchaseDate ? $purchaseDate->format('Y-m-d') : null,
                'message'      => __('common.api_success'),
                'id'           => $getData->id
            ]);
        }

        return response()->json(['productName' => '', 'currentStock' => 0, 'buyPrice' => '', 'salePrice' => '', 'purchaseDate'=>null, 'message' => __('common.api_not_found'), 'id' => ''], 404);
    }

    /**
     * Public endpoint: return aggregate previous due for a customer.
     * Computes sum of outstanding due for all sales belonging to the customer.
     */
    public function getCustomerPreviousDuePublic($customerId, Request $request)
    {
        $businessId = $this->salesBusinessId($request);
        $this->salesCustomer((int)$customerId, $businessId);
        try {
            // Prefer an explicit dueAmount column if present, otherwise compute grandTotal - paidAmount
            $table = 'sale_products';
            $hasDue = Schema::hasColumn($table, 'dueAmount');
            $hasGrand = Schema::hasColumn($table, 'grandTotal');
            $hasTotal = Schema::hasColumn($table, 'totalAmount');

            // Only the tenant scope is replaced (businessId is filtered explicitly);
            // the soft-delete scope stays so cancelled sales never count as due.
            if ($hasDue) {
                $prevDue = SaleProduct::withoutGlobalScope('business')->where('customerId', $customerId)->where('businessId', $businessId)->sum('dueAmount');
            } else if ($hasGrand) {
                $prevDue = SaleProduct::withoutGlobalScope('business')->where('customerId', $customerId)->where('businessId', $businessId)
                    ->selectRaw('COALESCE(SUM(COALESCE(grandTotal,0) - COALESCE(paidAmount,0)),0) as s')->value('s');
            } else if ($hasTotal) {
                $prevDue = SaleProduct::withoutGlobalScope('business')->where('customerId', $customerId)->where('businessId', $businessId)
                    ->selectRaw('COALESCE(SUM(COALESCE(totalAmount,0) - COALESCE(paidAmount,0)),0) as s')->value('s');
            } else {
                // Fallback: try to compute from known fields if present
                $prevDue = SaleProduct::withoutGlobalScope('business')->where('customerId', $customerId)->where('businessId', $businessId)
                    ->selectRaw('COALESCE(SUM(COALESCE(grandTotal, totalAmount, 0) - COALESCE(paidAmount,0)),0) as s')->value('s');
            }

            $prevDue = (float) ($prevDue ?: 0);
            return response()->json(['status' => 'ok', 'prevDue' => number_format($prevDue, 2, '.', '')]);
        } catch (\Throwable $e) {
            \Log::warning('getCustomerPreviousDuePublic error: '.$e->getMessage(), ['id'=>$customerId]);
            return response()->json(['status' => 'error', 'prevDue' => '0.00'], 500);
        }
    }

    /**
     * Public endpoint: return categories as <option> HTML for filtering products on sale page.
     */
    public function getBrandsPublic(Request $request)
    {
        // Let authorization and business-context validation produce their
        // intended 403/422 responses instead of turning them into lookup 500s.
        $businessId = $this->salesBusinessId($request);
        try {
            $brandIds = $this->salesProductStockRows($businessId)->pluck('brandId')
                ->filter(fn ($id) => is_numeric($id) && (int)$id > 0)->map(fn ($id) => (int)$id)->unique()->values();
            $brands = Brand::whereIn('id', $brandIds)->orderBy('name')->get(['id','name']);
            $options = '<option value="">'.e(__('sales.all_brands')).'</option>';
            foreach ($brands as $brand) {
                $options .= '<option value="'.(int)$brand->id.'">'.e((string)$brand->name).'</option>';
            }
            return response()->json(['data' => $options]);
        } catch (\Throwable $e) {
            \Log::warning('getBrandsPublic error: '.$e->getMessage());
            return response()->json(['data' => '<option value="">'.e(__('sales.all_brands')).'</option>'], 500);
        }
    }

    /** Categories are derived only from saleable products in the active business and selected brand. */
    public function getCategoriesPublic(Request $request)
    {
        // Business authorization is not a recoverable lookup failure.
        $businessId = $this->salesBusinessId($request);
        try {
            $rows = $this->salesProductStockRows($businessId, null, $request->input('brandId'));
            $categoryValues = $rows->pluck('categoryId')->filter(fn ($value) => trim((string)$value) !== '')
                ->map(fn ($value) => (string)$value)->unique()->values();
            $numericIds = $categoryValues->filter(fn ($value) => ctype_digit($value))->map(fn ($value) => (int)$value);
            $categories = Category::whereIn('id', $numericIds)->get(['id','name'])->keyBy(fn ($category) => (string)$category->id);
            $options = '<option value="">'.e(__('sales.all_categories')).'</option>';
            $labels = [];
            foreach ($categoryValues as $value) {
                $category = $categories->get($value);
                $labels[$value] = $category ? (string)$category->name : $value;
            }
            asort($labels, SORT_NATURAL | SORT_FLAG_CASE);
            foreach ($labels as $value => $label) {
                $options .= '<option value="'.e((string)$value).'">'.e($label).'</option>';
            }
            return response()->json(['data' => $options]);
        } catch (\Throwable $e) {
            \Log::warning('getCategoriesPublic error: '.$e->getMessage());
            return response()->json(['data' => '<option value="">'.e(__('sales.all_categories')).'</option>'], 500);
        }
    }

    /**
     * Public endpoint: return product options filtered by a category id.
     * Options include stock and supplier, consistent with customer products endpoint.
     */
    public function getProductsByCategoryPublic($categoryId, Request $request)
    {
        $businessId = $this->salesBusinessId($request);
        try {
            return $this->salesProductOptionsResponse($this->salesProductStockRows($businessId, $categoryId, $request->input('brandId')));
        } catch (\Throwable $e) {
            \Log::warning('getProductsByCategoryPublic error: '.$e->getMessage(), ['categoryId'=>$categoryId]);
            return response()->json(['data' => '<option value="">'.__('sales.select').'</option>', 'outOfStock' => []], 500);
        }
    }
    public function getPurchaseDetails($id){
        $getData = PurchaseProduct::find($id);
        if($getData){
            $salePrice      = $getData->salePriceExVat;
            $buyPrice       = $getData->buyPrice;
            // include current stock for this purchase row so frontend can validate quantities immediately
            $currentStock = \App\Models\ProductStock::where('purchaseId', $id)->sum('currentStock');
            return ['id'=>$id, 'buyPrice'=> $buyPrice, 'getData' => $getData, 'salePrice'=>$salePrice, 'currentStock' => $currentStock];
        }else{
            return ['id'=>"", 'buyPrice'=> "", 'getData' => null, 'salePrice'=>"", 'currentStock' => 0];
        }
    }

    /**
     * Authenticated serial lookup (legacy public route name retained for clients).
     */
    public function getPurchaseSerialsPublic($purchaseId)
    {
        $actor = auth('admin')->user();
        if (!$actor) {
            return response()->json(['status' => 'error', 'serials' => []], 401);
        }
        $purchase = PurchaseProduct::find($purchaseId);
        $canAccessAllBusinesses = $actor->canAccessAllSalesBusinesses();
        if (!$purchase || (!$canAccessAllBusinesses && (
            (int) $actor->businessId <= 0 || (int) $purchase->businessId !== (int) $actor->businessId
        ))) {
            return response()->json(['status' => 'error', 'serials' => []], 404);
        }
        try {
            $policy = app(\App\Services\SalesSerialPolicy::class);
            $query = $policy->available(ProductSerial::where('purchaseId', $purchaseId)
                ->where('productId', $purchase->productName));
            $serials = $query->orderBy('id', 'asc')->get(['id','serialNumber','saleId','status','purchaseId']);
            return response()->json(['status' => 'ok', 'serials' => $serials,
                'serialized' => $policy->serialized($purchase),
                'selection_required' => $policy->required($purchase->businessId)]);
        } catch (\Throwable $e) {
            \Log::warning('getPurchaseSerialsPublic error: '.$e->getMessage(), ['purchaseId' => $purchaseId]);
            return response()->json(['status' => 'error', 'serials' => []], 500);
        }
    }

    public function savePurchase(PurchaseSaveRequest $requ){
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        $header=null;
        $ids=array_filter((array)$requ->input('purchaseId',[]));
        if($ids){
            foreach($ids as $id){
                $line=$workflow->line((int)$id);
                if(!$line->purchase_invoice_id){
                    throw \Illuminate\Validation\ValidationException::withMessages(['items'=>__('purchases.legacy_identity_required')]);
                }
                if($header && $header->id!==(int)$line->purchase_invoice_id){
                    throw \Illuminate\Validation\ValidationException::withMessages(['items'=>__('purchases.foreign_line')]);
                }
                $header=$workflow->header((int)$line->purchase_invoice_id);
            }
        }
        $header=$workflow->save($requ->all(),$header);
        return redirect()->route('purchase.invoice.show',$header->id)->with('status',__('purchases.invoice_saved'));
    }

    public function getServiceDetails($id){
        $getData = Service::find($id);

        if($getData):
            $serviceName = $getData->serviceName;
            $rate = $getData->rate;
            return ['serviceName' => $serviceName, 'rate' => $rate, 'message'=>__('common.api_success_form_successfully_subjmit'),'id'=> $getData->id];
        else:
            return ['serviceName' => "",'rate' => "", 'message'=>__('common.api_error_there_is_an_error_please_try_agin'),'id'=>''];
        endif;    
    }

    // get grand total
     public function calculateGrandTotal()
    {
        $stock = ProductStock::where(['purchaseId'=>request()->input('purchaseId')])->sum('currentStock');
        $items = request()->input('items');
        $grandTotal = 0;

        foreach ($items as $item) {
            $price = floatval($item['price']);
            $quantity = intval($item['quantity']);
            $grandTotal += $price * $quantity;
        }

        return response()->json([
            'grandTotal'    => $grandTotal,
            'currentStock'  => $stock
        ]);
    }

    public function saveSale(SaleStoreRequest $requ){
        // Validation handled by FormRequest
        $stockService = app(StockService::class);
        $invoiceService = app(InvoiceService::class);
        $createdSaleId = null;

        $items = $requ->qty ?? [];
        // Preserve sparse row keys when a preceding row has no serial inputs.
        $serialIdsInput = (array)$requ->input('serialId', []);
        $serialNumbersInput = (array)$requ->input('serialNumber', []);
        $serialPolicy = app(\App\Services\SalesSerialPolicy::class);

        try {
            DB::transaction(function() use ($requ, $items, $stockService, $invoiceService, $serialPolicy, $serialIdsInput, $serialNumbersInput, &$createdSaleId) {
                $sales = new SaleProduct();
                // Generate a sequenced sale invoice (replaces client-provided invoice)
                $sales->invoice         = $invoiceService->generateSaleInvoice();
                $sales->date            = $requ->date;
                $sales->customerId      = $requ->customerId;
                $sales->reference       = $requ->reference;
                $sales->note            = $requ->note;
                $sales->totalSale       = 0;
                $sales->discountAmount  = 0;
                // Persist optional additional charge (e.g., service charge)
                try{
                    $sales->additionalChargeName   = $requ->input('additionalChargeName');
                    $sales->additionalChargeAmount = $requ->input('additionalChargeAmount');
                }catch(\Throwable $_){ /* ignore */ }
                $sales->grandTotal      = 0;
                $sales->paidAmount      = 0;
                $sales->payment_method  = $requ->validated('payment_method');
                $sales->invoiceDue      = 0;
                $sales->prevDue         = 0;
                $sales->curDue          = 0;
                $sales->status          = "Ordered";
                // Assign businessId from request for admin/superadmin
                $actor = auth('admin')->user();
                if($actor && $actor->canAccessAllSalesBusinesses()){
                    $bizId = (int)($requ->input('businessId') ?? 0);
                    if($bizId > 0){ $sales->businessId = $bizId; }
                }
                if ($actor && !$actor->canAccessAllSalesBusinesses()) {
                    $sales->businessId = $actor->businessId;
                }
                if (!$sales->businessId) {
                    $sales->businessId = PurchaseProduct::withoutGlobalScopes()->find($requ->purchaseData[0] ?? 0)?->businessId;
                }
                $customer = \App\Models\Customer::withoutGlobalScope('business')->lockForUpdate()->find($requ->customerId);
                $legacyWalking = $customer && $customer->businessId === null && $customer->isWalkIn();
                if (!$customer || (!$legacyWalking && (int)$customer->businessId !== (int)$sales->businessId)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['customerId' => __('sales.customer_unavailable_for_business')]);
                }
                // Record salesperson (the admin user who created this sale)
                try{ if($actor && isset($actor->id)){ $sales->salespersonId = $actor->id; } }catch(\Throwable $_){}

                $seenPurchases = [];
                $purchasesByRow = [];
                foreach ($requ->purchaseData as $row => $pid) {
                    $purchase = $serialPolicy->purchase((int)$pid, $sales->businessId, "purchaseData.$row");
                    $purchasesByRow[$row] = $purchase;
                    if (isset($seenPurchases[(int)$pid]) && $serialPolicy->serialized($purchase)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(["purchaseData.$row" => __('common.notification_combine_duplicate_serialized_purchase_rows_into_one_quantity')]);
                    }
                    $seenPurchases[(int)$pid] = true;
                }

                $backorders = $requ->backorder ?? [];
                if(!is_array($backorders)) $backorders = [$backorders];
                $this->validateSaleBatchAllocation((int)$sales->businessId, $items, $purchasesByRow, $backorders);

                $salePrices = (array)$requ->input('salePrice', []);
                $unitPriceByProduct = [];
                $subtotalCents = 0;
                $maxStoredCents = 999999999999;
                foreach ($items as $index => $quantity) {
                    $purchase = $purchasesByRow[$index] ?? null;
                    if (!$purchase) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "purchaseData.$index" => __('sales.the_purchase_is_unavailable_for_this_business'),
                        ]);
                    }
                    $priceCents = $this->saleMoneyCents($salePrices[$index] ?? 0);
                    if ($priceCents < 0 || $priceCents > 999999999999) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "salePrice.$index" => __('validation.numeric', ['attribute' => __('sales.sale_price')]),
                        ]);
                    }
                    $productId = (int)$purchase->productName;
                    if (isset($unitPriceByProduct[$productId]) && $unitPriceByProduct[$productId] !== $priceCents) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "salePrice.$index" => __('sales.product_group_price_must_match'),
                        ]);
                    }
                    $unitPriceByProduct[$productId] = $priceCents;
                    $subtotalCents += $priceCents * (int)$quantity;
                    if ($subtotalCents > $maxStoredCents) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'totalSaleAmount' => __('sales.amount_exceeds_supported_limit'),
                        ]);
                    }
                }

                $discountCents = $this->saleMoneyCents($requ->input('discountAmount', 0));
                $chargeCents = $this->saleMoneyCents($requ->input('additionalChargeAmount', 0));
                $paidCents = $this->saleMoneyCents($requ->input('paidAmount', 0));
                if ($discountCents > $subtotalCents) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'discountAmount' => __('sales.discount_cannot_exceed_subtotal'),
                    ]);
                }
                $grandCents = max(0, $subtotalCents - $discountCents + $chargeCents);
                if ($grandCents > $maxStoredCents || $paidCents > $maxStoredCents) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'grandTotal' => __('sales.amount_exceeds_supported_limit'),
                    ]);
                }
                $dueCents = max(0, $grandCents - $paidCents);
                $dueColumn = Schema::hasColumn('sale_products', 'dueAmount') ? 'dueAmount'
                    : (Schema::hasColumn('sale_products', 'invoiceDue') ? 'invoiceDue' : 'curDue');
                $previousDueCents = $this->saleMoneyCents(SaleProduct::withoutGlobalScope('business')
                    ->where('businessId', (int)$sales->businessId)
                    ->where('customerId', (int)$sales->customerId)
                    ->sum($dueColumn));

                $sales->totalSale = $this->formatSaleMoney($subtotalCents);
                $sales->discountAmount = $this->formatSaleMoney($discountCents);
                $sales->additionalChargeAmount = $this->formatSaleMoney($chargeCents);
                $sales->grandTotal = $this->formatSaleMoney($grandCents);
                $sales->paidAmount = $this->formatSaleMoney($paidCents);
                $sales->invoiceDue = $this->formatSaleMoney($dueCents);
                $sales->prevDue = $this->formatSaleMoney($previousDueCents);
                $sales->curDue = $this->formatSaleMoney($dueCents);
                if(!$sales->save()){
                    throw new \Exception('Failed to save sale');
                }
                $createdSaleId = (int)$sales->id;

                if(count($items)>0){
                    $totalVatCents = 0;
                    foreach($items as $index => $item){
                        $invoice = new InvoiceItem();
                        $invoice->purchaseId = $requ->purchaseData[$index];
                        // if this purchase was marked as a backorder by the client, set flag
                        $isBackorder = false;
                        try{ $pidCheck = (string)($requ->purchaseData[$index] ?? ''); $isBackorder = in_array($pidCheck, array_map('strval', $backorders), true); }catch(
                        Exception $_){ $isBackorder = false; }
                        if($isBackorder){ $invoice->isBackorder = true; }
                        $invoice->saleId = $sales->id;
                        if(isset($sales->businessId)) { $invoice->businessId = $sales->businessId; }
                        $invoice->qty = $item;
                        // Persist optional per-item warranty in days from request
                        try{ $invoice->warranty_days = isset($requ->warranty_days[$index]) ? $requ->warranty_days[$index] : null; }catch(\Throwable $_){ $invoice->warranty_days = null; }
                        $purchase = $purchasesByRow[$index];
                        $invoice->salePrice = $this->formatSaleMoney($this->saleMoneyCents($salePrices[$index]));
                        $invoice->buyPrice = $purchase->buyPrice ?? 0;
                        
                        // VAT and cost come from the authorized purchase batch, never from client input.
                        $vatPct = (float)($purchase->vatPercent ?? 0);
                        $isVatIncluded = (bool)($purchase->vatStatus ?? false);
                        $invoice->vatPercent = $vatPct;
                        $invoice->vatIncluded = $isVatIncluded ? 1 : 0;
                        
                        // Calculate VAT amount and salePriceIncVat
                        $basePrice = (float)$invoice->salePrice;
                        $basePriceCents = $this->saleMoneyCents($invoice->salePrice);
                        if($isVatIncluded && $vatPct > 0) {
                            $salePriceIncVatCents = (int)round($basePriceCents * (1 + ($vatPct / 100)), 0, PHP_ROUND_HALF_UP);
                            $vatAmount = round($basePrice * ($vatPct / 100) * (int)$item, 2, PHP_ROUND_HALF_UP);
                            if ($salePriceIncVatCents > 999999999999 || !is_finite($vatAmount) || $vatAmount < 0 || $vatAmount > 9999999999.99) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    "salePrice.$index" => __('sales.amount_exceeds_supported_limit'),
                                ]);
                            }
                            $invoice->salePriceIncVat = $this->formatSaleMoney($salePriceIncVatCents);
                            $invoice->vatAmount = number_format($vatAmount, 2, '.', '');
                        } else {
                            $invoice->salePriceIncVat = $this->formatSaleMoney($basePriceCents);
                            $invoice->vatAmount = 0;
                        }
                        $vatAmountCents = $this->saleMoneyCents($invoice->vatAmount);
                        $totalVatCents += $vatAmountCents;
                        if ($totalVatCents > 999999999999) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'grandTotal' => __('sales.amount_exceeds_supported_limit'),
                            ]);
                        }

                        $unitSaleCents = $this->saleMoneyCents($invoice->salePrice);
                        $unitCostCents = $this->saleMoneyCents($purchase->buyPrice ?? 0);
                        $maxStoredCents = 999999999999;
                        if ($unitCostCents < 0 || $unitCostCents > $maxStoredCents
                            || ($unitCostCents > 0 && (int)$item > intdiv($maxStoredCents, $unitCostCents))) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                "purchaseData.$index" => __('sales.amount_exceeds_supported_limit'),
                            ]);
                        }
                        $totalSale      = $this->formatSaleMoney($unitSaleCents * (int)$item);
                        $totalPurchase  = $this->formatSaleMoney($unitCostCents * (int)$item);
                        $profitCents    = ($unitSaleCents - $unitCostCents) * (int)$item;
                        if (abs($profitCents) > $maxStoredCents) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                "salePrice.$index" => __('sales.amount_exceeds_supported_limit'),
                            ]);
                        }
                        $profitTotal    = $this->formatSaleMoney($profitCents);
                        $profitMargin   = $unitCostCents != 0 ? ($profitCents / ($unitCostCents * (int)$item)) * 100 : 0;
                        $profitParcent  = number_format($profitMargin,2);

                        $invoice->totalSale     = $totalSale;
                        $invoice->totalPurchase = $totalPurchase;
                        $invoice->profitTotal   = $profitTotal;
                        $invoice->profitMargin  = $profitParcent;

                        if(!$invoice->save()){
                            throw new \Exception('Failed to save invoice item');
                        }

                        $purchaseId = (int)$requ->purchaseData[$index];
                        $qty = (int)$item;
                        $serialPolicy->sync($sales, $purchaseId, $qty,
                            (array)($serialIdsInput[$index] ?? []), (array)($serialNumbersInput[$index] ?? []),
                            "serialId.$index", false, $isBackorder);

                        // Only decrease stock for non-backorder rows
                        if(!$isBackorder){
                            $ok = $stockService->decreaseStockForSale($purchaseId, $qty);
                            // decreaseStockForSale returns false if stock insufficient or not found
                            if(!$ok){
                                throw new \Exception('Insufficient stock or invalid purchaseId: '.$purchaseId);
                            }
                        }
                    }
                    
                    // Calculate total VAT from all invoice items and save to SaleProduct
                    $sales->totalVat = $this->formatSaleMoney($totalVatCents);
                    $sales->save();
                }
            });

            Alert::success(__('common.notification_success'),__('common.notification_data_saved_successfully'));
            if($createdSaleId > 0){
                $defaultPrinter = (string) config('pos.default_invoice_printer', 'thermal80');
                if(!in_array($defaultPrinter, ['a4', 'thermal80', 'thermal58'], true)){
                    $defaultPrinter = 'thermal80';
                }
                $selectedPrinter = (string) $requ->input('printerProfile', $defaultPrinter);
                if(!in_array($selectedPrinter, ['a4', 'thermal80', 'thermal58'], true)){
                    $selectedPrinter = $defaultPrinter;
                }
                $actor = auth('admin')->user();
                $printerSessionKey = $actor && isset($actor->id)
                    ? ('sale_printer_profile_admin_' . (int)$actor->id)
                    : 'sale_printer_profile_admin_guest';
                session([$printerSessionKey => $selectedPrinter]);
                return redirect()->route('invoiceGenerate', ['id' => $createdSaleId, 'autoprint' => $requ->input('saleAction', 'save_print') === 'save' ? 0 : 1, 'printer' => $selectedPrinter]);
            }
            return back();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            // Log exception
            \Log::error('saveSale transaction failed: '.$e->getMessage());

            $msg = __('common.data_failed');
            // Friendly message for insufficient stock
            if (stripos($e->getMessage(), 'Insufficient stock') !== false) {
                // try to extract purchaseId from message
                $purchaseId = null;
                if (preg_match('/(\d+)$/', $e->getMessage(), $m)) {
                    $purchaseId = (int)$m[1];
                }
                $productName = null;
                if ($purchaseId) {
                    $pp = PurchaseProduct::find($purchaseId);
                    if ($pp) {
                        $productName = $pp->productName;
                    }
                }
                if ($productName) {
                    $msg = __('sales.insufficient_product_stock', ['product'=>$productName]);
                } else {
                    $msg = __('sales.insufficient_stock');
                }
            }

            $message = Alert::error(__('common.notification_sorry'),$msg);
            return back();
        }
    }

    /**
     * Delete a product serial by id (AJAX)
     */
    public function deleteProductSerial($id){
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        DB::transaction(function() use($workflow,$id){
            $workflow->lockSerialNamespace();$serial=ProductSerial::findOrFail($id);
            $line=$workflow->line((int)$serial->purchaseId);$workflow->mutableSerial($line,(int)$id)->delete();
        });
        return response()->json(['status'=>'success','message'=>__('common.api_serial_deleted')]);
    }

    public function updateProductSerial(Request $request,$id){
        $data=$request->validate(['serialNumber'=>['required','string']]);
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        DB::transaction(function() use($workflow,$id,$data){
            $workflow->lockSerialNamespace();$serial=ProductSerial::findOrFail($id);$line=$workflow->line((int)$serial->purchaseId);
            $workflow->serials($line,['serial_updates'=>[$id=>$data['serialNumber']]]);
        });
        return response()->json(['status'=>'success','message'=>__('common.api_serial_updated')]);
    }

    public function addProductSerial(Request $request){
        $data=$request->validate(['purchaseId'=>['required','integer'],'serials'=>['required','array'],'serials.*'=>['nullable','string']]);
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        $created=DB::transaction(function() use($workflow,$data){
            $workflow->lockSerialNamespace();$line=$workflow->line($data['purchaseId']);
            $line=PurchaseProduct::withoutGlobalScopes()->whereKey($line->id)->lockForUpdate()->firstOrFail();
            $before=ProductSerial::where('purchaseId',$line->id)->pluck('id');$workflow->serials($line,['serials'=>$data['serials']]);
            $stock=ProductStock::where('purchaseId',$line->id)->lockForUpdate()->first();
            if(ProductSerial::where('purchaseId',$line->id)->count()>(int)$line->qty || !$stock
                || app(\App\Services\SalesSerialPolicy::class)->available(ProductSerial::where('purchaseId',$line->id))->count()>(int)$stock->currentStock){
                throw \Illuminate\Validation\ValidationException::withMessages(['serials'=>__('purchases.serials_exceed_qty')]);
            }
            return ProductSerial::where('purchaseId',$line->id)->whereNotIn('id',$before)->get(['id','serialNumber']);
        });
        return response()->json(['status'=>'success','created'=>$created,'skipped'=>[]]);
    }

    public function findProductByBarcode(Request $request)
    {
        $barcode = trim($request->input('barcode', ''));
        
        if (empty($barcode)) {
            return response()->json([
                'status' => 'error',
                'message' => __('common.api_barcode_cannot_be_empty')
            ], 400);
        }

        // Resolve authorization outside the lookup catch so unauthorized
        // tenant targets remain controlled 403 responses.
        $businessId = $this->salesBusinessId($request);
        try {
            // A barcode lookup follows the active, server-authorized shop just like
            // customer and category product lists.
            $product = Product::withoutGlobalScopes()->where('businessId', $businessId)
                ->where(function ($query) use ($barcode) {
                    $query->where('barCode', $barcode)
                ->orWhere('barCode', strtoupper($barcode))
                ->orWhere('barCode', strtolower($barcode));
                })
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('common.api_product_not_found'),
                    'barcode'  => $barcode
                ], 404);
            }

            // Barcode uses the same authorized, saleable-batch view as the selector.
            $eligibleBatches = $this->salesProductStockRows($businessId, null, null, (int)$product->id)
                ->filter(fn ($batch) => (int)$batch->currentStock > 0)->values();
            if ($eligibleBatches->isEmpty()) {
                return response()->json(['status'=>'error','message'=>__('sales.insufficient_stock')], 404);
            }
            $currentStock = (int)$eligibleBatches->sum(fn ($batch) => (int)$batch->currentStock);

            // Get brand info
            $brand = Brand::find($product->brand);
            $brandName = $brand ? $brand->name : '';
            $productName = $product->name;
            $productWithBrand = $productName . ($brandName ? ' - ' . $brandName : '');

            // Default price follows purchase chronology among currently eligible batches.
            $lastPurchase = $this->latestEligibleSalesBatch($eligibleBatches);

            $buyPrice = $lastPurchase ? ($lastPurchase->buyPrice ?? '') : '';
            $salePrice = $lastPurchase ? ($lastPurchase->salePriceExVat ?? '') : '';
            $vatStatus = $lastPurchase ? ($lastPurchase->vatStatus ?? '') : '';

            return response()->json([
                'status' => 'success',
                'product' => [
                    'id' => $product->id,
                    'name' => $productWithBrand,
                    'barcode' => $product->barCode,
                    'currentStock' => $currentStock,
                    'buyPrice' => $buyPrice,
                    'salePrice' => $salePrice,
                    'vatStatus' => $vatStatus,
                    'defaultPricePurchaseId' => (int)$lastPurchase->purchaseId,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('findProductByBarcode error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => __('common.api_error_searching_product')
            ], 500);
        }
    }
}
