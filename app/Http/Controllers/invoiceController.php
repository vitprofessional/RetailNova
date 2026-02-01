<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaleProduct;
use App\Models\InvoiceItem;
use App\Models\Customer;


class invoiceController extends Controller
{
    
    //add bamage product list
    public function invoicePage(Request $request){
        $id = $request->input('id');
        if(!$id){
            abort(404, 'Invoice not specified');
        }
        $invoice = SaleProduct::find($id);
        if(!$invoice){ abort(404, 'Invoice not found'); }

        $actor = auth('admin')->user();
        if($actor && !in_array(strtolower($actor->role), ['admin','superadmin'])){
            // restrict access to only invoices created by this user
            if((int)$invoice->salespersonId !== (int)$actor->id){
                abort(403, 'You are not authorized to view this invoice');
            }
        }

        $items = InvoiceItem::where('saleId', $invoice->id)->get();
        $customer = null;
        try{ if($invoice->customerId) $customer = Customer::find($invoice->customerId); }catch(\Throwable $_){ $customer = null; }

        return view('invoice.invoicePage', compact('invoice','items','customer'));
   }
}
