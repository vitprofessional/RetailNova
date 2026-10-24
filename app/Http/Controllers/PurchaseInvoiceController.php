<?php

namespace App\Http\Controllers;

use App\Models\{Product, PurchaseInvoice, PurchaseProduct, Supplier};
use App\Services\PurchaseWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request, PurchaseWorkflow $workflow)
    {
        $filters=$request->validate(['q'=>['nullable','string','max:190'],'supplier'=>['nullable','integer'],
            'from'=>['nullable','date'],'to'=>['nullable','date'],'status'=>['nullable','in:paid,due,partial'],'sort'=>['nullable','in:id,invoice,purchase_date,grand_total'],'direction'=>['nullable','in:asc,desc']]);
        $aggregate=PurchaseProduct::withoutGlobalScopes()->selectRaw('purchase_invoice_id, COUNT(*) AS item_count, SUM(qty) AS quantity_count')
            ->whereNotNull('purchase_invoice_id')->groupBy('purchase_invoice_id');
        $query=$workflow->scoped(PurchaseInvoice::query())->with('supplier')
            ->leftJoinSub($aggregate,'item_totals',fn($join)=>$join->on('item_totals.purchase_invoice_id','=','purchase_invoices.id'))
            ->select('purchase_invoices.*','item_totals.item_count','item_totals.quantity_count');
        if(!empty($filters['q'])) {$q=$filters['q'];$query->where(fn($builder)=>$builder->where('invoice','like','%'.$q.'%')->orWhereHas('supplier',fn($s)=>$s->where('name','like','%'.$q.'%')));}
        if(!empty($filters['supplier'])) {$query->where('supplier_id',$filters['supplier']);}
        if(!empty($filters['from'])) {$query->whereDate('purchase_date','>=',$filters['from']);}
        if(!empty($filters['to'])) {$query->whereDate('purchase_date','<=',$filters['to']);}
        if(($filters['status']??'')==='paid') {$query->where('due_amount',0);}
        if(($filters['status']??'')==='due') {$query->where('due_amount','>',0)->where('paid_amount',0);}
        if(($filters['status']??'')==='partial') {$query->where('due_amount','>',0)->where('paid_amount','>',0);}
        $invoices=$query->orderBy('purchase_invoices.'.($filters['sort']??'id'),$filters['direction']??'desc')->orderByDesc('purchase_invoices.id')->paginate(20)->withQueryString();
        $legacy=$workflow->scoped(PurchaseProduct::withoutGlobalScopes())->whereNull('purchase_invoice_id')->with(['supplier','product'])->orderByDesc('id')->paginate(20,['*'],'legacy_page');
        $suppliers=$workflow->scoped(Supplier::query())->orderBy('name')->get();
        return view('purchase.invoiceList',compact('invoices','legacy','suppliers'));
    }

    public function show(int $id, PurchaseWorkflow $workflow)
    {
        $invoice=$workflow->header($id)->load(['supplier','items.product','items.serials','items.stock','items.returns']);
        $items=$invoice->items;

        // Older purchases predate purchase_invoice_id. Only attach those legacy
        // lines when the full tenant/supplier/invoice/date identity resolves to
        // exactly this one header; invoice text alone is not a safe identifier.
        if ($items->isEmpty() && filled($invoice->invoice)) {
            $purchaseDate=$invoice->purchase_date->toDateString();
            $matchingHeaders=PurchaseInvoice::withoutGlobalScopes()
                ->where('businessId',$invoice->businessId)
                ->where('supplier_id',$invoice->supplier_id)
                ->where('invoice',$invoice->invoice)
                ->whereDate('purchase_date',$purchaseDate)
                ->get(['id']);

            if ($matchingHeaders->count()===1 && (int)$matchingHeaders->first()->id===(int)$invoice->id) {
                $items=PurchaseProduct::withoutGlobalScopes()
                    ->where('businessId',$invoice->businessId)
                    ->where('supplier',(string)$invoice->supplier_id)
                    ->where('invoice',$invoice->invoice)
                    ->where('purchase_date',$purchaseDate)
                    ->whereNull('purchase_invoice_id')
                    ->with(['product','serials','stock','returns'])
                    ->orderBy('id')
                    ->get();
            }
        }

        return view('purchase.invoiceView',compact('invoice','items'));
    }

    public function edit(int $id, PurchaseWorkflow $workflow)
    {
        $invoice=$workflow->header($id)->load(['supplier','items.product','items.serials','items.stock']);
        $products=Product::withoutGlobalScopes()->where('businessId',$invoice->businessId)->orderBy('name')->get();
        return view('purchase.invoiceEdit',compact('invoice','products'));
    }

    public function update(Request $request, int $id, PurchaseWorkflow $workflow)
    {
        $data=$request->validate(['rows'=>['required','array','min:1'],'rows.*.id'=>['nullable','integer'],
            'rows.*.product_id'=>['required','integer'],'rows.*.qty'=>['required','integer','min:1'],
            'rows.*.buy_price'=>['required','numeric','min:0'],'rows.*.sale_price'=>['nullable','numeric','min:0'],
            'rows.*.vat_percent'=>['nullable','numeric','between:0,100'],'rows.*.vat_included'=>['nullable','boolean'],
            'rows.*.serials'=>['nullable',function($attribute,$value,$fail){if(!is_string($value) && !is_array($value)) {$fail(__('validation.array',['attribute'=>$attribute]));} elseif(is_array($value)){foreach($value as $serial){if(!is_string($serial) || strlen($serial)>255){$fail(__('validation.string',['attribute'=>$attribute]));break;}}}}],'rows.*.serial_updates'=>['sometimes','array'],
            'rows.*.serial_updates.*'=>['required','string'],'rows.*.remove_serials'=>['sometimes','array'],
            'rows.*.remove_serials.*'=>['integer'],'purchaseDate'=>['required','date'],'discountStatus'=>['nullable','in:1,2'],
            'discountAmount'=>['nullable','numeric','min:0'],'discountPercent'=>['nullable','numeric','between:0,100'],
            'paidAmount'=>['nullable','numeric','min:0'],'specialNote'=>['nullable','string','max:1000']]);
        $invoice=$workflow->save($data,$workflow->header($id));
        return redirect()->route('purchase.invoice.show',$invoice->id)->with('status',__('purchases.invoice_saved'));
    }

    private function deleteHeader(int $id, PurchaseWorkflow $workflow): void
    {
        $header=$workflow->header($id);
        $header=PurchaseInvoice::whereKey($header->id)->lockForUpdate()->firstOrFail();
        if((float)$header->paid_amount>0) {throw ValidationException::withMessages(['items'=>__('purchases.paid_delete')]);}
        foreach($header->items()->lockForUpdate()->get() as $line) {$workflow->removeLine($line);}
        $header->delete();
    }

    public function destroy(int $id, PurchaseWorkflow $workflow)
    {
        DB::transaction(function() use($id,$workflow){$workflow->lockSerialNamespace();$this->deleteHeader($id,$workflow);});
        return redirect()->route('purchaseList')->with('status',__('purchases.invoice_deleted'));
    }

    public function bulkDelete(Request $request, PurchaseWorkflow $workflow)
    {
        $data=$request->validate(['ids'=>['required','array','min:1'],'ids.*'=>['integer','distinct']]);
        DB::transaction(function() use($data,$workflow){$workflow->lockSerialNamespace();foreach($data['ids'] as $id){$this->deleteHeader($id,$workflow);}});
        return redirect()->route('purchaseList')->with('status',__('purchases.invoice_deleted'));
    }
}
