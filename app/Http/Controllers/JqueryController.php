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

        if($getData):
            $stockHistory       = ProductStock::where(['productId'=>$getData->id])->get();
            if(!empty($stockHistory)):
                $currentStock   = $stockHistory->sum('currentStock');
            else:
                $currentStock   = 0;
            endif;

            // Get brand information
            $brand = Brand::find($getData->brand);
            $brandName = $brand ? $brand->name : '';

            // $purchaseHistory = ProductPurchase::where(['productId'=>$getData->id])->get();

            $productName = $getData->name;
            $productWithBrand = $productName . ($brandName ? ' - ' . $brandName : '');
            // $buyingPrice = $getData->purchasePrice;
            // $productName = $getData->name;
            return ['productName' => $productWithBrand, 'currentStock' => $currentStock, 'message'=>'Success ! Form successfully subjmit.','id'=> $getData->id];
        else:
            return ['productName' => "",'currentStock' =>"", 'message'=>'Error ! There is an error. Please try agin.','id'=>''];
        endif;    
    }

    public function getSaleProductDetails($id){
        $getData = PurchaseProduct::where(['productId'=>$id])
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
            'product_stocks.currentStock',
        )->orderBy('purchaseId','desc')->get();

        //purchase product
        if($getData->count()>0):
            $product        = Product::find($id);
            $productName    = $product->name;
            $salePrice      = $getData->first()->salePriceExVat;
            $buyPrice       = $getData->first()->buyPrice;

            return ['productName' => $productName, 'id'=>$getData->first()->purchaseId, 'getData' => $getData, 'buyPrice'=> $buyPrice, 'salePrice'=>$salePrice];
        else:
            return ['productName' => "", 'id'=>$id, 'getData' => null, 'buyPrice'=>'', 'salePrice'=>''];
        endif;
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
        // For now return all products; we keep the response identical to the
        // authenticated version for simplicity.
        $products = Product::orderBy('name','asc')->get();

        $options = '<option value="">Select</option>';
        foreach ($products as $p) {
            $options .= '<option value="'.$p->id.'">'.htmlspecialchars($p->name).'</option>';
        }

        return response()->json(['data' => $options]);
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
            return ['id'=>"", 'buyPrice'=> "", 'getData' => null, 'salePrice'=>""];
        }
    }

    public function savePurchase(PurchaseSaveRequest $requ){
        // Validation moved to FormRequest

        // Update path (editing existing purchase)
        if(!empty($requ->purchaseId)):
            $purchase = PurchaseProduct::find($requ->purchaseId);
            if(!$purchase):
                Alert::error('Sorry!','Purchase not found for update');
                return back();
            endif;

            $oldQty      = (int)$purchase->qty;
            $newQty      = (int)$requ->quantity;
            $returnedQty = (int)ReturnPurchaseItem::where('purchaseId',$purchase->id)->sum('qty');

            if($newQty < $returnedQty):
                Alert::error('Sorry!','New quantity cannot be less than already returned quantity (Returned: '.$returnedQty.')');
                return back();
            endif;

            $delta = $newQty - $oldQty; // positive => increase, negative => decrease

            // Update purchase fields
            $purchase->productName      = $requ->productName;
            $purchase->supplier         = $requ->supplierName;
            $purchase->purchase_date    = $requ->purchaseDate;
            $purchase->invoice          = $requ->get('invoiceData');
            $purchase->reference        = $requ->get('refData');
            $purchase->qty              = $newQty; // updated
            $purchase->buyPrice         = $requ->get('buyPrice');
            $purchase->salePriceExVat   = $requ->get('salePriceExVat');
            $purchase->vatStatus        = $requ->get('vatStatus');
            $purchase->salePriceInVat   = $requ->get('salePriceInVat');
            $purchase->profit           = $requ->get('profitMargin');
            $purchase->totalAmount      = $requ->get('totalAmount');
            $purchase->disType          = $requ->get('discountStatus');
            $purchase->disAmount        = $requ->get('discountAmount');
            $purchase->disParcent       = $requ->get('discountPercent');
            $purchase->grandTotal       = $requ->get('grandTotal');
            $purchase->paidAmount       = $requ->get('paidAmount');
            $purchase->dueAmount        = $requ->get('dueAmount');
            $purchase->specialNote      = $requ->get('specialNote');

            if($purchase->save()):
                // Adjust stock by delta
                $stock = ProductStock::where('purchaseId',$purchase->id)->first();
                if($stock):
                    // If product changed, reflect it
                    if($stock->productId != $purchase->productName):
                        $stock->productId = $purchase->productName;
                    endif;
                    $stock->currentStock = max(0, (int)$stock->currentStock + $delta);
                    $stock->save();
                endif;

                // Optional: add new serials if provided
                if($requ->has('serialNumber')):
                    $serials = $requ->input('serialNumber', []);
                    if(is_array($serials) && count($serials) > 0):
                        foreach($serials as $serialValue):
                            if(!empty(trim($serialValue))):
                                $newSerial = new ProductSerial();
                                $newSerial->serialNumber = trim($serialValue);
                                $newSerial->productId = $purchase->productName;
                                // associate this serial with the created purchase if DB column exists
                                if (Schema::hasColumn('product_serials', 'purchaseId')) {
                                    $newSerial->purchaseId = $purchase->id;
                                }
                                $newSerial->save();
                            endif;
                        endforeach;
                    endif;
                endif;

                Alert::success('Success!','Purchase updated successfully');
                return back();
            else:
                Alert::error('Sorry!','Failed to update purchase');
                return back();
            endif;

        else:
            // Create path (original logic with duplicate prevention)
            $purchaseHistory = PurchaseProduct::where([
                'productName'   => $requ->productName,
                'qty'           => $requ->quantity,
                'supplier'      => $requ->supplierName,
                'purchase_date' => $requ->purchaseDate
            ])->get();

            if($purchaseHistory->count()>0):
                Alert::error('Opps! Purchase history already exist');
                return back();
            endif;

            $purchase = new PurchaseProduct();
            $purchase->productName      = $requ->productName;
            $purchase->supplier         = $requ->supplierName;
            $purchase->purchase_date    = $requ->purchaseDate;
            $purchase->invoice          = $requ->get('invoiceData');
            $purchase->reference        = $requ->get('refData');
            $purchase->qty              = $requ->quantity;
            $purchase->buyPrice         = $requ->get('buyPrice');
            $purchase->salePriceExVat   = $requ->get('salePriceExVat');
            $purchase->vatStatus        = $requ->get('vatStatus');
            $purchase->salePriceInVat   = $requ->get('salePriceInVat');
            $purchase->profit           = $requ->get('profitMargin');
            $purchase->totalAmount      = $requ->get('totalAmount');
            $purchase->disType          = $requ->get('discountStatus');
            $purchase->disAmount        = $requ->get('discountAmount');
            $purchase->disParcent       = $requ->get('discountPercent');
            $purchase->grandTotal       = $requ->get('grandTotal');
            $purchase->paidAmount       = $requ->get('paidAmount');
            $purchase->dueAmount        = $requ->get('dueAmount');
            $purchase->specialNote      = $requ->get('specialNote');

            if($purchase->save()):
                if($requ->serialNumber && count($requ->serialNumber) > 0):
                    foreach($requ->serialNumber as $serialValue):
                        if(!empty($serialValue)):
                            $newSerial = new ProductSerial();
                            $newSerial->serialNumber = $serialValue;
                                $newSerial->productId = $requ->productName;
                                // associate with this purchase if DB column exists
                                if (Schema::hasColumn('product_serials', 'purchaseId')) {
                                    $newSerial->purchaseId = $purchase->id;
                                }
                            $newSerial->save();
                        endif;
                    endforeach;
                endif;

                $prevStock = new ProductStock();
                $prevStock->productId    = $requ->productName;
                $prevStock->purchaseId   = $purchase->id;
                $prevStock->currentStock = (int)$requ->quantity;
                $prevStock->save();

                Alert::success('Success!','Data saved successfully');
                return back();
            else:
                Alert::error('Sorry!','Data failed to save');
                return back();
            endif;
        endif; // end create/update branching
    }

    //service
    public function getserviceDetails($id){
        $getData = Service::find($id);

        if($getData):
            $serviceName = $getData->serviceName;
            $rate = $getData->rate;
            return ['serviceName' => $serviceName, 'rate' => $rate, 'message'=>'Success ! Form successfully subjmit.','id'=> $getData->id];
        else:
            return ['serviceName' => "",'rate' => "", 'message'=>'Error ! There is an error. Please try agin.','id'=>''];
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

        $items = $requ->qty ?? [];

        try {
            DB::transaction(function() use ($requ, $items, $stockService, $invoiceService, &$message) {
                $sales = new SaleProduct();
                // Generate a sequenced sale invoice (replaces client-provided invoice)
                $sales->invoice         = $invoiceService->generateSaleInvoice();
                $sales->date            = $requ->date;
                $sales->customerId      = $requ->customerId;
                $sales->reference       = $requ->reference;
                $sales->note            = $requ->note;
                $sales->totalSale       = $requ->totalSaleAmount;
                $sales->discountAmount  = $requ->discountAmount;
                $sales->grandTotal      = $requ->grandTotal;
                $sales->paidAmount      = $requ->paidAmount;
                $sales->invoiceDue      = $requ->dueAmount;
                $sales->prevDue         = $requ->prevDue;
                $sales->curDue          = $requ->curDue;
                $sales->status          = "Ordered";

                if(!$sales->save()){
                    throw new \Exception('Failed to save sale');
                }

                if(count($items)>0){
                    foreach($items as $index => $item){
                        $invoice = new InvoiceItem();
                        $invoice->purchaseId = $requ->purchaseData[$index];
                        $invoice->saleId = $sales->id;
                        $invoice->qty = $item;
                        $invoice->salePrice = $requ->salePrice[$index];
                        $invoice->buyPrice = $requ->buyPrice[$index];

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

                        // Use StockService to safely decrease stock for this purchase row (sale-specific)
                        $purchaseId = (int)$requ->purchaseData[$index];
                        $qty = (int)$item;
                        $ok = $stockService->decreaseStockForSale($purchaseId, $qty);
                        // decreaseStockForSale returns false if stock insufficient or not found
                        if(!$ok){
                            throw new \Exception('Insufficient stock or invalid purchaseId: '.$purchaseId);
                        }
                    }
                }
            });

            $message = Alert::success('Success!','Data saved successfully');
            return back();
        } catch (\Exception $e) {
            // Log exception
            \Log::error('saveSale transaction failed: '.$e->getMessage());

            $msg = 'Data failed to save';
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
                    $msg = 'Insufficient stock for "'.$productName.'". Adjust quantity or restock and try again.';
                } else {
                    $msg = 'Insufficient stock for one or more items. Adjust quantity or restock and try again.';
                }
            }

            $message = Alert::error('Sorry!',$msg);
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
            return response()->json(['status' => 'error', 'message' => 'Serial not found'], 404);
        }

        try {
            $serial->delete();
            return response()->json(['status' => 'success', 'message' => 'Serial deleted']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to delete serial'], 500);
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
            return response()->json(['status' => 'error', 'message' => 'Purchase not found'], 404);
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
}
