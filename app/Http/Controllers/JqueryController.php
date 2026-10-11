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
    public function getProductDetails($id){
        $getData = Product::find($id);

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
    public function getSaleProductDetailsPublic($id)
    {
        try {
            // In our schema, `purchase_products.productName` stores the Product ID
            $rows = PurchaseProduct::where('productName', $id)
                ->join('suppliers','purchase_products.supplier','suppliers.id')
                ->join('product_stocks','product_stocks.purchaseId','purchase_products.id')
                ->select(
                    'purchase_products.id as purchaseId',
                    'purchase_products.buyPrice',
                    'purchase_products.salePriceExVat',
                    'purchase_products.vatStatus',
                    'suppliers.name as supplierName',
                    'product_stocks.currentStock'
                )->orderBy('purchaseId','desc')->get();
            if($rows->count() === 0){
                return response()->json(['status'=>'ok','getData'=>[]]);
            }
            $product = Product::find($id);
            $pname = $product ? $product->name : '';
            // Ensure each row includes productName for UI table rendering
            $rows = $rows->map(function($r) use ($pname){
                $r->productName = $pname;
                return $r;
            });
            return response()->json([
                'status' => 'ok',
                'productName' => $pname,
                'getData' => $rows
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

        $options = '<option value="">Select</option>';
        foreach ($products as $p) {
            $options .= '<option value="'.$p->id.'">'.htmlspecialchars($p->name).'</option>';
        }

        return response()->json(['data' => $options]);
    }

    /**
     * Public variant of getProductsForCustomer used by AJAX on the sale page.
     * This intentionally does not require authentication and returns the same
     * option HTML payload so the frontend can populate the select safely.
     */
    public function getProductsForCustomerPublic($customerId)
    {
        // Return ALL purchase rows so the sale product dropdown can list each
        // purchase separately (even when the same product was purchased from
        // the same supplier multiple times). This helps users pick the exact
        // purchase row (purchase_products.id) and ensures stock and pricing
        // are accurate per purchase.
        $rows = \App\Models\PurchaseProduct::join('suppliers','purchase_products.supplier','suppliers.id')
            ->join('products','purchase_products.productName','products.id')
            ->leftJoin('product_stocks','product_stocks.purchaseId','purchase_products.id')
            ->select(
                'purchase_products.id as purchaseId',
                'products.id as productId',
                'products.name as productName',
                'purchase_products.buyPrice',
                'purchase_products.salePriceExVat',
                'purchase_products.purchase_date as purchaseDate',
                'suppliers.name as supplierName',
                'product_stocks.currentStock as currentStock')
            ->orderBy('purchase_products.id','desc')
            ->get();

        $options = '<option value="">Select</option>';
            $outOfStock = [];
            foreach ($rows as $r) {
                $val = 'purchase_' . $r->purchaseId;
                $datePart = '';
                try{ if(!empty($r->purchaseDate)) $datePart = ' ['. \Carbon\Carbon::parse($r->purchaseDate)->format('Y-m-d') .']'; }catch(\Exception $e){}
                $stock = intval($r->currentStock ?: 0);
                // If out of stock, add to separate list and do not include in options
                if($stock <= 0){
                    $outOfStock[] = [
                        'purchaseId' => $r->purchaseId,
                        'productId' => $r->productId ?? null,
                        'productName' => $r->productName,
                        'supplierName' => $r->supplierName,
                        'currentStock' => $stock,
                        'purchaseDate' => !empty($r->purchaseDate) ? \Carbon\Carbon::parse($r->purchaseDate)->format('Y-m-d') : null,
                    ];
                    continue;
                }
                $label = htmlspecialchars($r->productName . ' — ' . ($r->supplierName ?: 'Unknown') . ' (Stock: ' . $stock . ')' . $datePart);
                $titleAttr = ' title="Stock: ' . $stock . '"';
                $options .= '<option value="'.$val.'" data-purchase-id="'.$r->purchaseId.'" data-current-stock="'.$stock.'"'.$titleAttr.'>'.$label.'</option>';
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
        try{
            $p = PurchaseProduct::where('purchase_products.id', $id)
                ->join('products','purchase_products.productName','products.id')
                ->join('suppliers','purchase_products.supplier','suppliers.id')
                ->leftJoin('product_stocks','product_stocks.purchaseId','purchase_products.id')
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
        $getData = Product::find($id);

        if ($getData) {
            $stockHistory = ProductStock::where(['productId' => $getData->id])->get();
            $currentStock = $stockHistory ? $stockHistory->sum('currentStock') : 0;

            $brand = Brand::find($getData->brand);
            $brandName = $brand ? $brand->name : '';
            $productName = $getData->name;
            $productWithBrand = $productName . ($brandName ? ' - ' . $brandName : '');

            $lastPurchase = PurchaseProduct::where('productName', $getData->id)->orderBy('id', 'desc')->first();

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

        return response()->json(['productName' => '', 'currentStock' => 0, 'buyPrice' => '', 'salePrice' => '', 'purchaseDate'=>null, 'message' => __('common.api_not_found'), 'id' => '']);
    }

    /**
     * Public endpoint: return aggregate previous due for a customer.
     * Computes sum of outstanding due for all sales belonging to the customer.
     */
    public function getCustomerPreviousDuePublic($customerId)
    {
        try {
            // Prefer an explicit dueAmount column if present, otherwise compute grandTotal - paidAmount
            $table = 'sale_products';
            $hasDue = Schema::hasColumn($table, 'dueAmount');
            $hasGrand = Schema::hasColumn($table, 'grandTotal');
            $hasTotal = Schema::hasColumn($table, 'totalAmount');

            if ($hasDue) {
                $prevDue = SaleProduct::where('customerId', $customerId)->sum('dueAmount');
            } else if ($hasGrand) {
                $prevDue = SaleProduct::where('customerId', $customerId)
                    ->selectRaw('COALESCE(SUM(COALESCE(grandTotal,0) - COALESCE(paidAmount,0)),0) as s')->value('s');
            } else if ($hasTotal) {
                $prevDue = SaleProduct::where('customerId', $customerId)
                    ->selectRaw('COALESCE(SUM(COALESCE(totalAmount,0) - COALESCE(paidAmount,0)),0) as s')->value('s');
            } else {
                // Fallback: try to compute from known fields if present
                $prevDue = SaleProduct::where('customerId', $customerId)
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
    public function getCategoriesPublic()
    {
        try {
            $cats = \App\Models\Category::orderBy('name','asc')->get(['id','name']);
            $options = '<option value="">All Categories</option>';
            foreach($cats as $c){
                $options .= '<option value="'.$c->id.'">'.htmlspecialchars($c->name).'</option>';
            }
            return response()->json(['data' => $options]);
        } catch (\Throwable $e) {
            \Log::warning('getCategoriesPublic error: '.$e->getMessage());
            return response()->json(['data' => '<option value="">All Categories</option>'], 500);
        }
    }

    /**
     * Public endpoint: return product options filtered by a category id.
     * Options include stock and supplier, consistent with customer products endpoint.
     */
    public function getProductsByCategoryPublic($categoryId)
    {
        try {
            $cat = \App\Models\Category::find($categoryId);
            $catName = $cat ? $cat->name : null;
            $rows = \App\Models\PurchaseProduct::join('suppliers','purchase_products.supplier','suppliers.id')
                ->join('products','purchase_products.productName','products.id')
                ->leftJoin('product_stocks','product_stocks.purchaseId','purchase_products.id')
                ->when($categoryId, function($q) use ($categoryId, $catName){
                    $q->where(function($w) use ($categoryId, $catName){
                        $w->where('products.category', (string)$categoryId);
                        if($catName){ $w->orWhere('products.category', $catName); }
                    });
                })
                ->select(
                    'purchase_products.id as purchaseId',
                    'products.id as productId',
                    'products.name as productName',
                    'purchase_products.buyPrice',
                    'purchase_products.salePriceExVat',
                    'purchase_products.purchase_date as purchaseDate',
                    'suppliers.name as supplierName',
                    'product_stocks.currentStock as currentStock'
                )
                ->orderBy('purchase_products.id','desc')
                ->get();

            $options = '<option value="">Select</option>';
            $outOfStock = [];
            foreach ($rows as $r) {
                $stock = intval($r->currentStock ?: 0);
                if($stock <= 0){
                    $outOfStock[] = [
                        'purchaseId' => $r->purchaseId,
                        'productId' => $r->productId ?? null,
                        'productName' => $r->productName,
                        'supplierName' => $r->supplierName,
                        'currentStock' => $stock,
                        'purchaseDate' => !empty($r->purchaseDate) ? \Carbon\Carbon::parse($r->purchaseDate)->format('Y-m-d') : null,
                    ];
                    continue;
                }
                $val = 'purchase_' . $r->purchaseId;
                $datePart = '';
                try{ if(!empty($r->purchaseDate)) $datePart = ' ['. \Carbon\Carbon::parse($r->purchaseDate)->format('Y-m-d') .']'; }catch(\Exception $e){}
                $label = htmlspecialchars(($r->productName ?: '') . ' — ' . ($r->supplierName ?: 'Unknown') . ' (Stock: ' . $stock . ')' . $datePart);
                $titleAttr = ' title="Stock: ' . $stock . '"';
                $options .= '<option value="'.$val.'" data-purchase-id="'.$r->purchaseId.'" data-current-stock="'.$stock.'"'.$titleAttr.'>'.$label.'</option>';
            }

            return response()->json(['data' => $options, 'outOfStock' => $outOfStock]);
        } catch (\Throwable $e) {
            \Log::warning('getProductsByCategoryPublic error: '.$e->getMessage(), ['categoryId'=>$categoryId]);
            return response()->json(['data' => '<option value="">Select</option>', 'outOfStock' => []], 500);
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
        $canAccessAllBusinesses = in_array(strtolower(trim((string) $actor->role)), ['admin', 'superadmin'], true);
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
        // Validation moved to FormRequest

        // Debug: log incoming payload during test runs to help diagnose failures
        try {
            \Log::debug('savePurchase called', ['payload' => $requ->all()]);
        } catch (\Exception $e) {
            // ignore logging errors in test environment
        }
        try {
            // Ensure an error-level log entry exists so it appears in `storage/logs/laravel.log`
            \Log::error('savePurchase called (error-level)', ['payload' => $requ->all()]);
        } catch (\Exception $e) {}
        // debug logging removed
        // temporary file-based debug removed; framework logging retained above

        $normalizeMoney = function ($value): float {
            if ($value === null || $value === '') {
                return 0.0;
            }
            if (is_string($value)) {
                $value = str_replace([',', ' '], '', $value);
            }
            return (float) $value;
        };

        $rowTotals = $requ->input('totalAmount', []);
        if (!is_array($rowTotals)) {
            $rowTotals = [$rowTotals];
        }

        $baseTotal = 0.0;
        foreach ($rowTotals as $rowTotal) {
            $baseTotal += $normalizeMoney($rowTotal);
        }

        $discountStatus = (string) $requ->input('discountStatus', '');
        $discountAmountInput = $normalizeMoney($requ->input('discountAmount'));
        $discountPercentInput = $normalizeMoney($requ->input('discountPercent'));
        $discountAmount = 0.0;

        if ($discountStatus === '1') {
            $discountAmount = max(0, $discountAmountInput);
        } elseif ($discountStatus === '2') {
            $discountAmount = max(0, ($baseTotal * $discountPercentInput) / 100);
        } else {
            if ($discountAmountInput > 0) {
                $discountAmount = $discountAmountInput;
            } elseif ($discountPercentInput > 0) {
                $discountAmount = max(0, ($baseTotal * $discountPercentInput) / 100);
            }
        }

        $grandTotal = max(0, $baseTotal - $discountAmount);
        $paidAmount = max(0, $normalizeMoney($requ->input('paidAmount')));
        if ($paidAmount > $grandTotal) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'paidAmount' => __('common.notification_paid_amount_cannot_exceed_grand_total'),
            ]);
        }
        $dueAmount = max(0, $grandTotal - $paidAmount);

        // Update path (editing existing purchase). Support single or multiple purchaseId values.
        if(!empty($requ->purchaseId)):
            $purchaseIds = is_array($requ->purchaseId) ? $requ->purchaseId : [$requ->purchaseId];
            $quantities = is_array($requ->quantity) ? $requ->quantity : [$requ->quantity];
            $buyPrices = is_array($requ->get('buyPrice')) ? $requ->get('buyPrice') : [$requ->get('buyPrice')];
            $salePrices = is_array($requ->get('salePriceExVat')) ? $requ->get('salePriceExVat') : [$requ->get('salePriceExVat')];
            $vatStatuses = is_array($requ->get('vatStatus')) ? $requ->get('vatStatus') : [$requ->get('vatStatus')];
            $vatPercents = is_array($requ->get('vatPercent')) ? $requ->get('vatPercent') : [$requ->get('vatPercent')];
            $profitMargins = is_array($requ->get('profitMargin')) ? $requ->get('profitMargin') : [$requ->get('profitMargin')];
            $totals = is_array($requ->get('totalAmount')) ? $requ->get('totalAmount') : [$requ->get('totalAmount')];
            $serialsInput = $requ->input('serialNumber', []);

            DB::beginTransaction();
            try{
                $updatedAny = false;
                foreach($purchaseIds as $idx => $pid){
                    if(!$pid){
                        // If this index has no purchaseId, treat it as new row (create)
                        $productNames = is_array($requ->productName) ? $requ->productName : [$requ->productName];
                        $pId = isset($productNames[$idx]) ? $productNames[$idx] : null;
                        $qty = isset($quantities[$idx]) ? (int)$quantities[$idx] : 0;
                        if(!$pId || $qty <= 0) continue;
                        $purchase = new PurchaseProduct();
                        $purchase->productName      = $pId;
                        $purchase->supplier         = $requ->supplierName;
                        $purchase->purchase_date    = $requ->purchaseDate;
                        // Keep the invoice value identical for the whole purchase.
                        // Do not append a per-row suffix — a single invoice string is sufficient.
                        // If your DB schema enforces a unique constraint on `invoice`, remove it
                        // or adjust the schema to allow multiple rows with the same invoice.
                        $purchase->invoice          = $requ->get('invoiceData') ? $requ->get('invoiceData') : null;
                        $purchase->reference        = $requ->get('refData');
                        $purchase->qty              = $qty;
                        $purchase->buyPrice         = isset($buyPrices[$idx]) ? $buyPrices[$idx] : null;
                        $purchase->salePriceExVat   = isset($salePrices[$idx]) ? $salePrices[$idx] : null;
                        $purchase->vatStatus        = isset($vatStatuses[$idx]) ? $vatStatuses[$idx] : null;
                        $purchase->vatPercent       = isset($vatPercents[$idx]) ? $vatPercents[$idx] : 0;
                        // Calculate salePriceInVat based on salePriceExVat and vatPercent
                        $exVat = floatval(isset($salePrices[$idx]) ? $salePrices[$idx] : 0);
                        $vatPct = floatval(isset($vatPercents[$idx]) ? $vatPercents[$idx] : 0);
                        $isVatIncluded = isset($vatStatuses[$idx]) ? $vatStatuses[$idx] : 0;
                        $purchase->salePriceInVat = ($isVatIncluded && $vatPct > 0 && $exVat > 0) ? round($exVat * (1 + ($vatPct/100)), 2) : $exVat;
                        $purchase->profit           = isset($profitMargins[$idx]) ? $profitMargins[$idx] : null;
                        $purchase->totalAmount      = isset($totals[$idx]) ? $totals[$idx] : null;
                        $purchase->disType          = $requ->get('discountStatus');
                        $purchase->disAmount        = $discountAmount;
                        $purchase->disParcent       = $requ->get('discountPercent');
                        $purchase->grandTotal       = $grandTotal;
                        $purchase->paidAmount       = $paidAmount;
                        $purchase->dueAmount        = $dueAmount;
                        $purchase->specialNote      = $requ->get('specialNote');
                        // Assign businessId from request for admin/superadmin
                        $actor = auth('admin')->user();
                        if($actor && in_array(strtolower($actor->role), ['admin','superadmin'])){
                            $bizId = (int)($requ->input('businessId') ?? 0);
                            if($bizId > 0){ $purchase->businessId = $bizId; }
                        }
                        if(!$purchase->save()) throw new \Exception('Failed to save new purchase row during update');
                        $stock = new ProductStock(); $stock->productId = $pId; $stock->purchaseId = $purchase->id; $stock->currentStock = (int)$qty; 
                        if(isset($purchase->businessId)) { $stock->businessId = $purchase->businessId; }
                        $stock->save();
                        // serials: accept either an array or a comma-separated string per-row
                        $rowSerials = [];
                        if (isset($serialsInput[$idx])) {
                            if (is_array($serialsInput[$idx])) {
                                $rowSerials = $serialsInput[$idx];
                            } else {
                                $rowSerials = array_filter(array_map('trim', explode(',', (string)$serialsInput[$idx])));
                            }
                        }
                        if (!empty($rowSerials)) {
                            foreach ($rowSerials as $s) {
                                $v = trim($s);
                                if ($v === '') continue;
                                if (ProductSerial::where('serialNumber', $v)->exists()) continue;
                                $ns = new ProductSerial();
                                $ns->serialNumber = $v;
                                $ns->productId = $pId;
                                if (Schema::hasColumn('product_serials','purchaseId')) $ns->purchaseId = $purchase->id;
                                $ns->save();
                            }
                        }
                        $updatedAny = true;
                        continue;
                    }

                    $purchase = PurchaseProduct::find($pid);
                    if(!$purchase) continue;
                    $oldQty      = (int)$purchase->qty;
                    $newQty      = isset($quantities[$idx]) ? (int)$quantities[$idx] : $oldQty;
                    $returnedQty = (int)ReturnPurchaseItem::where('purchaseId',$purchase->id)->sum('qty');
                    if($newQty < $returnedQty) continue; // skip invalid update
                    $delta = $newQty - $oldQty;
                        // per-row update debug removed

                    // Update fields
                    // productName array handling
                    $productNames = is_array($requ->productName) ? $requ->productName : [$requ->productName];
                    $pName = isset($productNames[$idx]) ? $productNames[$idx] : $purchase->productName;
                    $purchase->productName      = $pName;
                    $purchase->supplier         = $requ->supplierName;
                    $purchase->purchase_date    = $requ->purchaseDate;
                    // Store the provided invoice string unchanged so rows share the same invoice
                    // for the overall purchase. If a unique DB constraint exists, remove or
                    // update it to allow same invoice for multiple product rows in a purchase.
                    $purchase->invoice          = $requ->get('invoiceData') ? $requ->get('invoiceData') : null;
                    $purchase->reference        = $requ->get('refData');
                    $purchase->qty              = $newQty;
                    $tmp = isset($buyPrices[$idx]) ? $buyPrices[$idx] : $purchase->buyPrice;
                    $purchase->buyPrice = ($tmp === '' ? null : $tmp);
                    $tmp = isset($salePrices[$idx]) ? $salePrices[$idx] : $purchase->salePriceExVat;
                    $purchase->salePriceExVat = ($tmp === '' ? null : $tmp);
                    $purchase->vatStatus        = isset($vatStatuses[$idx]) ? $vatStatuses[$idx] : $purchase->vatStatus;
                    $purchase->vatPercent       = isset($vatPercents[$idx]) ? $vatPercents[$idx] : $purchase->vatPercent;
                    // Calculate salePriceInVat based on salePriceExVat and vatPercent
                    $exVat = floatval($purchase->salePriceExVat ?? 0);
                    $vatPct = floatval($purchase->vatPercent ?? 0);
                    $isVatIncluded = $purchase->vatStatus ? 1 : 0;
                    $purchase->salePriceInVat = ($isVatIncluded && $vatPct > 0 && $exVat > 0) ? round($exVat * (1 + ($vatPct/100)), 2) : $exVat;
                    $tmp = isset($profitMargins[$idx]) ? $profitMargins[$idx] : $purchase->profit;
                    $purchase->profit = ($tmp === '' ? null : $tmp);
                    $tmp = isset($totals[$idx]) ? $totals[$idx] : $purchase->totalAmount;
                    $purchase->totalAmount = ($tmp === '' ? null : $tmp);
                    $purchase->disType          = $requ->get('discountStatus');
                    $purchase->disAmount        = $discountAmount;
                    $purchase->disParcent       = $requ->get('discountPercent');
                    $purchase->grandTotal       = $grandTotal;
                    $purchase->paidAmount       = $paidAmount;
                    $purchase->dueAmount        = $dueAmount;
                    $purchase->specialNote      = $requ->get('specialNote');

                    // Assign/override businessId from request for admin/superadmin (optional on update)
                    $actor = auth('admin')->user();
                    if($actor && in_array(strtolower($actor->role), ['admin','superadmin'])){
                        $bizId = (int)($requ->input('businessId') ?? 0);
                        if($bizId > 0){ $purchase->businessId = $bizId; }
                    }
                    if(!$purchase->save()) throw new \Exception('Failed to update purchase id '.$pid);

                        // debug removed

                    // adjust stock record
                    $stock = ProductStock::where('purchaseId',$purchase->id)->first();
                    if($stock){
                        if($stock->productId != $purchase->productName) $stock->productId = $purchase->productName;
                        $stock->currentStock = max(0, (int)$stock->currentStock + $delta);
                        $stock->save();
                        // debug removed
                    }

                    // attach serials for this row if provided (accept array or comma-separated string)
                    $rowSerials = [];
                    // Try idx and idx+1 (for 1-based indexing used in DOM)
                    $serialKey = null;
                    if (isset($serialsInput[$idx])) {
                        $serialKey = $idx;
                    } elseif (isset($serialsInput[$idx + 1])) {
                        $serialKey = $idx + 1;
                    }
                    if ($serialKey !== null) {
                        if (is_array($serialsInput[$serialKey])) {
                            $rowSerials = $serialsInput[$serialKey];
                        } else {
                            $rowSerials = array_filter(array_map('trim', explode(',', (string)$serialsInput[$serialKey])));
                        }
                    }

                    // VALIDATION: qty must not exceed (existing serials + new unique serials)
                    $existingCount = (int) \App\Models\ProductSerial::where('purchaseId', $purchase->id)->count();
                    $newSet = [];
                    foreach ((array)$rowSerials as $sv) {
                        $v = trim((string)$sv);
                        if ($v !== '') { $newSet[$v] = true; }
                    }
                    $newUnique = array_keys($newSet);
                    $alreadyForThisPurchase = 0;
                    if (!empty($newUnique)) {
                        $alreadyForThisPurchase = (int) \App\Models\ProductSerial::whereIn('serialNumber', $newUnique)
                            ->where('purchaseId', $purchase->id)
                            ->count();
                    }
                    $newUniqueToAdd = max(0, count($newUnique) - $alreadyForThisPurchase);
                    $totalSerialsAfter = $existingCount + $newUniqueToAdd;
                    if ($newQty > $totalSerialsAfter) {
                        throw new \Exception('Quantity ('.$newQty.') exceeds total serials ('.$totalSerialsAfter.') for row '.($idx+1).'.');
                    }

                    if (!empty($rowSerials)) {
                        foreach ($rowSerials as $serialValue) {
                            $v = trim($serialValue);
                            if ($v === '') continue;
                            if (ProductSerial::where('serialNumber', $v)->exists()) continue;
                            $newSerial = new ProductSerial();
                            $newSerial->serialNumber = $v;
                            $newSerial->productId = $purchase->productName;
                            if (Schema::hasColumn('product_serials', 'purchaseId')) $newSerial->purchaseId = $purchase->id;
                            $newSerial->save();
                        }
                    }

                    $updatedAny = true;
                }

                DB::commit();
                if($updatedAny) Alert::success(__('common.notification_success'),__('common.notification_purchase_updated_successfully'));
                else Alert::warning(__('common.notification_no_changes'),__('common.notification_no_valid_rows_were_updated'));
                return back();
            }catch(\Exception $e){
                DB::rollBack();
                // update exception debug removed
                Alert::error(__('common.notification_sorry'),__('common.notification_failed_to_update_purchases').$e->getMessage());
                return back();
            }

        else:
            // Create path: support multi-row purchases. Accept arrays for productName, quantity, buyPrice, etc.
            $productNames = is_array($requ->productName) ? $requ->productName : [$requ->productName];
            $quantities = is_array($requ->quantity) ? $requ->quantity : [$requ->quantity];
            $buyPrices = is_array($requ->get('buyPrice')) ? $requ->get('buyPrice') : [$requ->get('buyPrice')];
            $salePrices = is_array($requ->get('salePriceExVat')) ? $requ->get('salePriceExVat') : [$requ->get('salePriceExVat')];
            $vatStatuses = is_array($requ->get('vatStatus')) ? $requ->get('vatStatus') : [$requ->get('vatStatus')];
            $profitMargins = is_array($requ->get('profitMargin')) ? $requ->get('profitMargin') : [$requ->get('profitMargin')];
            $totals = is_array($requ->get('totalAmount')) ? $requ->get('totalAmount') : [$requ->get('totalAmount')];

            $serialsInput = $requ->input('serialNumber', []);

            DB::beginTransaction();
            try{
                $createdAny = false;
                foreach($productNames as $idx => $prodId){
                    try {
                        \Log::debug('Creating purchase row', ['index' => $idx, 'productId' => $prodId, 'qty' => $quantities[$idx] ?? null]);
                    } catch (\Exception $e) {}
                    $qty = isset($quantities[$idx]) ? (int)$quantities[$idx] : 0;
                    if(!$prodId || $qty <= 0) continue; // skip invalid rows

                    // Duplicate prevention removed: always create a purchase row for each submitted line.

                    $purchase = new PurchaseProduct();
                    $purchase->productName      = $prodId;
                    $purchase->supplier         = $requ->supplierName;
                    $purchase->purchase_date    = $requ->purchaseDate;
                    // For create: assign the provided invoice value directly (no per-row suffix)
                    $purchase->invoice          = $requ->get('invoiceData') ? $requ->get('invoiceData') : null;
                    $purchase->reference        = $requ->get('refData');
                    $purchase->qty              = $qty;
                    $tmp = isset($buyPrices[$idx]) ? $buyPrices[$idx] : null;
                    $purchase->buyPrice = ($tmp === '' ? null : $tmp);
                    $tmp = isset($salePrices[$idx]) ? $salePrices[$idx] : null;
                    $purchase->salePriceExVat = ($tmp === '' ? null : $tmp);
                    $purchase->vatStatus        = isset($vatStatuses[$idx]) ? $vatStatuses[$idx] : null;
                    $purchase->salePriceInVat   = null;
                    $tmp = isset($profitMargins[$idx]) ? $profitMargins[$idx] : null;
                    $purchase->profit = ($tmp === '' ? null : $tmp);
                    $tmp = isset($totals[$idx]) ? $totals[$idx] : null;
                    $purchase->totalAmount = ($tmp === '' ? null : $tmp);
                    // global discount/paid/due stored on each row for compatibility
                    $purchase->disType          = $requ->get('discountStatus');
                    $purchase->disAmount        = $discountAmount;
                    $purchase->disParcent       = $requ->get('discountPercent');
                    $purchase->grandTotal       = $grandTotal;
                    $purchase->paidAmount       = $paidAmount;
                    $purchase->dueAmount        = $dueAmount;
                    $purchase->specialNote      = $requ->get('specialNote');

                    // Assign businessId from request for admin/superadmin
                    $actor = auth('admin')->user();
                    if($actor && in_array(strtolower($actor->role), ['admin','superadmin'])){
                        $bizId = (int)($requ->input('businessId') ?? 0);
                        if($bizId > 0){ $purchase->businessId = $bizId; }
                    }
                    if(!$purchase->save()){
                        throw new \Exception('Failed to save purchase row for product '.$prodId);
                    }

                    try { \Log::debug('Saved purchase', ['purchaseId' => $purchase->id, 'productId' => $prodId, 'qty' => $qty]); } catch (\Exception $e) {}

                    // create stock record
                    $prevStock = new ProductStock();
                    $prevStock->productId    = $prodId;
                    $prevStock->purchaseId   = $purchase->id;
                    $prevStock->currentStock = (int)$qty;
                    if(isset($purchase->businessId)) { $prevStock->businessId = $purchase->businessId; }
                    $prevStock->save();

                    // attach serials for this row if provided as array or comma-separated string
                    \Log::info('savePurchase: processing serials for row', [
                        'idx' => $idx, 
                        'productId' => $prodId,
                        'purchaseId' => $purchase->id,
                        'serialsInput_keys' => array_keys($serialsInput),
                        'serialsInput_raw' => $serialsInput
                    ]);
                    
                    $rowSerials = [];
                    // Try to find serials for this row - check both idx and idx+1 (for 1-based indexing)
                    $serialKey = null;
                    if (isset($serialsInput[$idx])) {
                        $serialKey = $idx;
                    } elseif (isset($serialsInput[$idx + 1])) {
                        $serialKey = $idx + 1;
                    }
                    
                    if ($serialKey !== null) {
                        if (is_array($serialsInput[$serialKey])) {
                            $rowSerials = $serialsInput[$serialKey];
                        } else {
                            $rowSerials = array_filter(array_map('trim', explode(',', (string)$serialsInput[$serialKey])));
                        }
                        \Log::info('savePurchase: found serials for row', [
                            'idx' => $idx,
                            'serialKey' => $serialKey,
                            'rowSerials' => $rowSerials
                        ]);
                    } else {
                        \Log::info('savePurchase: no serials found for row', ['idx' => $idx]);
                    }
                    // VALIDATION: qty must not exceed total provided serials for this row
                    $providedSerialCount = 0;
                    if (!empty($rowSerials)) {
                        $providedSerialCount = count(array_filter(array_map(function($v){ return trim((string)$v) !== '' ? 1 : null; }, $rowSerials)));
                    }
                    if ($qty > $providedSerialCount) {
                        throw new \Exception('Quantity ('.$qty.') exceeds total serials ('.$providedSerialCount.') for row '.($idx+1).'.');
                    }
                    
                    if (!empty($rowSerials)) {
                        $savedCount = 0;
                        $skippedCount = 0;
                        foreach ($rowSerials as $serialValue) {
                            $v = trim($serialValue);
                            if ($v === '') continue;
                            // skip duplicates
                            if (ProductSerial::where('serialNumber', $v)->exists()) {
                                $skippedCount++;
                                \Log::info('savePurchase: skipped duplicate serial', ['serial' => $v]);
                                continue;
                            }
                            $newSerial = new ProductSerial();
                            $newSerial->serialNumber = $v;
                            $newSerial->productId = $prodId;
                            if (Schema::hasColumn('product_serials', 'purchaseId')) {
                                $newSerial->purchaseId = $purchase->id;
                            }
                            $newSerial->save();
                            $savedCount++;
                            \Log::info('savePurchase: saved new serial', [
                                'serial' => $v,
                                'serialId' => $newSerial->id,
                                'purchaseId' => $purchase->id,
                                'productId' => $prodId
                            ]);
                        }
                        \Log::info('savePurchase: serial save summary for row', [
                            'idx' => $idx,
                            'saved' => $savedCount,
                            'skipped' => $skippedCount,
                            'total' => count($rowSerials)
                        ]);
                    }

                    $createdAny = true;
                }

                DB::commit();

                if($createdAny){
                    Alert::success(__('common.notification_success'),__('common.notification_data_saved_successfully'));
                } else {
                    Alert::warning(__('common.notification_no_rows'),__('common.notification_no_valid_purchase_rows_to_save_or_duplicates_skipped'));
                }
                try {
                    $count = \App\Models\PurchaseProduct::count();
                    \Log::info('create_result', ['createdAny' => $createdAny ? 1 : 0, 'purchase_count' => $count]);
                } catch (\Exception $e) { \Log::warning('create_result_log_failed', ['error' => $e->getMessage()]); }
                return back();
            }catch(\Exception $e){
                DB::rollBack();
                try { \Log::error('create_exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]); } catch (\Exception $_) {}
                Alert::error(__('common.notification_sorry'),__('common.notification_data_failed_to_save').$e->getMessage());
                return back();
            }
        endif; // end create/update branching
    }

    //service
    public function getserviceDetails($id){
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

        // Extract VAT data from request (same pattern as savePurchase)
        $vatPercents = is_array($requ->get('vatPercent')) ? $requ->get('vatPercent') : [$requ->get('vatPercent')];
        $vatIncludeds = is_array($requ->get('includeVat')) ? $requ->get('includeVat') : [$requ->get('includeVat')];

        try {
            DB::transaction(function() use ($requ, $items, $stockService, $invoiceService, $serialPolicy, $serialIdsInput, $serialNumbersInput, $vatPercents, $vatIncludeds, &$createdSaleId) {
                $sales = new SaleProduct();
                // Generate a sequenced sale invoice (replaces client-provided invoice)
                $sales->invoice         = $invoiceService->generateSaleInvoice();
                $sales->date            = $requ->date;
                $sales->customerId      = $requ->customerId;
                $sales->reference       = $requ->reference;
                $sales->note            = $requ->note;
                $sales->totalSale       = $requ->totalSaleAmount;
                $sales->discountAmount  = $requ->discountAmount;
                // Persist optional additional charge (e.g., service charge)
                try{
                    $sales->additionalChargeName   = $requ->input('additionalChargeName');
                    $sales->additionalChargeAmount = $requ->input('additionalChargeAmount');
                }catch(\Throwable $_){ /* ignore */ }
                $sales->grandTotal      = $requ->grandTotal;
                $sales->paidAmount      = $requ->paidAmount;
                $sales->invoiceDue      = $requ->dueAmount;
                $sales->prevDue         = $requ->prevDue;
                $sales->curDue          = $requ->curDue;
                $sales->status          = "Ordered";
                // Assign businessId from request for admin/superadmin
                $actor = auth('admin')->user();
                if($actor && in_array(strtolower($actor->role), ['admin','superadmin'])){
                    $bizId = (int)($requ->input('businessId') ?? 0);
                    if($bizId > 0){ $sales->businessId = $bizId; }
                }
                if ($actor && !in_array(strtolower(trim($actor->role)), ['admin','superadmin'], true)) {
                    $sales->businessId = $actor->businessId;
                }
                if (!$sales->businessId) {
                    $sales->businessId = PurchaseProduct::withoutGlobalScopes()->find($requ->purchaseData[0] ?? 0)?->businessId;
                }
                // Record salesperson (the admin user who created this sale)
                try{ if($actor && isset($actor->id)){ $sales->salespersonId = $actor->id; } }catch(\Throwable $_){}

                if(!$sales->save()){
                    throw new \Exception('Failed to save sale');
                }
                $createdSaleId = (int)$sales->id;

                $seenPurchases = [];
                foreach ($requ->purchaseData as $row => $pid) {
                    $purchase = $serialPolicy->purchase((int)$pid, $sales->businessId, "purchaseData.$row");
                    if (isset($seenPurchases[(int)$pid]) && $serialPolicy->serialized($purchase)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(["purchaseData.$row" => __('common.notification_combine_duplicate_serialized_purchase_rows_into_one_quantity')]);
                    }
                    $seenPurchases[(int)$pid] = true;
                }

                $backorders = $requ->backorder ?? [];
                if(!is_array($backorders)) $backorders = [$backorders];

                if(count($items)>0){
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
                        $invoice->salePrice = $requ->salePrice[$index];
                        $invoice->buyPrice = $requ->buyPrice[$index];
                        
                        // Set VAT data from request
                        $vatPct = isset($vatPercents[$index]) ? floatval($vatPercents[$index]) : 0;
                        $isVatIncluded = isset($vatIncludeds[$index]) ? $vatIncludeds[$index] : 0;
                        $invoice->vatPercent = $vatPct;
                        $invoice->vatIncluded = $isVatIncluded ? 1 : 0;
                        
                        // Calculate VAT amount and salePriceIncVat
                        $basePrice = floatval($requ->salePrice[$index]);
                        if($isVatIncluded && $vatPct > 0) {
                            $invoice->salePriceIncVat = round($basePrice * (1 + ($vatPct / 100)), 2);
                            $invoice->vatAmount = round($basePrice * ($vatPct / 100) * $item, 2);
                        } else {
                            $invoice->salePriceIncVat = $basePrice;
                            $invoice->vatAmount = 0;
                        }

                        $totalSale      = $requ->salePrice[$index]*$item;
                        $totalPurchase  = $requ->buyPrice[$index]*$item;
                        $profitTotal    = $totalSale - $totalPurchase;
                        $profitMargin   = $totalPurchase != 0 ? ($profitTotal / $totalPurchase) * 100 : 0;
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
                    $totalVat = InvoiceItem::where('saleId', $sales->id)->sum('vatAmount');
                    $sales->totalVat = $totalVat;
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
                return redirect()->route('invoiceGenerate', ['id' => $createdSaleId, 'autoprint' => 1, 'printer' => $selectedPrinter]);
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
    public function deleteProductSerial($id)
    {
        $serial = ProductSerial::find($id);
        if (!$serial) {
            return response()->json(['status' => 'error', 'message' => __('common.api_serial_not_found')], 404);
        }

        try {
            $serial->delete();
            return response()->json(['status' => 'success', 'message' => __('common.api_serial_deleted')]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => __('common.api_failed_to_delete_serial')], 500);
        }
    }

    /**
     * Update a product serial by id (AJAX)
     */
    public function updateProductSerial(Request $request, $id)
    {
        \Log::info('updateProductSerial called', ['id' => $id, 'input' => $request->all()]);
        
        $serial = ProductSerial::find($id);
        if (!$serial) {
            \Log::warning('updateProductSerial: Serial not found', ['id' => $id]);
            return response()->json(['status' => 'error', 'message' => __('common.api_serial_not_found')], 404);
        }

        \Log::info('updateProductSerial: Found serial', ['oldValue' => $serial->serialNumber]);

        $request->validate([
            'serialNumber' => 'required|string'
        ]);

        try {
            $oldValue = $serial->serialNumber;
            $serial->serialNumber = $request->input('serialNumber');
            $serial->save();
            \Log::info('updateProductSerial: Serial updated successfully', [
                'id' => $id,
                'oldValue' => $oldValue,
                'newValue' => $serial->serialNumber
            ]);
            return response()->json(['status' => 'success', 'message' => __('common.api_serial_updated')]);
        } catch (\Exception $e) {
            \Log::error('updateProductSerial: Failed to update', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => __('common.api_failed_to_update_serial') . $e->getMessage()], 500);
        }
    }

    /**
     * Add product serials via AJAX for an existing purchase
     */
    public function addProductSerial(Request $request)
    {
        $request->validate([
            'purchaseId' => 'required|integer',
            'serials' => 'required|array',
            'serials.*' => 'nullable|string'
        ]);

        $purchase = PurchaseProduct::find($request->purchaseId);
        if (!$purchase) {
            return response()->json(['status' => 'error', 'message' => __('common.api_purchase_not_found')], 404);
        }

        $created = [];
        $skipped = [];
        $serials = $request->input('serials', []);
        foreach ($serials as $val) {
            $v = trim($val);
            if ($v === '') continue;
            // skip if already exists
            $exists = ProductSerial::where('serialNumber', $v)->first();
            if ($exists) {
                $skipped[] = $v;
                continue;
            }
            $newSerial = new ProductSerial();
            $newSerial->serialNumber = $v;
            // link to the product on this purchase
            $newSerial->productId = $purchase->productName;
            if (Schema::hasColumn('product_serials', 'purchaseId')) {
                $newSerial->purchaseId = $purchase->id;
            }
            $newSerial->save();
            $created[] = ['id' => $newSerial->id, 'serialNumber' => $newSerial->serialNumber];
        }

        return response()->json(['status' => 'success', 'created' => $created, 'skipped' => $skipped]);
    }

    /**
     * Find product by barcode (AJAX)
     * Returns product details including pricing and stock
     */
    public function findProductByBarcode(Request $request)
    {
        $barcode = trim($request->input('barcode', ''));
        
        if (empty($barcode)) {
            return response()->json([
                'status' => 'error',
                'message' => __('common.api_barcode_cannot_be_empty')
            ], 400);
        }

        try {
            // Find product by barcode
            $product = Product::where('barCode', $barcode)
                ->orWhere('barCode', strtoupper($barcode))
                ->orWhere('barCode', strtolower($barcode))
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('common.api_product_not_found'),
                    'barcode'  => $barcode
                ], 404);
            }

            // Get stock and pricing info
            $stockHistory = ProductStock::where('productId', $product->id)->get();
            $currentStock = $stockHistory ? $stockHistory->sum('currentStock') : 0;

            // Get brand info
            $brand = Brand::find($product->brand);
            $brandName = $brand ? $brand->name : '';
            $productName = $product->name;
            $productWithBrand = $productName . ($brandName ? ' - ' . $brandName : '');

            // Get latest purchase pricing
            $lastPurchase = PurchaseProduct::where('productName', $product->id)
                ->orderBy('id', 'desc')
                ->first();

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
                    'vatStatus' => $vatStatus
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
