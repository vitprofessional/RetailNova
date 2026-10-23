<?php
// Read-only local QA inspection. The existing guard refuses anything except localhost retailnova_local.
if (($argv[1] ?? '') !== '--check') {
    throw new RuntimeException('Use --check; this script has no write mode.');
}
require __DIR__.'/ApplyLocalPurchaseHeaderMigration.php';

use Illuminate\Support\Facades\DB;

$qaProducts = DB::table('products')->whereIn('name', [
    'SPRINT02-QA-20261012 Product A', 'SPRINT02-QA-20261012 Product B',
])->pluck('id');
$purchaseIds = DB::table('purchase_products')->whereIn('productName', $qaProducts)->pluck('id');
$owner = DB::table('admin_users')->orderBy('id')->first(['id', 'role', 'businessId']);
$saleIds = DB::table('invoice_items')->whereIn('purchaseId', $purchaseIds)->pluck('saleId')->unique();
echo json_encode([
    'first_account_scope' => $owner,
    'business_scoped_account_count' => DB::table('admin_users')->where('businessId', 1)->count(),
    'serial_required' => DB::table('business_setups')->where('id', 1)->value('sales_serial_selection_required'),
    'qa_stock' => DB::table('product_stocks')->whereIn('purchaseId', $purchaseIds)->get(['purchaseId', 'currentStock']),
    'qa_serials' => DB::table('product_serials')->whereIn('purchaseId', $purchaseIds)->get(['id', 'purchaseId', 'serialNumber', 'status', 'saleId']),
    'qa_sales' => DB::table('sale_products')->whereIn('id', $saleIds)->get(['id', 'invoice', 'totalSale', 'discountAmount', 'additionalChargeAmount', 'grandTotal', 'paidAmount', 'invoiceDue', 'businessId']),
    'qa_sale_items' => DB::table('invoice_items')->whereIn('saleId', $saleIds)->get(['saleId', 'purchaseId', 'qty', 'salePrice', 'totalSale']),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
