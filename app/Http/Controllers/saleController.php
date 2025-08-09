<?php

namespace App\Http\Controllers;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleProduct;
use App\Models\InvoiceItem;
use Alert;


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
                'invoice_items.salePrice',
                'invoice_items.buyPrice',
                'invoice_items.qty',
                'invoice_items.totalSale',
            )->orderBy('totalSale','desc')->get();
            return view('sale.returnSale',['invoice'=>$invoice,'items'=>$items,'customer'=>$customer]);
        else:
            $message = Alert::error('Sorry!','No invoice items found');
            return back();
        endif;
    }
    
    public function saleReturnSave(){
        // return $requ;
        $sales = new returnSaleProduct();

        $sales->invoice         = $requ->invoice;
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
        
        $items = $requ->qty;
        if($sales->save()):
            if(count($items)>0):
                foreach($items as $index => $item):
                    $invoice = new returnInvoiceItem();
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

                    $invoice->totalSale     = $totalSale;
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

     public function returnSaleList(){
        return view('sale.returnSaleList');
    }
}
