<?php

namespace App\Http\Controllers;
use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleProduct;
use App\Models\InvoiceItem;
use App\Models\ProductSerial;
use Alert;
use App\Models\returnSaleProduct;
use App\Models\returnInvoiceItem;
use App\Models\SaleReturn;
use App\Models\ProductStock;
use App\Models\ReturnSaleItem;
use App\Services\StockService;
use App\Services\SaleCancellation;
use App\Services\SaleCancellationException;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class saleController extends Controller
{
    private function hasAdminPrivileges($actor): bool
    {
        if (!$actor) {
            return false;
        }

        if (method_exists($actor, 'hasSuperAdminPrivileges')) {
            return (bool) $actor->hasSuperAdminPrivileges();
        }

        $normalizedRole = preg_replace('/[^a-z]/', '', strtolower((string) ($actor->role ?? '')));
        return in_array($normalizedRole, ['admin', 'superadmin'], true);
    }

    Public function newsale (){
        // Ensure a default Walking Customer exists for quick walk-in sales
        try{ $walking = Customer::ensureWalkingCustomer(); }catch(\Throwable $e){ $walking = null; }
        $customer = Customer::orderBy('id','DESC')->get();
        $product = Product::orderBy('id','DESC')->get();
        $businesses = \App\Models\BusinessSetup::orderBy('id','asc')->get();
        $defaultPrinter = (string) config('pos.default_invoice_printer', 'thermal80');
        if(!in_array($defaultPrinter, ['a4','thermal80','thermal58'], true)){
            $defaultPrinter = 'thermal80';
        }
        $actor = auth('admin')->user();
        $printerSessionKey = $actor && isset($actor->id)
            ? ('sale_printer_profile_admin_' . (int)$actor->id)
            : 'sale_printer_profile_admin_guest';
        $rememberedPrinter = (string) session($printerSessionKey, $defaultPrinter);
        if(!in_array($rememberedPrinter, ['a4','thermal80','thermal58'], true)){
            $rememberedPrinter = $defaultPrinter;
        }
        // Generate a random invoice number (format: INV + ymd + 4 random digits)
        $randomInvoiceNumber = 'INV' . date('ymd') . rand(1000,9999);
        return view('sale.newsale',[
            'customerList'=>$customer,
            'productList'=>$product,
            'walkingCustomerId' => $walking ? $walking->id : null,
            'businesses' => $businesses,
            'randomInvoiceNumber' => $randomInvoiceNumber,
            'defaultInvoicePrinter' => $rememberedPrinter,
        ]);

    }

    public function saleList(){
        $actor = auth('admin')->user();
        $query = SaleProduct::with('salesperson')->orderBy('id','desc');
        // Non-admin users should only see their own generated bills
        if($actor && !$this->hasAdminPrivileges($actor)){
            $query->where('salespersonId', $actor->id);
        }
        $saleList = $query->get();
        return view('sale.saleList',[
            'saleList' => $saleList,
            'canCancelSales' => $actor instanceof AdminUser && $actor->canCancelSales(),
        ]);
    }

    /**
     * Edit a sale: allow updating paid amount and date; due is recalculated.
     */
    public function editSale($id)
    {
        $sale = SaleProduct::find($id);
        if(!$sale){
            Alert::error(__('common.notification_error'), __('common.notification_sale_not_found'));
            return back();
        }
        // Ownership check: non-admins can only edit their own sales
        $actor = auth('admin')->user();
        if($actor && !$this->hasAdminPrivileges($actor) && (int)$sale->salespersonId !== (int)$actor->id){
            Alert::error(__('common.notification_unauthorized'),__('common.notification_you_are_not_allowed_to_edit_this_sale'));
            return back();
        }
        $customer = Customer::find($sale->customerId);
        return view('sale.editSale', ['sale' => $sale, 'customer' => $customer]);
    }

    /**
     * Edit sale items: quantities and prices, with stock reconciliation.
     */
    public function editSaleItems($id)
    {
        $sale = SaleProduct::find($id);
        if(!$sale){
            Alert::error(__('common.notification_error'), __('common.notification_sale_not_found'));
            return back();
        }
        // Ownership check
        $actor = auth('admin')->user();
        // The legacy first-user privilege must not expose another business's serials.
        if ($actor && !$actor->canAccessAllSalesBusinesses()
            && (int)$sale->businessId > 0 && (int)$sale->businessId !== (int)$actor->businessId) {
            abort(403, 'This sale belongs to another business.');
        }
        if($actor && !$this->hasAdminPrivileges($actor) && (int)$sale->salespersonId !== (int)$actor->id){
            Alert::error(__('common.notification_unauthorized'),__('common.notification_you_are_not_allowed_to_edit_items_of_this_sale'));
            return back();
        }
        $customer = Customer::find($sale->customerId);
        // Load items with purchase and product name
        $items = InvoiceItem::where('saleId', $sale->id)
            ->join('purchase_products','purchase_products.id','=','invoice_items.purchaseId')
            ->join('products','products.id','=','purchase_products.productName')
            ->leftJoin('product_stocks','product_stocks.purchaseId','=','purchase_products.id')
            ->select(
                'invoice_items.id as id',
                'invoice_items.saleId as saleId',
                'invoice_items.purchaseId as purchaseId',
                'products.name as productName',
                'invoice_items.qty as qty',
                'invoice_items.warranty_days as warranty_days',
                'invoice_items.salePrice as salePrice',
                'invoice_items.buyPrice as buyPrice',
                'invoice_items.totalSale as totalSale',
                DB::raw('COALESCE(product_stocks.currentStock, 0) as currentStock')
            )
            ->orderBy('products.name')
            ->get();
        // Gather currently assigned serials for this sale keyed by purchaseId
        $soldSerials = \App\Models\ProductSerial::where('saleId', $sale->id)
            ->orderBy('id')
            ->get(['id','serialNumber','purchaseId'])
            ->groupBy('purchaseId');
        $soldSerialsByPurchase = [];
        foreach($soldSerials as $pid => $rows){
            $soldSerialsByPurchase[$pid] = $rows->map(function($r){ return ['id'=>$r->id, 'serialNumber'=>$r->serialNumber]; })->toArray();
        }
        return view('sale.editSaleItems', ['sale' => $sale, 'customer' => $customer, 'items' => $items, 'soldSerialsByPurchase' => $soldSerialsByPurchase]);
    }

    /**
     * Update sale paid amount and date; recompute current due.
     * Also allows updating payment mode and remarks.
     */
    public function updateSale(Request $req, $id)
    {
        $sale = SaleProduct::find($id);
        if(!$sale){
            Alert::error(__('common.notification_error'), __('common.notification_sale_not_found'));
            return back();
        }

        // Ownership check
        $actor = auth('admin')->user();
        if($actor && !$this->hasAdminPrivileges($actor) && (int)$sale->salespersonId !== (int)$actor->id){
            Alert::error(__('common.notification_unauthorized'),__('common.notification_you_are_not_allowed_to_update_this_sale'));
            return back();
        }

        // Base validation
        $validator = \Validator::make($req->all(), [
            'paidAmount'  => 'required|numeric|min:0',
            'date'        => 'nullable|date',
            'note'        => 'nullable|string|max:1000',
        ]);

        $grand = (float)($sale->grandTotal ?? $sale->totalAmount ?? 0);
        $validator->after(function($v) use ($grand) {
            $paid = (float)request('paidAmount');
            if($paid > $grand){
                $v->errors()->add('paidAmount', __('sales.payment_exceeds', ['total'=>number_format($grand,2)]));
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Apply updates
        $sale->paidAmount = (float)$data['paidAmount'];
        // Persist due into commonly-used field names
        $sale->curDue     = max(0, $grand - (float)$sale->paidAmount);
        // Align with create-sale schema: use invoiceDue instead of dueAmount
        $sale->invoiceDue = $sale->curDue;

        if(isset($data['date']) && $data['date']){
            $sale->date = $data['date']; // schema uses `date`
        }

        if(array_key_exists('note', $data)){
            $sale->note = $data['note'];
        }

        if($sale->save()){
            Alert::success(__('common.notification_updated'),__('common.notification_sale_updated_successfully'));
            return redirect()->route('saleList');
        }
        Alert::error(__('common.notification_error'),__('common.notification_failed_to_update_sale'));
        return back();
    }

    /**
     * Update sale items (quantities/prices) and reconcile stock; recompute totals/due.
     */
    public function updateSaleItems(Request $req, $id)
    {
        $sale = SaleProduct::find($id);
        if(!$sale){
            Alert::error(__('common.notification_error'), __('common.notification_sale_not_found'));
            return back();
        }

        // Ownership check
        $actor = auth('admin')->user();
        if($actor && !$this->hasAdminPrivileges($actor) && (int)$sale->salespersonId !== (int)$actor->id){
            Alert::error(__('common.notification_unauthorized'),__('common.notification_you_are_not_allowed_to_update_items_of_this_sale'));
            return back();
        }

        if ($actor && !$actor->canAccessAllSalesBusinesses()
            && ((int)$actor->businessId <= 0 || (int)$sale->businessId !== (int)$actor->businessId)) {
            abort(403, 'This sale belongs to another business.');
        }

        $itemsInput = (array)$req->input('items', []);
        $addInput   = (array)$req->input('add', []);

        $service = new StockService();
        $serialPolicy = app(\App\Services\SalesSerialPolicy::class);
        $req->validate([
            'items' => ['sometimes','array'], 'items.*.qty' => ['sometimes','integer','min:0'],
            'items.*.salePrice' => ['sometimes','numeric','min:0'], 'add.qty' => ['nullable','integer','min:1'],
            'serialIdByItem' => ['sometimes','array'], 'serialIdByItem.*' => ['array'], 'serialIdByItem.*.*' => ['integer','min:1'],
            'serialIdByPurchase' => ['sometimes','array'], 'serialIdByPurchase.*' => ['array'], 'serialIdByPurchase.*.*' => ['integer','min:1'],
        ]);

        DB::beginTransaction();
        try {
            // Keep the soft-delete scope: a sale cancelled meanwhile must not be edited.
            $sale = SaleProduct::withoutGlobalScope('business')->lockForUpdate()->findOrFail($sale->id);
            // Allocation is sale + purchase based. Do not corrupt ambiguous legacy duplicate lines.
            $duplicates = InvoiceItem::where('saleId', $sale->id)->select('purchaseId')
                ->groupBy('purchaseId')->havingRaw('COUNT(*) > 1')->pluck('purchaseId');
            foreach ($duplicates as $purchaseId) {
                if (\App\Models\ProductSerial::where('purchaseId', $purchaseId)->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => __('common.notification_this_legacy_invoice_has_duplicate_serialized_purchase_lines_review_its_allocations_before_editing')]);
                }
            }
            // Update existing items
            foreach($itemsInput as $itemId => $payload){
                $payloadQty  = isset($payload['qty']) ? (int)$payload['qty'] : null;
                $payloadPrice= isset($payload['salePrice']) ? (float)$payload['salePrice'] : null;
                $payloadWarranty = isset($payload['warranty_days']) ? (string)$payload['warranty_days'] : null;
                $invItem = InvoiceItem::lockForUpdate()->find($itemId);
                if(!$invItem || $invItem->saleId != $sale->id){
                    continue;
                }
                $oldQty = (int)$invItem->qty;
                $newQty = is_null($payloadQty) ? $oldQty : max(0, $payloadQty);

                // Handle qty change and stock reconciliation
                $serialPolicy->purchase((int)$invItem->purchaseId, $sale->businessId, "items.$itemId");
                if($newQty !== $oldQty && !$invItem->isBackorder){
                    $delta = $newQty - $oldQty;
                    if($delta > 0){
                        // Need to consume additional stock
                        $ok = $service->decreaseStockForSale((int)$invItem->purchaseId, $delta);
                        if(!$ok){
                            throw new \Exception(__('sales.edit_stock_insufficient', ['item' => $invItem->id]));
                        }
                    } elseif($delta < 0){
                        // Return stock for reduced quantity
                        if (!$service->increaseStockForSaleReturn((int)$invItem->purchaseId, abs($delta))) {
                            throw new \Exception(__('sales.edit_stock_restore_failed'));
                        }
                    }
                    $invItem->qty = $newQty;
                }

                $invItem->qty = $newQty;

                // Update price if provided
                if(!is_null($payloadPrice) && $payloadPrice >= 0){
                    $invItem->salePrice = $payloadPrice;
                }

                // Update warranty if provided (free-text, e.g., number of days)
                if(!is_null($payloadWarranty)){
                    $invItem->warranty_days = trim($payloadWarranty);
                }

                $selected = (array)$req->input('serialIdByItem.'.(int)$invItem->id,
                    $req->input('serialIdByPurchase.'.(int)$invItem->purchaseId, []));
                $serialPolicy->sync($sale, (int)$invItem->purchaseId, $newQty, $selected, [],
                    'serialIdByItem.'.(int)$invItem->id, true, (bool)$invItem->isBackorder);

                // Recompute totals
                $invItem->totalSale     = (float)$invItem->salePrice * (int)$invItem->qty;
                $invItem->totalPurchase = (float)$invItem->buyPrice * (int)$invItem->qty;
                $invItem->profitTotal   = (float)$invItem->totalSale - (float)$invItem->totalPurchase;
                $invItem->profitMargin  = $invItem->totalSale > 0 ? round(($invItem->profitTotal / $invItem->totalSale) * 100, 2) : 0;

                // If quantity became 0, delete the line to keep invoice clean
                // (permanently, as before; soft delete is reserved for cancelled sales)
                if((int)$invItem->qty === 0){
                    $invItem->forceDelete();
                } else {
                    $invItem->save();
                }
            }

            // Optionally add a new item
            if(!empty($addInput)){
                $purchaseId = (int)($addInput['purchaseId'] ?? 0);
                $qty        = (int)($addInput['qty'] ?? 0);
                $salePrice  = (float)($addInput['salePrice'] ?? 0);
                $warrantyDays = isset($addInput['warranty_days']) ? (string)$addInput['warranty_days'] : null;
                if($purchaseId > 0 && $qty > 0 && $salePrice > 0){
                    $serialPolicy->purchase($purchaseId, $sale->businessId, 'add.purchaseId');
                    if (InvoiceItem::where('saleId', $sale->id)->where('purchaseId', $purchaseId)->exists()
                        && \App\Models\ProductSerial::where('purchaseId', $purchaseId)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['add.purchaseId' => __('common.notification_update_the_existing_serialized_purchase_line_instead_of_adding_it_twice')]);
                    }
                    $ok = $service->decreaseStockForSale($purchaseId, $qty);
                    if(!$ok){
                        throw new \Exception(__('sales.edit_new_stock_insufficient', ['purchase' => $purchaseId]));
                    }
                    $purchase = \App\Models\PurchaseProduct::find($purchaseId);
                    if(!$purchase){
                        throw new \Exception(__('sales.edit_purchase_missing'));
                    }
                    $invItem = new InvoiceItem();
                    $invItem->saleId      = $sale->id;
                    $invItem->businessId = $sale->businessId;
                    $invItem->purchaseId  = $purchaseId;
                    $invItem->qty         = $qty;
                    $invItem->salePrice   = $salePrice;
                    $invItem->buyPrice    = (float)($purchase->buyPrice ?? 0);
                    if(!is_null($warrantyDays)) { $invItem->warranty_days = trim($warrantyDays); }
                    $invItem->totalSale     = (float)$invItem->salePrice * (int)$invItem->qty;
                    $invItem->totalPurchase = (float)$invItem->buyPrice * (int)$invItem->qty;
                    $invItem->profitTotal   = (float)$invItem->totalSale - (float)$invItem->totalPurchase;
                    $invItem->profitMargin  = $invItem->totalSale > 0 ? round(($invItem->profitTotal / $invItem->totalSale) * 100, 2) : 0;
                    $invItem->save();

                    $serialPolicy->sync($sale, $purchaseId, $qty,
                        (array)$req->input('serialIdByPurchase.'.$purchaseId, []), [], 'serialIdByPurchase.'.$purchaseId);

                }
            }

            // Recompute sale totals
            $sum = (float)InvoiceItem::where('saleId', $sale->id)->sum('totalSale');
            $discountReq = (float)$req->input('discountAmount', $sale->discountAmount ?? 0);
            if($discountReq < 0){ $discountReq = 0; }
            if($discountReq > $sum){ $discountReq = $sum; }
            $grand = max(0, $sum - $discountReq);
            // Persist totals/due (align to create-sale fields)
            $sale->totalSale     = $sum;
            $sale->grandTotal    = $grand;
            $sale->discountAmount= $discountReq;
            $paid = (float)($sale->paidAmount ?? 0);
            $sale->curDue        = max(0, $grand - $paid);
            $sale->invoiceDue    = $sale->curDue;
            $sale->save();

            DB::commit();
            Alert::success(__('common.notification_updated'), __('common.notification_sale_items_updated_successfully'));
            return redirect()->route('invoiceGenerate', ['id' => $sale->id]);
        } catch(\Exception $e){
            DB::rollBack();
            \Log::error('updateSaleItems failed: '.$e->getMessage());
            if ($e instanceof \Illuminate\Validation\ValidationException) { throw $e; }
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }
    }

    public function invoiceGenerate($id){
        $invoice = SaleProduct::find($id);
        if($invoice):
            // Ownership check for viewing invoices
            $actor = auth('admin')->user();
            if ($actor && !$actor->canAccessAllSalesBusinesses()
                && ((int)$invoice->businessId <= 0 || (int)$invoice->businessId !== (int)$actor->businessId)) {
                abort(403, 'This sale belongs to another business.');
            }
            if($actor && !$this->hasAdminPrivileges($actor) && (int)$invoice->salespersonId !== (int)$actor->id){
                Alert::error(__('common.notification_unauthorized'),__('common.notification_you_are_not_allowed_to_view_this_invoice'));
                return back();
            }
            $customer = Customer::find($invoice->customerId);
            $items = InvoiceItem::where(['saleId'=>$id])
            ->join('purchase_products','purchase_products.id','invoice_items.purchaseId')
            ->join('products','products.id','purchase_products.productName')
            ->leftJoin('product_units','product_units.id','=','products.unitName')
            ->select(
                'purchase_products.id as purchaseId',
                'products.id as productId',
                'products.name as productName',
                // Professional POS code: use product barcode
                DB::raw('COALESCE(NULLIF(products.barCode, ""), NULL) as productCode'),
                // Unit display name if available
                'product_units.name as unit',
                'invoice_items.id as invoiceId',
                'invoice_items.salePrice',
                'invoice_items.buyPrice',
                'invoice_items.qty',
                'invoice_items.warranty_days',
                'invoice_items.totalSale',
            )->orderBy('totalSale','desc')->get();

            // Fetch sold serials for this sale grouped by purchase row for quick lookup
            $serialsByPurchase = ProductSerial::where('saleId', $id)
                ->select('purchaseId','serialNumber')
                ->orderBy('id')
                ->get()
                ->groupBy('purchaseId');
            // Invoice branding always follows the owning sale, never session order or
            // the first configured business. A NULL-owned legacy sale has no safe brand.
            $business = $invoice->business;
            return view('invoice.invoicePage',[ 'invoice'=>$invoice, 'items'=>$items, 'customer'=>$customer, 'business' => $business, 'serialsByPurchase' => $serialsByPurchase ]);
        else:
            $message = Alert::error(__('common.notification_sorry'),__('common.no_invoice_items'));
            return back();
        endif;
    }

    
     public function returnSale($id){
        $invoice = SaleProduct::find($id);
        if($invoice):
            // Ownership check
            $actor = auth('admin')->user();
            if($actor && !$this->hasAdminPrivileges($actor) && (int)$invoice->salespersonId !== (int)$actor->id){
                Alert::error(__('common.notification_unauthorized'),__('common.notification_you_are_not_allowed_to_access_returns_for_this_sale'));
                return back();
            }
            $customer = Customer::find($invoice->customerId);
            $items = InvoiceItem::where(['saleId'=>$id])
            ->join('purchase_products','purchase_products.id','invoice_items.purchaseId')
            ->join('products','products.id','purchase_products.productName')
            ->select(
                'purchase_products.id as purchaseId',
                'products.id as productId',
                'products.name as productName',
                'invoice_items.id as invoiceId',
                'invoice_items.saleId as saleId',
                'invoice_items.salePrice',
                'invoice_items.buyPrice',
                'invoice_items.qty',
                'invoice_items.totalSale',
            )->orderBy('totalSale','desc')->get();

            $returnHistory = null;
            $readOnlyReturnHistory = false;
            if($items->isEmpty()){
                $returnHistory = SaleReturn::with(['items.product', 'items.purchase'])
                    ->where('saleId', $id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if($returnHistory && $returnHistory->items && $returnHistory->items->isNotEmpty()){
                    $readOnlyReturnHistory = true;
                    $items = $returnHistory->items->map(function($item){
                        return (object) [
                            'purchaseId' => (int)$item->purchaseId,
                            'productId' => (int)$item->productId,
                            'productName' => optional($item->product)->name ?? '-',
                            'invoiceId' => null,
                            'saleId' => (int)$item->saleId,
                            'salePrice' => (float)($item->saleReturn && $item->saleReturn->totalReturnAmount ? 0 : 0),
                            'buyPrice' => (float)0,
                            'qty' => (int)$item->qty,
                            'totalSale' => (float)0,
                            'returnQty' => (int)$item->qty,
                        ];
                    });
                }
            }

            if($items->isEmpty()){
                Alert::error(__('common.notification_sorry'),__('common.no_invoice_items'));
                return back();
            }

            return view('sale.returnSale',[
                'invoice' => $invoice,
                'items' => $items,
                'customer' => $customer,
                'saleId' => $id,
                'readOnlyReturnHistory' => $readOnlyReturnHistory,
                'returnHistory' => $returnHistory,
            ]);
        else:
            $message = Alert::error(__('common.notification_sorry'),__('common.no_invoice_items'));
            return back();
        endif;
    }

    public function saleReturnDetails($id)
    {
        // Returns of a cancelled sale are part of its history, not an open record.
        $return = SaleReturn::with(['sale.salesperson', 'items.product', 'items.purchase', 'items.customer'])
            ->whereDoesntHave('sale', fn ($q) => $q->onlyTrashed())
            ->find($id);

        if(!$return){
            Alert::error(__('common.notification_sorry'),__('common.notification_return_record_not_found'));
            return back();
        }

        $actor = auth('admin')->user();
        if ($actor && !in_array(strtolower($actor->role), ['admin', 'superadmin'])) {
            $sale = $return->sale;
            if ($sale && (int)$sale->salespersonId !== (int)$actor->id) {
                Alert::error(__('common.notification_unauthorized'),__('common.notification_you_are_not_allowed_to_access_this_return'));
                return back();
            }
        }

        $sale = $return->sale;
        $customer = $sale ? Customer::find($sale->customerId) : ($return->items->first() ? $return->items->first()->customer : null);
        $displaySale = $sale ?: (object) [
            'invoice' => 'RETURN-' . $return->id,
            'date' => $return->created_at,
            'reference' => null,
            'note' => $return->returnNote ?? null,
            'totalSale' => $return->totalReturnAmount ?? 0,
            'paidAmount' => 0,
            'curDue' => 0,
        ];

        $items = $return->items->map(function($item){
            return (object) [
                'purchaseId' => (int)$item->purchaseId,
                'productId' => (int)$item->productId,
                'productName' => optional($item->product)->name ?? '-',
                'saleId' => (int)$item->saleId,
                'salePrice' => 0,
                'buyPrice' => 0,
                'qty' => (int)$item->qty,
                'totalSale' => 0,
            ];
        });

        return view('sale.returnSaleDetails', [
            'invoice' => $displaySale,
            'returnRecord' => $return,
            'items' => $items,
            'customer' => $customer,
            'readOnlyReturnHistory' => true,
        ]);
    }
    
    public function saleReturnSave(Request $requ){
        $requ->validate([
            'customerId' => 'required|integer|exists:customers,id',
            'totalQty' => 'required|array|min:1',
            'totalQty.*' => 'integer|min:0',
            'saleId' => 'required|array|min:1',
            'saleId.*' => 'required|integer|exists:sale_products,id',
            'productId' => 'required|array|min:1',
            'productId.*' => 'required|integer|exists:products,id',
            'purchaseId' => 'required|array|min:1',
            'purchaseId.*' => 'required|integer|exists:purchase_products,id',
            'adjustAmount' => 'nullable|numeric|min:0',
            'returnNote' => 'nullable|string|max:1000',
        ]);

        $actor = auth('admin')->user();
        $saleIds = is_array($requ->input('saleId')) ? $requ->input('saleId') : [];
        $productIds = is_array($requ->input('productId')) ? $requ->input('productId') : [];
        $purchaseIds = is_array($requ->input('purchaseId')) ? $requ->input('purchaseId') : [];
        $qtyItems = is_array($requ->input('totalQty')) ? $requ->input('totalQty') : [];

        $resolvedSaleId = null;
        foreach($saleIds as $sid){
            if((int)$sid > 0){
                $resolvedSaleId = (int)$sid;
                break;
            }
        }
        if(!$resolvedSaleId && !empty($requ->invoiceId)){
            $found = SaleProduct::where('invoice', $requ->invoiceId)->first();
            $resolvedSaleId = $found ? (int)$found->id : null;
        }

        if(!$resolvedSaleId){
            Alert::error(__('common.notification_sorry'),__('common.notification_invalid_sale_reference_for_return'));
            return back();
        }

        $sale = SaleProduct::find($resolvedSaleId);
        if(!$sale){
            Alert::error(__('common.notification_sorry'),__('common.notification_sale_not_found_for_return'));
            return back();
        }

        if((int)$sale->customerId !== (int)$requ->customerId){
            Alert::error(__('common.notification_sorry'),__('common.notification_customer_mismatch_for_sale_return'));
            return back();
        }

        if ($actor && !$this->hasAdminPrivileges($actor)) {
            if ($sale && (int)$sale->salespersonId !== (int)$actor->id) {
                Alert::error(__('common.notification_unauthorized'),__('common.notification_you_are_not_allowed_to_return_items_for_this_sale'));
                return back();
            }
        }

        $hasAnyReturnQty = false;
        foreach($qtyItems as $q){
            if((int)$q > 0){
                $hasAnyReturnQty = true;
                break;
            }
        }
        if(!$hasAnyReturnQty){
            Alert::error(__('common.notification_sorry'),__('common.notification_please_select_at_least_one_item_quantity_to_return'));
            return back();
        }

        $rowsToReturn = [];
        foreach($qtyItems as $index => $item){
            $qty = (int)$item;
            if($qty <= 0){
                continue;
            }
            if(!isset($saleIds[$index], $productIds[$index], $purchaseIds[$index])){
                Alert::error(__('common.notification_sorry'),__('common.notification_invalid_return_row_data'));
                return back();
            }
            $rowsToReturn[] = [
                'index' => $index,
                'qty' => $qty,
                'saleId' => (int)$saleIds[$index],
                'productId' => (int)$productIds[$index],
                'purchaseId' => (int)$purchaseIds[$index],
            ];
        }

        if(empty($rowsToReturn)){
            Alert::error(__('common.notification_sorry'),__('common.notification_please_select_at_least_one_valid_return_row'));
            return back();
        }

        DB::beginTransaction();
        try {
            $computedReturnAmount = 0;
            foreach($rowsToReturn as $row){
                if($row['saleId'] !== $resolvedSaleId){
                    throw new \Exception('Mismatched sale row detected');
                }
                $invoiceItem = InvoiceItem::where('saleId', $resolvedSaleId)
                    ->where('purchaseId', $row['purchaseId'])
                    ->lockForUpdate()
                    ->first();

                if(!$invoiceItem){
                    throw new \Exception('Sale item not found for purchase #'.$row['purchaseId']);
                }
                if((int)$invoiceItem->qty < (int)$row['qty']){
                    throw new \Exception('Return quantity exceeds available sold quantity for purchase #'.$row['purchaseId']);
                }
                $computedReturnAmount += ((float)$invoiceItem->salePrice * (int)$row['qty']);
            }

            $currentDue = max(0, (float)($sale->curDue ?? 0));
            $requestedAdjust = max(0, (float)$requ->input('adjustAmount', 0));
            $effectiveAdjust = min($requestedAdjust, $currentDue, $computedReturnAmount);

            $history = new SaleReturn();
            $history->saleId = $resolvedSaleId;
            $history->totalReturnAmount = round($computedReturnAmount, 2);
            $history->adjustAmount = round($effectiveAdjust, 2);
            if(\Schema::hasColumn('sale_returns', 'returnNote')){
                $history->returnNote = trim((string)$requ->input('returnNote', ''));
            }
            $history->save();

            $service = new StockService();

            foreach($rowsToReturn as $row){
                $returnItem = new ReturnSaleItem();
                $returnItem->returnId   = $history->id;
                $returnItem->saleId     = $row['saleId'];
                $returnItem->productId  = $row['productId'];
                $returnItem->purchaseId = $row['purchaseId'];
                $returnItem->customerId = (int)$requ->customerId;
                $returnItem->qty        = (int)$row['qty'];
                $returnItem->save();

                $applied = $service->applySaleReturnItem($returnItem);
                if(!$applied){
                    throw new \Exception('Failed to apply stock/invoice update for purchase #'.$row['purchaseId']);
                }
            }

            $sum = (float)InvoiceItem::where('saleId', $sale->id)->sum('totalSale');
            $discount = (float)($sale->discountAmount ?? 0);
            if($discount < 0){ $discount = 0; }
            if($discount > $sum){ $discount = $sum; }
            $grand = max(0, $sum - $discount);
            $paid = (float)($sale->paidAmount ?? 0);
            $newDue = max(0, $grand - $paid - $effectiveAdjust);

            $sale->totalSale = $sum;
            $sale->grandTotal = $grand;
            $sale->curDue = $newDue;
            $sale->invoiceDue = $newDue;
            $sale->save();

            DB::commit();
            Alert::success(__('common.notification_success'),__('common.notification_sale_return_saved_successfully'));
            return back();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('saleReturnSave failed: '.$e->getMessage(), [
                'saleId' => $resolvedSaleId,
                'payload' => $requ->all(),
            ]);
            Alert::error(__('common.notification_sorry'),__('common.notification_sale_return_failed_please_try_again'));
            return back();
        }
    }

    public function returnSaleList(){
        $actor = auth('admin')->user();
        // Hide returns of cancelled sales; returns whose sale no longer exists stay listed as before.
        $returns = SaleReturn::with(['sale', 'items'])
            ->whereDoesntHave('sale', fn ($q) => $q->onlyTrashed())
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Filter returns based on user role
        if($actor && !$this->hasAdminPrivileges($actor)){
            // Store managers only see returns for their sales
            $returns = $returns->filter(function($return) use ($actor) {
                return $return->sale && (int)$return->sale->salespersonId === (int)$actor->id;
            });
        }
        
        return view('sale.returnSaleList', ['returns' => $returns]);
    }

    /** Only admins and superadmins may cancel sales. */
    private function authorizeSaleCancellation(): AdminUser
    {
        $actor = auth('admin')->user();
        abort_unless($actor instanceof AdminUser && $actor->canCancelSales(), 403, __('sales.not_allowed_to_cancel_sales'));

        return $actor;
    }

    private function validatedCancelReason(Request $request): string
    {
        $data = $request->validate(
            ['reason' => 'required|string|min:3|max:500'],
            [],
            ['reason' => __('sales.cancel_reason')]
        );

        return trim($data['reason']);
    }

    /**
     * Cancel a sale (DELETE only): stock and serials are returned and the sale is
     * kept as a soft-deleted, audited record. See App\Services\SaleCancellation.
     */
    public function delSale(Request $request, $id, SaleCancellation $cancellation)
    {
        $actor = $this->authorizeSaleCancellation();
        $reason = $this->validatedCancelReason($request);

        try {
            $sale = $cancellation->cancel((int)$id, $reason, $actor);
        } catch (SaleCancellationException $e) {
            Alert::error(__('common.notification_error'), $e->getMessage());
            return back();
        } catch (\Throwable $e) {
            \Log::error('delSale failed: ' . $e->getMessage(), ['saleId' => $id]);
            Alert::error(__('common.notification_error'), __('sales.sale_cancel_failed'));
            return back();
        }

        Alert::success(__('sales.sale_cancelled_title'), __('sales.sale_cancelled', ['invoice' => $sale->invoice]));
        return redirect()->route('saleList');
    }

    /** Cancel several sales at once; if one fails, none is cancelled. */
    public function bulkDeleteSales(Request $req, SaleCancellation $cancellation){
        $actor = $this->authorizeSaleCancellation();
        $ids = array_values(array_unique(array_filter(array_map('intval', (array)$req->input('ids', $req->input('selected', []))))));
        if(empty($ids)){ return back()->with('error',__('common.notification_no_sales_selected')); }
        $reason = $this->validatedCancelReason($req);

        try {
            $count = $cancellation->cancelMany($ids, $reason, $actor);
            Alert::success(__('sales.sale_cancelled_title'), __('sales.selected_sales_cancelled', ['count' => $count]));
        } catch (SaleCancellationException $e) {
            Alert::error(__('common.notification_error'), __('sales.bulk_cancel_rolled_back', ['reason' => $e->getMessage()]));
        } catch (\Throwable $e) {
            \Log::error('bulkDeleteSales failed: '.$e->getMessage());
            Alert::error(__('common.notification_error'), __('common.bulk_sales_failed'));
        }
        return back();
    }
}
