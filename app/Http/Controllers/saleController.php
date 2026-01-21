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
