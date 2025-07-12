<?php

namespace App\Http\Controllers;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleProduct;
use App\Models\InvoiceItem;

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

    
     public function returnSale(){
        return view('sale.returnSale');
        
    }

     public function returnSaleList(){
        return view('sale.returnSaleList');
        
    }
}
