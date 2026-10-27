<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseProduct;
use App\Models\PurchaseReturn;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\ReturnPurchaseItem;
use App\Models\ReturnSaleItem;
use App\Models\SaleProduct;
use App\Models\SaleReturn;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Idempotent, local-only data fixtures for the authenticated mobile browser audit.
 * Run against a disposable SQLite database; never against production or the dev DB.
 */
class MobileQualityAuditSeeder extends Seeder
{
    private array $documentIds = [];

    public function run(): void
    {
        $database = (string) DB::connection()->getDatabaseName();
        $safeTestPath = str_contains(str_replace('\\', '/', $database), '/tests-output/');

        if (! app()->environment(['local', 'testing'])
            || DB::connection()->getDriverName() !== 'sqlite'
            || (! app()->environment('testing') && ! $safeTestPath)) {
            throw new RuntimeException('Mobile quality audit fixtures require a disposable SQLite database under tests-output/.');
        }

        $actorQuery = AdminUser::withoutGlobalScopes()->whereNotNull('businessId');
        if ($mail = env('RN_AUDIT_EMAIL')) {
            $actorQuery->where('mail', $mail);
        }
        $actor = $actorQuery->orderBy('id')->first();
        if (! $actor || ! $actor->businessId) {
            throw new RuntimeException('Seed a local business admin before running the mobile quality audit seeder.');
        }

        $businessId = (int) $actor->businessId;
        if (! BusinessSetup::withoutGlobalScopes()->whereKey($businessId)->exists()) {
            throw new RuntimeException('Audit admin business does not exist.');
        }

        // This account is a disposable fixture. A Bengali name avoids false Latin-digit findings.
        $actor->forceFill(['fullName' => 'পরীক্ষা প্রশাসক', 'preferred_locale' => 'bn'])->save();
        $customer = $this->firstOrCreate(Customer::class, ['businessId' => $businessId, 'name' => 'অডিট কাস্টমার'], ['mobile' => '01700000000']);
        $supplier = $this->firstOrCreate(Supplier::class, ['businessId' => $businessId, 'name' => 'অডিট মহাজন'], ['mobile' => '01800000000']);
        $product = $this->firstOrCreate(Product::class, ['businessId' => $businessId, 'name' => 'অডিট পণ্য']);

        $purchase = $this->firstOrCreate(PurchaseProduct::class,
            ['businessId' => $businessId, 'invoice' => 'MOBILE-AUDIT-PURCHASE', 'productName' => (string) $product->id],
            ['supplier' => (string) $supplier->id, 'purchase_date' => now()->toDateString(), 'qty' => 2,
                'buyPrice' => 100, 'salePriceExVat' => 150, 'totalAmount' => 200]);
        $purchaseInvoice = $this->firstOrCreate(PurchaseInvoice::class,
            ['businessId' => $businessId, 'invoice' => 'MOBILE-AUDIT-PURCHASE'],
            ['supplier_id' => $supplier->id, 'purchase_date' => now()->toDateString(), 'total_amount' => 200,
                'grand_total' => 200, 'paid_amount' => 100, 'due_amount' => 100]);
        if (! $purchase->purchase_invoice_id) {
            $purchase->forceFill(['purchase_invoice_id' => $purchaseInvoice->id])->save();
        }

        $sale = $this->firstOrCreate(SaleProduct::class,
            ['businessId' => $businessId, 'invoice' => 'MOBILE-AUDIT-SALE'],
            ['date' => now()->toDateString(), 'customerId' => $customer->id, 'grandTotal' => 150,
                'totalSale' => 150, 'paidAmount' => 100, 'invoiceDue' => 50, 'curDue' => 50,
                'salespersonId' => $actor->id]);
        $this->firstOrCreate(InvoiceItem::class,
            ['businessId' => $businessId, 'saleId' => $sale->id, 'purchaseId' => $purchase->id],
            ['qty' => 1, 'salePrice' => 150, 'buyPrice' => 100, 'totalSale' => 150,
                'totalPurchase' => 100, 'profitTotal' => 50]
        );

        $quotation = $this->firstOrCreate(Quotation::class,
            ['businessId' => $businessId, 'quote_number' => 'MOBILE-AUDIT-QUOTE'],
            ['customer_id' => $customer->id, 'date' => now()->toDateString(), 'status' => 'draft',
                'subtotal' => 150, 'discount_total' => 0, 'grand_total' => 150]
        );
        $this->firstOrCreate(QuotationItem::class,
            ['businessId' => $businessId, 'quotation_id' => $quotation->id, 'product_id' => $product->id],
            ['description' => 'অডিট পণ্য', 'qty' => 1, 'unit_price' => 150,
                'discount_percent' => 0, 'discount_amount' => 0, 'line_total' => 150]
        );

        $service = $this->firstOrCreate(ServiceInvoice::class,
            ['businessId' => $businessId, 'invoice_number' => 'MOBILE-AUDIT-SERVICE'],
            ['customer_id' => $customer->id, 'is_walkin' => false, 'total_amount' => 80]
        );
        $this->firstOrCreate(ServiceInvoiceItem::class,
            ['businessId' => $businessId, 'invoice_id' => $service->id, 'service_name' => 'অডিট সেবা'],
            ['rate' => 80, 'qty' => 1, 'line_total' => 80]
        );

        $saleReturn = $this->firstOrCreate(SaleReturn::class,
            ['businessId' => $businessId, 'saleId' => $sale->id],
            ['totalReturnAmount' => 20]
        );
        $this->firstOrCreate(ReturnSaleItem::class,
            ['businessId' => $businessId, 'returnId' => $saleReturn->id, 'productId' => $product->id],
            ['saleId' => $sale->id, 'purchaseId' => $purchase->id, 'customerId' => $customer->id, 'qty' => 1]
        );
        $purchaseReturn = $this->firstOrCreate(PurchaseReturn::class,
            ['businessId' => $businessId, 'purchaseId' => $purchase->id],
            ['totalReturnAmount' => 10]
        );
        $this->firstOrCreate(ReturnPurchaseItem::class,
            ['businessId' => $businessId, 'returnId' => $purchaseReturn->id, 'productId' => $product->id],
            ['purchaseId' => $purchase->id, 'supplierId' => $supplier->id, 'qty' => 1]
        );

        $this->documentIds = [
            'sale-invoice' => $sale->id,
            'purchase-invoice' => $purchaseInvoice->id,
            'quotation' => $quotation->id,
            'service-invoice' => $service->id,
            'sale-return' => $saleReturn->id,
            'purchase-return' => $purchaseReturn->id,
            'customer-ledger' => $customer->id,
            'supplier-ledger' => $supplier->id,
        ];
    }

    public function documentIds(): array
    {
        return $this->documentIds;
    }

    private function firstOrCreate(string $modelClass, array $keys, array $values = []): object
    {
        $query = $modelClass::withoutGlobalScopes();
        foreach ($keys as $column => $value) {
            $query->where($column, $value);
        }

        return $query->first() ?? $modelClass::forceCreate($keys + $values);
    }
}
