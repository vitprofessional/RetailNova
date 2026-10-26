<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaleProduct;
use App\Models\InvoiceItem;
use App\Models\Customer;


class invoiceController extends Controller
{

    /**
     * Resolve invoice rows through their tenant-scoped purchase and product models.
     * The /invoice and thermal-print routes render different views from the legacy
     * generate/invoice route, so their rows must be enriched consistently here.
     */
    private function invoiceItems(int $saleId)
    {
        $items = InvoiceItem::with(['purchase.product.unitModel'])
            ->where('saleId', $saleId)
            ->get();

        return $items->each(function (InvoiceItem $item): void {
            $product = $item->purchase?->product;
            $item->productId = $product?->id;
            $item->productName = $product?->name ?? __('invoices.item').' #'.$item->purchaseId;
            $item->productCode = $product?->barCode;
            $item->unit = $product?->unitModel?->name;
        });
    }

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

        $items = $this->invoiceItems((int) $invoice->id);
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
        $paper = $request->query('size', $request->query('printer', 'thermal80'));
        $paper = in_array($paper, ['a4', 'thermal80', 'thermal58'], true) ? $paper : 'thermal80';

        return redirect()->route('documents.print', [
            'type' => 'sale-invoice', 'id' => $invoice->id, 'size' => $paper, 'autoprint' => 1,
        ]);
   }
}
