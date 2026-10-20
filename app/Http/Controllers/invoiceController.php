<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaleProduct;
use App\Models\InvoiceItem;
use App\Models\Customer;


class invoiceController extends Controller
{

    private function authorizeInvoiceBusiness(SaleProduct $invoice): void
    {
        $actor = auth('admin')->user();
        if (!$actor) {
            abort(403, 'You are not authorized to view this invoice');
        }

        $canAccessAll = method_exists($actor, 'canAccessAllSalesBusinesses')
            ? $actor->canAccessAllSalesBusinesses()
            : in_array(strtolower((string) $actor->role), ['admin', 'superadmin'], true);

        if (!$canAccessAll && ((int) $invoice->businessId <= 0 || (int) $invoice->businessId !== (int) $actor->businessId)) {
            abort(403, 'This sale belongs to another business.');
        }

        if (!in_array(strtolower((string) $actor->role), ['admin', 'superadmin'], true)
            && (int) $invoice->salespersonId !== (int) $actor->id) {
            abort(403, 'You are not authorized to view this invoice');
        }
    }
    
    //add bamage product list
    public function invoicePage(Request $request){
        $id = $request->input('id');
        if(!$id){
            abort(404, 'Invoice not specified');
        }
        $invoice = SaleProduct::find($id);
        if(!$invoice){ abort(404, 'Invoice not found'); }

        $this->authorizeInvoiceBusiness($invoice);

        $items = InvoiceItem::where('saleId', $invoice->id)->get();
        $customer = null;
        try{ if($invoice->customerId) $customer = Customer::find($invoice->customerId); }catch(\Throwable $_){ $customer = null; }

        $business = $invoice->business;
        return view('invoice.invoicePage', compact('invoice','items','customer','business'));
   }

    /**
     * Display thermal printer optimized invoice print page
     */
    public function thermalPrint(Request $request){
        $id = $request->input('id');
        if(!$id){
            abort(404, 'Invoice not specified');
        }
        $invoice = SaleProduct::find($id);
        if(!$invoice){ abort(404, 'Invoice not found'); }

        $this->authorizeInvoiceBusiness($invoice);

        $items = InvoiceItem::where('saleId', $invoice->id)->get();
        $customer = null;
        try{ if($invoice->customerId) $customer = Customer::find($invoice->customerId); }catch(\Throwable $_){ $customer = null; }

        $business = $invoice->business;
        return view('invoice.thermalPrint', compact('invoice','items','customer','business'));
   }
}
