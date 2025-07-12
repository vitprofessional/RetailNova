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
use App\Models\SaleProduct;
use App\Models\InvoiceItem;
use App\Models\Service;
use Alert;

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

            // $purchaseHistory = ProductPurchase::where(['productId'=>$getData->id])->get();

            $productName = $getData->name;
            // $buyingPrice = $getData->purchasePrice;
            // $productName = $getData->name;
            return ['productName' => $productName, 'currentStock' => $currentStock, 'message'=>'Success ! Form successfully subjmit.','id'=> $getData->id];
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
    public function getPurchaseDetails($id){
        $getData = PurchaseProduct::find($id);
        if($getData){
            $salePrice      = $getData->salePriceExVat;
            $buyPrice       = $getData->buyPrice;
            return ['id'=>$id, 'buyPrice'=> $buyPrice, 'getData' => $getData, 'salePrice'=>$salePrice];
        }else{
            return ['id'=>"", 'buyPrice'=> "", 'getData' => null, 'salePrice'=>""];
        }
    }

    public function savePurchase(Request $requ){
        // return $requ;
        // return $serial = count($requ->serialNumber);
        $purchaseHistory    = PurchaseProduct::where(['productName'=>$requ->productName,'qty'=>$requ->qty,'supplier'=>$requ->supplierName,'purchase_date'=>$requ->purchaseDate])->get();
        if($purchaseHistory->count()>0):
            Alert::error('Opps! Purchase history already exist');
            return back();
        else:
            $purchase = new PurchaseProduct();

            $purchase->productName      = $requ->productName;
            $purchase->supplier         = $requ->supplierName;
            $purchase->purchase_date    = $requ->purchaseDate;
            $purchase->invoice          = $requ->invoiceData;
            $purchase->reference        = $requ->refData;
            $purchase->qty              = $requ->qty;
            $purchase->buyPrice         = $requ->buyingPrice;
            $purchase->salePriceExVat   = $requ->salingPriceWithoutVat;
            $purchase->vatStatus        = $requ->vatStatus;
            $purchase->salePriceInVat   = $requ->salingPriceWithVat;
            $purchase->profit           = $requ->profitMargin;
            $purchase->totalAmount      = $requ->totalPrice;
            $purchase->disType          = $requ->discountStatus;
            $purchase->disAmount        = $requ->discountAmount;
            $purchase->disParcent       = $requ->discountPercent;
            $purchase->grandTotal       = $requ->grandTotal;
            $purchase->paidAmount       = $requ->paidAmount;
            $purchase->dueAmount        = $requ->dueAmount;
            $purchase->specialNote      = $requ->specialNote;
            if($purchase->save()):
                if($requ->serialNumber  && count($requ->serialNumber)>0):
                    $serial = count($requ->serialNumber);
                    $i = 0;
                    while($i<$serial){
                        $newSerial = new ProductSerial();
                        $newSerial->serialNumber = $requ->serialNumber[$i];
                        $newSerial->productId = $requ->productName;
                        $newSerial->save();
                        $i++;
                    }
                endif;
                $prevStock = new ProductStock();
                $prevStock->productId    = $requ->productName;
                $prevStock->purchaseId   = $purchase->id;
                $prevStock->currentStock = $requ->qty;
                $prevStock->save();

                $message = Alert::success('Success!','Data saved successfully');
                return back();
            else:
                $message = Alert::error('Sorry!','Data failed to save');
                return back();
            endif;
        endif;
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
            'grandTotal'    => number_format($grandTotal, 2),
            'currentStock'  => $stock
        ]);
    }

    public function saveSale(Request $requ){
        // return $requ;
        $sales = new SaleProduct();

        $sales->invoice         = $requ->invoice;
        $sales->date   = $requ->date;
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
        
        $items = $requ->qty;
        if($sales->save()):
            if(count($items)>0):
                foreach($items as $index => $item):
                    $invoice = new InvoiceItem();
                    $invoice->purchaseId = $requ->purchaseData[$index];
                    $invoice->saleId = $sales->id;
                    $invoice->qty = $item;
                    $invoice->salePrice = $requ->salePrice[$index];
                    $invoice->buyPrice = $requ->buyPrice[$index];

                    $totalSale      = $requ->salePrice[$index]*$item;
                    $totalPurchase  = $requ->buyPrice[$index]*$item;
                    $profitTotal    = $totalSale-$totalPurchase;
                    $profitMargin   = ($profitTotal/$totalPurchase)*100;
                    $profitParcent  = number_format($profitMargin,2);

                    $invoice->totalSale = $totalSale;
                    $invoice->totalPurchase = $totalPurchase;
                    $invoice->profitTotal   = $profitTotal;
                    $invoice->profitMargin  = $profitParcent;

                    if($invoice->save()):
                        // stock updated
                        $stockHistory = ProductStock::where(['purchaseId'=>$requ->purchaseData[$index]])->first();
                        $updatedStock = $stockHistory->currentStock-$item;
                        $stockHistory->currentStock = $updatedStock;
                        $stockHistory->save();
                    endif;
                endforeach;
            endif;

            $message = Alert::success('Success!','Data saved successfully');
            return back();
        else:
            $message = Alert::error('Sorry!','Data failed to save');
            return back();
        endif;
    }
}
