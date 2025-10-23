<?php

namespace App\Http\Controllers;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleProduct;
use App\Models\InvoiceItem;
use Alert;
use App\Models\returnSaleProduct;
use App\Models\returnInvoiceItem;
use App\Models\SaleReturn;
use App\Models\ProductStock;
use App\Models\ReturnSaleItem;


use Illuminate\Http\Request;

class saleController extends Controller
{
    Public function newsale (){
        $customer = Customer::orderBy('id','DESC')->get();
        $product = Product::orderBy('id','DESC')->get();
        return view('sale.newsale',['customerList'=>$customer,'productList'=>$product]);

    }

    public function saleList(){
        $saleList = SaleProduct::orderBy('id','desc')->get();
        return view('sale.saleList',['saleList'=>$saleList]);
    }

    public function invoiceGenerate($id){
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
                'invoice_items.salePrice',
                'invoice_items.buyPrice',
                'invoice_items.qty',
                'invoice_items.totalSale',
            )->orderBy('totalSale','desc')->get();
            return view('invoice.invoicePage',['invoice'=>$invoice,'items'=>$items,'customer'=>$customer]);
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
        // return $requ;
        $history = new SaleReturn();
        $history->saleId              = $requ->invoiceId;
        $history->totalReturnAmount    = $requ->totalReturnAmount;
        $history->adjustAmount         = $requ->adjustAmount;
        if($history->save())
            $items = $requ->totalQty;
            // return $requ->adjustAmount;
            if(count($items)>0):
                foreach($items as $index => $item):
                    $sales = new ReturnSaleItem();
                    $sales->returnId            = $history->id;
                    $sales->saleId              = $requ->saleId[$index];
                    $sales->productId           = $requ->productId[$index];
                    $sales->purchaseId          = $requ->purchaseId[$index];
                    $sales->customerId          = $requ->customerId;
                    $sales->qty                 = $item;
                    
                    if($sales->save()):
                        // stock updated
                        $stockHistory = ProductStock::where(['purchaseId'=>$requ->purchaseId[$index]])->first();
                        $updatedStock = $stockHistory->currentStock+$item;
                        $stockHistory->currentStock = $updatedStock;
                        $stockHistory->save();

                        
                        // stock updated
                        $saleHistory = InvoiceItem::where(['saleId'=>$requ->saleId[$index],'purchaseId'=>$requ->purchaseId[$index]])->first();
                        if($saleHistory):
                            $updatedStockItem = $saleHistory->qty-$item;
                            $saleHistory->qty = $updatedStockItem;
                            $saleHistory->save();
                        endif;
                    endif;
                endforeach;
            
            $message = Alert::success('Success!','Data saved successfully');
            return back();
        else:
            $message = Alert::error('Sorry!','Data failed to save');
            return back();
        endif;
    }

     public function returnSaleList(){
        return view('sale.returnSaleList');
    }
}
