<?php
// Read-only inspection; refuse anything except the guarded named local database.
if (($argv[1] ?? '') !== '--check') { throw new RuntimeException('Use --check; no write mode exists.'); }
require __DIR__.'/ApplyLocalPurchaseHeaderMigration.php';
use Illuminate\Support\Facades\DB;
$ids=DB::table('products')->whereIn('name',['SPRINT02-QA-20261012 Product A','SPRINT02-QA-20261012 Product B'])->pluck('id');
$headers=DB::table('purchase_invoices')->where('special_note','like','SPRINT04-QA%')->orderBy('id')->get(['id','invoice','businessId','supplier_id','total_amount','discount_amount','grand_total','paid_amount','due_amount','special_note']);
$lines=DB::table('purchase_products')->whereIn('purchase_invoice_id',$headers->pluck('id'))->get(['id','purchase_invoice_id','productName','qty','buyPrice','salePriceExVat','totalAmount','grandTotal','paidAmount','dueAmount']);
$balances=DB::table('purchase_products')->where('supplier',4)->selectRaw('SUM(grandTotal) as grand, SUM(paidAmount) as paid, SUM(dueAmount) as due')->first();
echo json_encode(['history'=>purchaseHistoryFingerprint(),'qa_headers'=>$headers,'qa_lines'=>$lines,
'qa_serials'=>DB::table('product_serials')->whereIn('purchaseId',$lines->pluck('id'))->get(['id','purchaseId','serialNumber','status']),
'qa_product_stock'=>DB::table('product_stocks')->whereIn('productId',$ids)->selectRaw('productId,SUM(currentStock) as stock')->groupBy('productId')->get(),
'supplier_purchase_totals'=>$balances,'serial_required'=>DB::table('business_setups')->where('id',1)->value('sales_serial_selection_required')],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
