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
use App\Services\StockService;
use App\Services\InvoiceService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class purchase extends Controller
{
   public function __construct(){
       $this->middleware(function($request, $next){
           $actor = Auth::guard('admin')->user();
           return $next($request);
       });
   }
   public function addPurchase(){
       $workflow=app(\App\Services\PurchaseWorkflow::class);
       $supplier = $workflow->scoped(Supplier::query())->orderBy('id','DESC')->get();
       $product = $workflow->scoped(Product::query())->orderBy('id','DESC')->get();
       $productUnit = ProductUnit::orderBy('id','DESC')->get();
       $category = Category::orderBy('id','DESC')->get();
       $brand = Brand::orderBy('id','DESC')->get();
       // robust invoice generation using sequence table
       $generatedInvoice = app(InvoiceService::class)->generatePurchaseInvoice();
                $businesses = \App\Models\BusinessSetup::when(!$workflow->platform(),fn($q)=>$q->whereKey(auth('admin')->user()->businessId))->orderBy('id','asc')->get();

         return view('purchase.addPurchase',[
             'brandList'=>$brand,
             'categoryList'=>$category,
             'productUnitList'=>$productUnit,
             'supplierList'=>$supplier,
             'productList'=>$product,
                         'generatedInvoice' => $generatedInvoice,
                         'businesses' => $businesses,
         ]);
   }

    public function purchaseList(){
        return app(PurchaseInvoiceController::class)->index(request(),app(\App\Services\PurchaseWorkflow::class));
    }




   public function returnPurchase($id){
        $purchase = app(\App\Services\PurchaseWorkflow::class)->line((int)$id);
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
            Alert::error(__('common.notification_sorry'),__('common.notification_no_purchase_found'));
            return back();
        endif;
    }

   public function selectPurchaseForReturn(){
        return view('purchase.selectPurchaseForReturn');
   }

    public function purchaseReturnSave(Request $request){
        $data=$request->validate(['purchaseId'=>['required','integer'],'supplierId'=>['required','integer'],
            'productId'=>['required','integer'],'returnQty'=>['required','integer','min:1'],
            'adjustAmount'=>['nullable','numeric','min:0'],'serialIds'=>['sometimes','array'],
            'serialIds.*'=>['integer','distinct']]);
        $workflow=app(\App\Services\PurchaseWorkflow::class);
        \Illuminate\Support\Facades\DB::transaction(function() use($data,$workflow){
            $workflow->lockSerialNamespace();$line=$workflow->line((int)$data['purchaseId']);
            $line=PurchaseProduct::withoutGlobalScopes()->whereKey($line->id)->lockForUpdate()->firstOrFail();
            $stock=ProductStock::withoutGlobalScopes()->where('purchaseId',$line->id)->lockForUpdate()->first();
            $qty=(int)$data['returnQty'];$total=round($qty*(float)$line->buyPrice,2);
            if(!$stock || $qty>(int)$stock->currentStock || (int)$line->supplier!==(int)$data['supplierId'] || (int)$line->productName!==(int)$data['productId']
                || (float)($data['adjustAmount']??0)>$total){throw \Illuminate\Validation\ValidationException::withMessages(['returnQty'=>__('purchases.invalid_return')]);}
            $ids=$data['serialIds']??[];
            if($line->serials()->exists()){
                if(count($ids)!==$qty){throw \Illuminate\Validation\ValidationException::withMessages(['serialIds'=>__('purchases.return_serials_required')]);}
                foreach($ids as $id){$serial=$workflow->mutableSerial($line,$id);$serial->status='returned';$serial->save();}
            } elseif($ids){throw \Illuminate\Validation\ValidationException::withMessages(['serialIds'=>__('purchases.foreign_serial')]);}
            $history=PurchaseReturn::create(['purchaseId'=>$line->id,'totalReturnAmount'=>$total,'adjustAmount'=>$data['adjustAmount']??0]);
            $item=new ReturnPurchaseItem();$item->returnId=$history->id;$item->purchaseId=$line->id;$item->supplierId=$line->supplier;$item->productId=$line->productName;$item->qty=$qty;$item->save();
            $stock->currentStock-=$qty;$stock->save();
        });
        return redirect()->route('purchaseList')->with('status',__('purchases.return_saved'));
    }



    public function returnPurchaseList(){
        $returnList = PurchaseReturn::when(!app(\App\Services\PurchaseWorkflow::class)->platform(),fn($q)=>$q->where('purchase_products.businessId',auth('admin')->user()->businessId))->join('purchase_products', 'purchase_products.id', 'purchase_returns.purchaseId')
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
        $purchase = app(\App\Services\PurchaseWorkflow::class)->line((int)$id);
        if($purchase):
            if($purchase->purchase_invoice_id){return redirect()->route('purchase.invoice.show',$purchase->purchase_invoice_id);}
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
            Alert::error(__('common.notification_sorry'),__('common.notification_purchase_not_found'));
            return back();
        endif;
    }

    public function editPurchase($id){
        $purchase = app(\App\Services\PurchaseWorkflow::class)->line((int)$id);
        if($purchase):
            if($purchase->purchase_invoice_id){return redirect()->route('purchase.invoice.edit',$purchase->purchase_invoice_id);}
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
            Alert::error(__('common.notification_sorry'),__('common.notification_purchase_not_found'));
            return back();
        endif;
    }

    public function updatePurchase(Request $request){
        $line=app(\App\Services\PurchaseWorkflow::class)->line((int)$request->purchaseId);
        throw \Illuminate\Validation\ValidationException::withMessages(['items'=>__('purchases.legacy_identity_required')]);
    }



    

    public function delPurchase($id){
        $workflow=app(\App\Services\PurchaseWorkflow::class);$line=$workflow->line((int)$id);
        if(request()->isMethod('get') || $line->purchase_invoice_id || (float)$line->paidAmount>0){
            throw \Illuminate\Validation\ValidationException::withMessages(['items'=>__('purchases.use_invoice_actions')]);
        }
        \Illuminate\Support\Facades\DB::transaction(function() use($workflow,$line){$workflow->lockSerialNamespace();$workflow->removeLine($line);});
        return redirect()->route('purchaseList');
    }





}
