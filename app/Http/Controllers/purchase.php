<?php

namespace App\Http\Controllers;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductUnit;
use App\Models\PurchaseProduct;
use App\Models\PurchaseReturn;
use App\Models\ReturnPurchaseItem;
use App\Models\ProductStock;
use App\Models\ProductSerial;
use Alert;
use Illuminate\Support\Facades\Schema;

use Illuminate\Http\Request;

class purchase extends Controller
{
   public function addPurchase(){
        $supplier = Supplier::orderBy('id','DESC')->get();
        $product = Product::orderBy('id','DESC')->get();
        $productUnit = ProductUnit::orderBy('id','DESC')->get();
        $category = Category::orderBy('id','DESC')->get();
        $brand = Brand::orderBy('id','DESC')->get();
         // generate a new invoice for the add form: INV + YYYYMMDD + zero-padded next id
         $nextId = (int)(PurchaseProduct::max('id') ?? 0) + 1;
         $generatedInvoice = 'INV'.date('Ymd').str_pad($nextId, 6, '0', STR_PAD_LEFT);

         return view('purchase.addPurchase',[
             'brandList'=>$brand,
             'categoryList'=>$category,
             'productUnitList'=>$productUnit,
             'supplierList'=>$supplier,
             'productList'=>$product,
             'generatedInvoice' => $generatedInvoice,
         ]);
   }

   public function purchaseList(){
      $purchaseList = PurchaseProduct::join('products','products.id','purchase_products.productName')
      ->join('suppliers','suppliers.id','purchase_products.supplier')
      ->join('product_stocks','product_stocks.purchaseId','purchase_products.id')
      ->select(
            'purchase_products.id as purchaseId',
            'products.id as productId',
            'products.name as productName',
            'suppliers.id as supplierId',
            'suppliers.name as supplierName',
            'product_stocks.id as stockId',
            'product_stocks.currentStock',
            'purchase_products.invoice',
            'purchase_products.qty',
            'purchase_products.totalAmount',
            'purchase_products.grandTotal',
            'purchase_products.paidAmount',
            'purchase_products.paidAmount',
            'purchase_products.dueAmount',
            'purchase_products.salePriceExVat',
            'purchase_products.salePriceInVat',
            'purchase_products.vatStatus',
            'purchase_products.buyPrice',
            'purchase_products.purchase_date',
            'purchase_products.id',
      )->orderBy('totalAmount','desc')->get();
      return view('purchase.purchaseList',['purchaseList'=>$purchaseList]);
   }


   public function returnPurchase($id){
        $purchase = PurchaseProduct::find($id);
        if($purchase):
            $supplier = Supplier::find($purchase->supplier);
            $product = Product::find($purchase->productName);
            $stock = ProductStock::where('purchaseId', $id)->first();
            
            return view('purchase.returnPurchase', [
                'purchase' => $purchase,
                'supplier' => $supplier,
                'product' => $product,
                'stock' => $stock,
                'purchaseId' => $id
            ]);
        else:
            Alert::error('Sorry!','No purchase found');
            return back();
        endif;
    }

    public function purchaseReturnSave(Request $request){
        // Validate that quantity is integer
        $request->validate([
            'returnQty' => 'required|integer|min:1'
        ]);

        $history = new PurchaseReturn();
        $history->purchaseId = $request->purchaseId;
        $history->totalReturnAmount = $request->totalReturnAmount;
        $history->adjustAmount = $request->adjustAmount ?? 0;
        
        if($history->save()):
            $returnItem = new ReturnPurchaseItem();
            $returnItem->returnId = $history->id;
            $returnItem->purchaseId = $request->purchaseId;
            $returnItem->supplierId = $request->supplierId;
            $returnItem->productId = $request->productId;
            $returnItem->qty = (int)$request->returnQty;
            
            if($returnItem->save()):
                // Update stock: recalculate from original quantity minus all returns
                $stockHistory = ProductStock::where('purchaseId', $request->purchaseId)->first();
                $purchaseHistory = PurchaseProduct::find($request->purchaseId);
                if($stockHistory && $purchaseHistory):
                    $originalQty = (int)$purchaseHistory->qty + \App\Models\ReturnPurchaseItem::where('purchaseId', $request->purchaseId)->sum('qty');
                    $totalReturned = \App\Models\ReturnPurchaseItem::where('purchaseId', $request->purchaseId)->sum('qty');
                    $stockHistory->currentStock = max(0, $originalQty - $totalReturned);
                    $stockHistory->save();
                endif;

                // Update purchase quantity
                $purchaseHistory = PurchaseProduct::find($request->purchaseId);
                if($purchaseHistory):
                    $updatedQty = (int)$purchaseHistory->qty - (int)$request->returnQty;
                    $purchaseHistory->qty = max(0, $updatedQty);
                    $purchaseHistory->save();
                endif;
            endif;
            
            Alert::success('Success!','Purchase return saved successfully');
            return redirect()->route('purchaseList');
        else:
            Alert::error('Sorry!','Failed to save purchase return');
            return back();
        endif;
    }

