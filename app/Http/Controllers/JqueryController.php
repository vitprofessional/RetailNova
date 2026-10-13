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
    private function salesCustomer(int $customerId): \App\Models\Customer
    {
        $actor = auth('admin')->user();
        abort_unless($actor, 401);
        $customer = \App\Models\Customer::withoutGlobalScope('business')->find($customerId);
        $legacyWalking = $customer && $customer->businessId === null && $customer->name === 'Walking Customer';
        abort_if(!$customer || (!$actor->canAccessAllSalesBusinesses()
            && ((int)$actor->businessId <= 0 || (!$legacyWalking && (int)$customer->businessId !== (int)$actor->businessId))), 404);
        return $customer;
    }
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

        $options = '<option value="">'.__('sales.select').'</option>';
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
        $customer = $this->salesCustomer((int)$customerId);
        $businessId = $customer->businessId ?: auth('admin')->user()->businessId;
        // Return ALL purchase rows so the sale product dropdown can list each
        // purchase separately (even when the same product was purchased from
        // the same supplier multiple times). This helps users pick the exact
        // purchase row (purchase_products.id) and ensures stock and pricing
        // are accurate per purchase.
        $rows = \App\Models\PurchaseProduct::join('suppliers','purchase_products.supplier','suppliers.id')
            ->join('products','purchase_products.productName','products.id')
            ->leftJoin('product_stocks','product_stocks.purchaseId','purchase_products.id')
            ->when($businessId, fn($query) => $query->where('purchase_products.businessId', $businessId))
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

        $options = '<option value="">'.__('sales.select').'</option>';
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
                $label = htmlspecialchars($r->productName . ' — ' . ($r->supplierName ?: __('javascript.sale_unknown_supplier')) . ' ('.__('common.stock').': ' . $stock . ')' . $datePart);
                $titleAttr = ' title="'.htmlspecialchars(__('common.stock')).': ' . $stock . '"';
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
        $this->salesCustomer((int)$customerId);
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
            $options = '<option value="">'.__('sales.all_categories').'</option>';
            foreach($cats as $c){
                $options .= '<option value="'.$c->id.'">'.htmlspecialchars($c->name).'</option>';
            }
            return response()->json(['data' => $options]);
        } catch (\Throwable $e) {
            \Log::warning('getCategoriesPublic error: '.$e->getMessage());
            return response()->json(['data' => '<option value="">'.__('sales.all_categories').'</option>'], 500);
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

            $options = '<option value="">'.__('sales.select').'</option>';
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
                $label = htmlspecialchars(($r->productName ?: '') . ' — ' . ($r->supplierName ?: __('javascript.sale_unknown_supplier')) . ' ('.__('common.stock').': ' . $stock . ')' . $datePart);
                $titleAttr = ' title="'.htmlspecialchars(__('common.stock')).': ' . $stock . '"';
                $options .= '<option value="'.$val.'" data-purchase-id="'.$r->purchaseId.'" data-current-stock="'.$stock.'"'.$titleAttr.'>'.$label.'</option>';
            }

            return response()->json(['data' => $options, 'outOfStock' => $outOfStock]);
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
                $legacyWalking = $customer && $customer->businessId === null && $customer->name === 'Walking Customer';
                if (!$customer || (!$legacyWalking && (int)$customer->businessId !== (int)$sales->businessId)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['customerId' => __('sales.customer_unavailable_for_business')]);
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
