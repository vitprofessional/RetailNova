<?php

namespace App\Http\Controllers;
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


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class saleController extends Controller
{
    Public function newsale (){
        // Ensure a default Walking Customer exists for quick walk-in sales
        try{ $walking = Customer::ensureWalkingCustomer(); }catch(\Throwable $e){ $walking = null; }
        $customer = Customer::orderBy('id','DESC')->get();
        $product = Product::orderBy('id','DESC')->get();
        return view('sale.newsale',[
            'customerList'=>$customer,
            'productList'=>$product,
            'walkingCustomerId' => $walking ? $walking->id : null
        ]);

    }

    public function saleList(){
        $saleList = SaleProduct::orderBy('id','desc')->get();
        return view('sale.saleList',['saleList'=>$saleList]);
    }

    /**
     * Edit a sale: allow updating paid amount and date; due is recalculated.
     */
    public function editSale($id)
    {
        $sale = SaleProduct::find($id);
        if(!$sale){
            Alert::error('Error', 'Sale not found');
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
            Alert::error('Error', 'Sale not found');
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
            Alert::error('Error', 'Sale not found');
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
                $v->errors()->add('paidAmount', 'Paid amount cannot exceed grand total ('.number_format($grand,2).').');
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
            Alert::success('Updated','Sale updated successfully');
            return redirect()->route('saleList');
        }
        Alert::error('Error','Failed to update sale');
        return back();
    }

    /**
     * Update sale items (quantities/prices) and reconcile stock; recompute totals/due.
     */
    public function updateSaleItems(Request $req, $id)
    {
        $sale = SaleProduct::find($id);
        if(!$sale){
            Alert::error('Error', 'Sale not found');
            return back();
        }

        $itemsInput = (array)$req->input('items', []);
        $addInput   = (array)$req->input('add', []);

        $service = new StockService();

        DB::beginTransaction();
        try {
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
                if($newQty !== $oldQty){
                    $delta = $newQty - $oldQty;
                    if($delta > 0){
                        // Need to consume additional stock
                        $ok = $service->decreaseStockForSale((int)$invItem->purchaseId, $delta);
                        if(!$ok){
                            throw new \Exception('Insufficient stock to increase quantity for item ID '.$invItem->id);
                        }
                    } elseif($delta < 0){
                        // Return stock for reduced quantity
                        $service->increaseStockForSaleReturn((int)$invItem->purchaseId, abs($delta));
                    }
                    $invItem->qty = $newQty;
                }

                // Update price if provided
                if(!is_null($payloadPrice) && $payloadPrice >= 0){
                    $invItem->salePrice = $payloadPrice;
                }

                // Update warranty if provided (free-text, e.g., number of days)
                if(!is_null($payloadWarranty)){
                    $invItem->warranty_days = trim($payloadWarranty);
                }

                // Serial management: enforce and sync selection to match qty where serials exist
                $hasSerialsForPurchase = \App\Models\ProductSerial::where('purchaseId', $invItem->purchaseId)->exists();
                if($hasSerialsForPurchase){
                    // Current assignments for this sale & purchase
                    $current = \App\Models\ProductSerial::where('purchaseId', $invItem->purchaseId)
                        ->where('saleId', $sale->id)
                        ->pluck('id')
                        ->map(function($v){ return (int)$v; })
                        ->toArray();
                    // Prefer item-specific selection to avoid collisions when multiple rows share a purchaseId
                    $selected = (array)$req->input('serialIdByItem.'.(int)$invItem->id, []);
                    if(empty($selected)){
                        // Fallback to purchase-based selection if item-specific array not present
                        $selected = (array)$req->input('serialIdByPurchase.'.(int)$invItem->purchaseId, []);
                    }
                    // Clean values to ints
                    $selected = array_values(array_filter(array_map('intval', $selected), function($v){ return $v > 0; }));
                    // If no selection was submitted, and the current assignments already match the desired qty, accept as-is
                    if(empty($selected) && count($current) === (int)$invItem->qty){
                        $selected = $current;
                    }
                    if(count($selected) !== (int)$invItem->qty){
                        throw new \Exception('Select exactly '.(int)$invItem->qty.' serial(s) for purchase #'.(int)$invItem->purchaseId);
                    }
                    // Determine changes
                    $toRelease = array_diff($current, $selected);
                    $toAssign  = array_diff($selected, $current);
                    if(!empty($toRelease)){
                        $rels = \App\Models\ProductSerial::whereIn('id', $toRelease)->lockForUpdate()->get();
                        foreach($rels as $sr){
                            $sr->saleId = null;
                            if(\Schema::hasColumn('product_serials','status')){ $sr->status = null; }
                            if(\Schema::hasColumn('product_serials','sold_at')){ $sr->sold_at = null; }
                            $sr->save();
                        }
                    }
                    if(!empty($toAssign)){
                        $assignRows = \App\Models\ProductSerial::whereIn('id', $toAssign)->lockForUpdate()->get();
                        if($assignRows->count() !== count($toAssign)){
                            throw new \Exception('Invalid serial selection');
                        }
                        foreach($assignRows as $sr){
                            if((int)$sr->purchaseId !== (int)$invItem->purchaseId){
                                throw new \Exception('Serial '.$sr->serialNumber.' does not belong to this purchase');
                            }
                            $soldStatus = \Schema::hasColumn('product_serials','status') ? strtolower((string)$sr->status) : '';
                            if($sr->saleId || $soldStatus === 'sold'){
                                throw new \Exception('Serial '.$sr->serialNumber.' is already sold');
                            }
                        }
                        foreach($assignRows as $sr){
                            $sr->saleId = $sale->id;
                            if(\Schema::hasColumn('product_serials','status')){ $sr->status = 'sold'; }
                            if(\Schema::hasColumn('product_serials','sold_at')){ $sr->sold_at = now(); }
                            $sr->save();
                        }
                    }
                }

                // Recompute totals
                $invItem->totalSale     = (float)$invItem->salePrice * (int)$invItem->qty;
                $invItem->totalPurchase = (float)$invItem->buyPrice * (int)$invItem->qty;
                $invItem->profitTotal   = (float)$invItem->totalSale - (float)$invItem->totalPurchase;
                $invItem->profitMargin  = $invItem->totalSale > 0 ? round(($invItem->profitTotal / $invItem->totalSale) * 100, 2) : 0;

                // If quantity became 0, delete the line to keep invoice clean
                if((int)$invItem->qty === 0){
                    // Release any serials tied to this purchase in this sale
                    \App\Models\ProductSerial::where('purchaseId', $invItem->purchaseId)->where('saleId', $sale->id)
                        ->update(['saleId' => null] + (\Schema::hasColumn('product_serials','status') ? ['status'=>null] : []) + (\Schema::hasColumn('product_serials','sold_at') ? ['sold_at'=>null] : []));
                    $invItem->delete();
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
                    $ok = $service->decreaseStockForSale($purchaseId, $qty);
                    if(!$ok){
                        throw new \Exception('Insufficient stock to add new item from purchase #'.$purchaseId);
                    }
                    $purchase = \App\Models\PurchaseProduct::find($purchaseId);
                    if(!$purchase){
                        throw new \Exception('Purchase row not found for new item');
                    }
                    $invItem = new InvoiceItem();
                    $invItem->saleId      = $sale->id;
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

                    // Serial handling for new item
                    $hasSerialsForPurchase = \App\Models\ProductSerial::where('purchaseId', $purchaseId)->exists();
                    if($hasSerialsForPurchase){
                        $selected = (array)$req->input('serialIdByPurchase.'.(int)$purchaseId, []);
                        $selected = array_values(array_filter(array_map('intval', $selected), function($v){ return $v > 0; }));
                        if(count($selected) !== (int)$qty){
                            throw new \Exception('Select exactly '.$qty.' serial(s) for purchase #'.$purchaseId);
                        }
                        $assignRows = \App\Models\ProductSerial::whereIn('id', $selected)->lockForUpdate()->get();
                        if($assignRows->count() !== count($selected)){
                            throw new \Exception('Invalid serial selection');
                        }
                        foreach($assignRows as $sr){
                            if((int)$sr->purchaseId !== (int)$purchaseId){
                                throw new \Exception('Serial '.$sr->serialNumber.' does not belong to this purchase');
                            }
                            $soldStatus = \Schema::hasColumn('product_serials','status') ? strtolower((string)$sr->status) : '';
                            if($sr->saleId || $soldStatus === 'sold'){
                                throw new \Exception('Serial '.$sr->serialNumber.' is already sold');
                            }
                        }
                        foreach($assignRows as $sr){
                            $sr->saleId = $sale->id;
                            if(\Schema::hasColumn('product_serials','status')){ $sr->status = 'sold'; }
                            if(\Schema::hasColumn('product_serials','sold_at')){ $sr->sold_at = now(); }
                            $sr->save();
                        }
                    }
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
            Alert::success('Updated', 'Sale items updated successfully');
            return redirect()->route('invoiceGenerate', ['id' => $sale->id]);
        } catch(\Exception $e){
            DB::rollBack();
            \Log::error('updateSaleItems failed: '.$e->getMessage());
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }
    }

    public function invoiceGenerate($id){
        $invoice = SaleProduct::find($id);
        if($invoice):
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
            // Load business settings if available
            try{ $business = \App\Models\BusinessSetup::first(); }catch(\Exception $e){ $business = null; }
            return view('invoice.invoicePage',[ 'invoice'=>$invoice, 'items'=>$items, 'customer'=>$customer, 'business' => $business, 'serialsByPurchase' => $serialsByPurchase ]);
        else:
            $message = Alert::error('Sorry!','No invoice items found');
            return back();
        endif;
    }

    
     public function returnSale($id){
        $invoice = SaleProduct::find($id);
        if($invoice):
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
            return view('sale.returnSale',['invoice'=>$invoice,'items'=>$items,'customer'=>$customer,'saleId'=>$id]);
        else:
            $message = Alert::error('Sorry!','No invoice items found');
            return back();
        endif;
    }
    
    public function saleReturnSave(Request $requ){
        // Validate that quantities are integers
        $requ->validate([
            'totalQty.*' => 'integer|min:0'
        ]);
        $history = new SaleReturn();
        // Prefer numeric sale id from the form (saleId[] per item). If not available,
        // attempt to resolve the sale id by invoice string.
        $resolvedSaleId = null;
        if (isset($requ->saleId) && is_array($requ->saleId) && count($requ->saleId) > 0) {
            $resolvedSaleId = (int) $requ->saleId[0];
        } elseif (!empty($requ->invoiceId)) {
            $found = SaleProduct::where('invoice', $requ->invoiceId)->first();
            $resolvedSaleId = $found ? $found->id : null;
        }
        $history->saleId = $resolvedSaleId;
        $history->totalReturnAmount = $requ->totalReturnAmount;
        $history->adjustAmount      = $requ->adjustAmount;

        if($history->save()){
            $service = new StockService();
            $items = $requ->totalQty ?? [];
            if(is_array($items) && count($items) > 0){
                foreach($items as $index => $item){
                    $qty = (int)$item;
                    if($qty <= 0){
                        continue;
                    }
                    $returnItem = new ReturnSaleItem();
                    $returnItem->returnId   = $history->id;
                    $returnItem->saleId     = $requ->saleId[$index];
                    $returnItem->productId  = $requ->productId[$index];
                    $returnItem->purchaseId = $requ->purchaseId[$index];
                    $returnItem->customerId = $requ->customerId;
                    $returnItem->qty        = $qty;
                    if($returnItem->save()){
                        $service->applySaleReturnItem($returnItem);
                    }
                }
            }
            Alert::success('Success!','Sale return saved successfully');
            return back();
        }
        Alert::error('Sorry!','Data failed to save');
        return back();
    }

     public function returnSaleList(){
        return view('sale.returnSaleList');
    }

    /**
     * Delete a sale and its invoice items, reverting stock quantities.
     */
    public function delSale($id)
    {
        $sale = SaleProduct::find($id);
        if (!$sale) {
            Alert::error('Error', 'Sale not found');
            return back();
        }

        $service = new StockService();

        DB::beginTransaction();
        try {
            $items = InvoiceItem::where('saleId', $sale->id)->get();
            foreach ($items as $it) {
                // revert stock for each purchase row
                $service->increaseStockForSaleReturn((int)$it->purchaseId, (int)$it->qty);
            }

            // delete invoice items
            InvoiceItem::where('saleId', $sale->id)->delete();

            // delete sale
            $sale->delete();

            DB::commit();
            Alert::success('Deleted', 'Sale deleted and stock reverted');
            return redirect()->route('saleList');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('delSale failed: ' . $e->getMessage());
            Alert::error('Error', 'Failed to delete sale');
            return back();
        }
    }

    /** Bulk delete sales (reverting stock for each sale similar to single delete) */
    public function bulkDeleteSales(Request $req){
        $ids = (array)$req->input('ids', $req->input('selected', []));
        if(empty($ids)){ return back()->with('error','No sales selected'); }
        $service = new StockService();
        DB::beginTransaction();
        try {
            foreach($ids as $id){
                $sale = SaleProduct::find($id);
                if(!$sale) continue;
                $items = InvoiceItem::where('saleId', $sale->id)->get();
                foreach ($items as $it) {
                    $service->increaseStockForSaleReturn((int)$it->purchaseId, (int)$it->qty);
                }
                InvoiceItem::where('saleId', $sale->id)->delete();
                $sale->delete();
            }
            DB::commit();
            Alert::success('Deleted','Selected sales deleted and stock reverted');
        } catch(\Exception $e){
            DB::rollBack();
            \Log::error('bulkDeleteSales failed: '.$e->getMessage());
            Alert::error('Error','Failed to bulk delete sales');
        }
        return back();
    }
}