    public function returnPurchaseList(){
        $returnList = PurchaseReturn::join('purchase_products', 'purchase_products.id', 'purchase_returns.purchaseId')
            ->join('suppliers', 'suppliers.id', 'purchase_products.supplier')
            ->join('products', 'products.id', 'purchase_products.productName')
            ->select(
                'purchase_returns.*',
                'purchase_products.invoice',
                'purchase_products.purchase_date',
                'suppliers.name as supplierName',
                'products.name as productName'
            )
            ->orderBy('purchase_returns.id', 'desc')
            ->get();
            
        return view('purchase.returnPurchaseList', ['returnList' => $returnList]);
    }

    public function purchaseView($id){
        $purchase = PurchaseProduct::find($id);
        if($purchase):
            $supplier = Supplier::find($purchase->supplier);
            $product = Product::find($purchase->productName);
            $stock = ProductStock::where('purchaseId', $id)->first();
            
            // Get purchase history/details
            $purchaseDetails = PurchaseProduct::where('purchase_products.id', $id)
                ->join('suppliers', 'suppliers.id', 'purchase_products.supplier')
                ->join('products', 'products.id', 'purchase_products.productName')
                ->select(
                    'purchase_products.*',
                    'suppliers.name as supplierName',
                    'suppliers.mobile as supplierMobile',
                    'suppliers.mail as supplierEmail',
                    'suppliers.country as supplierCountry',
                    'suppliers.state as supplierState',
                    'suppliers.city as supplierCity',
                    'suppliers.area as supplierArea',
                    'products.name as productName',
                    'products.barCode as productBarCode',
                    'products.details as productDetails'
                )
                ->first();

            // Get related returns if any
            $returns = PurchaseReturn::where('purchase_returns.purchaseId', $id)
                ->join('return_purchase_items', 'return_purchase_items.returnId', 'purchase_returns.id')
                ->select('purchase_returns.*', 'return_purchase_items.qty as returnQty')
                ->get();

            return view('purchase.viewPurchase', [
                'purchase' => $purchaseDetails,
                'stock' => $stock,
                'returns' => $returns,
                'purchaseId' => $id
            ]);
        else:
            Alert::error('Sorry!','Purchase not found');
            return back();
        endif;
    }

    public function editPurchase($id){
        $purchase = PurchaseProduct::find($id);
        if($purchase):
            $supplier = Supplier::orderBy('id','DESC')->get();
            $product = Product::orderBy('id','DESC')->get();
            $productUnit = ProductUnit::orderBy('id','DESC')->get();
            $category = Category::orderBy('id','DESC')->get();
            $brand = Brand::orderBy('id','DESC')->get();
            // load the current stock record for this purchase (if any)
            $stock = ProductStock::where('purchaseId', $id)->first();
            // total stock for the product across all purchases
            $totalStock = ProductStock::where('productId', $purchase->productName)->sum('currentStock');
            // load available serials for this purchase (if any)
            // If migration hasn't been run yet the `purchaseId` column may not exist.
            if (Schema::hasColumn('product_serials', 'purchaseId')) {
                $serials = ProductSerial::where('purchaseId', $id)->get();
            } else {
                // fallback: fetch by productId (older behavior)
                $serials = ProductSerial::where('productId', $purchase->productName)->get();
            }
            // ensure invoice exists for the edit form: keep existing or generate from id
            $generatedInvoice = $purchase->invoice ?: ('INV'.date('Ymd').str_pad($purchase->id, 6, '0', STR_PAD_LEFT));

            return view('purchase.editPurchase',[
                'brandList'=>$brand,
                'categoryList'=>$category,
                'productUnitList'=>$productUnit,
                'supplierList'=>$supplier,
                'productList'=>$product,
                'purchaseData'=>$purchase,
                'stock' => $stock,
                'totalStock' => $totalStock,
                'serials' => $serials,
                'generatedInvoice' => $generatedInvoice,
            ]);
        else:
            Alert::error('Sorry!','Purchase not found');
            return back();
        endif;
    }

    public function updatePurchase(Request $request){
        $purchase = PurchaseProduct::find($request->purchaseId);
        if($purchase):
            $purchase->purchase_date = $request->purchaseDate;
            $purchase->supplier = $request->supplierName;
            $purchase->productName = $request->productName;
            $purchase->buyPrice = $request->buyPrice;
            $purchase->salePriceExVat = $request->salePriceExVat;
            $purchase->salePriceInVat = $request->salePriceInVat;
            $purchase->vatStatus = $request->vatStatus;
            // preserve previous quantity for delta calculation
            $oldQty = (int)$purchase->qty;
            $newQty = (int)$request->quantity;

            // Protect against reducing below already returned qty
            $returnedQty = (int)ReturnPurchaseItem::where('purchaseId', $purchase->id)->sum('qty');
            if($newQty < $returnedQty):
                Alert::error('Sorry!','New quantity cannot be less than already returned quantity (Returned: '.$returnedQty.')');
                return back();
            endif;

            $purchase->qty = $newQty;
            $purchase->totalAmount = $request->totalAmount;
            $purchase->grandTotal = $request->grandTotal;
            $purchase->paidAmount = $request->paidAmount;
            $purchase->dueAmount = $request->dueAmount;
            // additional fields (profit, discounts, notes)
            $purchase->profit = $request->profitMargin ?? $purchase->profit;
            $purchase->disType = $request->discountStatus ?? $purchase->disType;
            $purchase->disAmount = $request->discountAmount ?? $purchase->disAmount;
            $purchase->disParcent = $request->discountPercent ?? $purchase->disParcent;
            $purchase->specialNote = $request->specialNote ?? $purchase->specialNote;

            if($purchase->save()):
                // Adjust stock by delta
                $delta = $newQty - $oldQty;
                $stock = ProductStock::where('purchaseId', $purchase->id)->first();
                if($stock):
                    if($stock->productId != $purchase->productName):
                        $stock->productId = $purchase->productName;
                    endif;
                    $stock->currentStock = max(0, (int)$stock->currentStock + $delta);
                    $stock->save();
                else:
                    // create stock record if missing
                    $newStock = new ProductStock();
                    $newStock->productId = $purchase->productName;
                    $newStock->purchaseId = $purchase->id;
                    $newStock->currentStock = max(0, $newQty);
                    $newStock->save();
                endif;

                // Save any new serials provided
                if($request->has('serialNumber')):
                    $serials = $request->input('serialNumber', []);
                    if(is_array($serials) && count($serials) > 0):
                        foreach($serials as $serialValue):
                            if(!empty(trim($serialValue))):
                                $newSerial = new ProductSerial();
                                $newSerial->serialNumber = trim($serialValue);
                                $newSerial->productId = $purchase->productName;
                                if (Schema::hasColumn('product_serials', 'purchaseId')) {
                                    $newSerial->purchaseId = $purchase->id;
                                }
                                $newSerial->save();
                            endif;
                        endforeach;
                    endif;
                endif;

                Alert::success('Success!','Purchase updated successfully');
                return redirect()->route('editPurchase', ['id' => $purchase->id]);
            else:
                Alert::error('Sorry!','Failed to update purchase');
                return redirect()->route('editPurchase', ['id' => $purchase->id])->withInput();
            endif;
        else:
            Alert::error('Sorry!','Purchase not found');
            return back();
        endif;
    }

    

    public function delPurchase($id){
        $purchase = PurchaseProduct::find($id);
        if($purchase):
            // Also consider deleting related stock and return records if necessary
            ProductStock::where('purchaseId', $id)->delete();
            ReturnPurchaseItem::where('purchaseId', $id)->delete();
            PurchaseReturn::where('purchaseId', $id)->delete();
            
            $purchase->delete();
            Alert::success('Success!','Purchase deleted successfully');
            return redirect()->route('purchaseList');
        else:
            Alert::error('Sorry!','Purchase not found');
            return back();
        endif;
    }
}
