<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read-only report for purchases whose tenant ownership was never backfilled. */
class AuditLegacyPurchaseOwnership extends Command
{
    protected $signature = 'ownership:audit-legacy-purchases {--json : Emit a JSON report} {--limit=25 : Maximum rows to inspect in this page (1-100)} {--after-id=0 : Continue after this purchase ID}';
    protected $description = 'Read-only audit of legacy purchase business ownership evidence';

    public function handle(): int
    {
        foreach ([
            'business_setups' => ['id'],
            'purchase_products' => ['id', 'businessId', 'productName', 'supplier', 'purchase_invoice_id'],
            'purchase_invoices' => ['id', 'businessId', 'supplier_id'],
            'products' => ['id', 'businessId'],
            'suppliers' => ['id', 'businessId', 'deleted_at'],
            'product_stocks' => ['id', 'purchaseId', 'productId', 'businessId', 'currentStock'],
            'product_serials' => ['id', 'purchaseId', 'productId', 'saleId', 'status'],
            'invoice_items' => ['id', 'purchaseId', 'saleId', 'businessId'],
            'sale_products' => ['id', 'businessId'],
        ] as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->error("Required table missing: {$table}. No report generated.");
                return self::FAILURE;
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $this->error("Required column missing: {$table}.{$column}. No report generated.");
                    return self::FAILURE;
                }
            }
        }

        $rows = [];
        $purchaseCount = DB::table('purchase_products')->count();
        $nullOwnershipCount = DB::table('purchase_products')->whereNull('businessId')->count();
        $limit = min(100, max(1, (int) $this->option('limit')));
        $afterId = max(0, (int) $this->option('after-id'));
        $purchases = DB::table('purchase_products')->whereNull('businessId')->where('id', '>', $afterId)
            ->orderBy('id')->limit($limit)->get(['id', 'businessId', 'productName', 'supplier', 'purchase_invoice_id']);

        foreach ($purchases as $purchase) {
                $productId = $this->id($purchase->productName);
                $supplierId = $this->id($purchase->supplier);
                $headerId = $this->id($purchase->purchase_invoice_id);
                $product = $productId ? DB::table('products')->where('id', $productId)->first(['id', 'businessId']) : null;
                $supplier = $supplierId ? DB::table('suppliers')->where('id', $supplierId)->first(['id', 'businessId', 'deleted_at']) : null;
                $header = $headerId ? DB::table('purchase_invoices')->where('id', $headerId)->first(['id', 'businessId', 'supplier_id']) : null;
                $headerSupplierId = $this->id($header->supplier_id ?? null);
                $headerSupplier = $headerSupplierId ? DB::table('suppliers')->where('id', $headerSupplierId)->first(['id', 'businessId', 'deleted_at']) : null;
                $stocks = DB::table('product_stocks')->where('purchaseId', $purchase->id)->orderBy('id')
                    ->limit(101)->get(['id', 'productId', 'businessId', 'currentStock']);
                $serials = DB::table('product_serials')->where('purchaseId', $purchase->id)->orderBy('id')
                    ->limit(101)->get(['id', 'productId', 'saleId', 'status']);
                $items = DB::table('invoice_items')->where('purchaseId', $purchase->id)->orderBy('id')
                    ->limit(101)->get(['id', 'saleId', 'businessId']);
                $stocksTruncated = $stocks->count() > 100;
                $serialsTruncated = $serials->count() > 100;
                $itemsTruncated = $items->count() > 100;
                $stocks = $stocks->take(100);
                $serials = $serials->take(100);
                $items = $items->take(100);

                $saleIds = collect($items)->pluck('saleId')->merge(collect($serials)->pluck('saleId'))
                    ->map(fn ($id) => $this->id($id))->filter()->unique()->values();
                $sales = $saleIds->isEmpty() ? collect() : DB::table('sale_products')->whereIn('id', $saleIds)->get(['id', 'businessId']);
                $salesById = $sales->keyBy('id');

                $evidence = [];
                $this->addEvidence($evidence, 'purchase', $purchase->businessId);
                $this->addEvidence($evidence, 'invoice_header', $header->businessId ?? null);
                $this->addEvidence($evidence, 'product', $product->businessId ?? null);
                $this->addEvidence($evidence, 'supplier', $supplier->businessId ?? null);
                if ($headerSupplierId && $headerSupplierId !== $supplierId) {
                    $this->addEvidence($evidence, 'header_supplier', $headerSupplier->businessId ?? null);
                }
                foreach ($stocks as $stock) $this->addEvidence($evidence, 'stock#'.$stock->id, $stock->businessId);
                foreach ($items as $item) $this->addEvidence($evidence, 'invoice_item#'.$item->id, $item->businessId);
                foreach ($sales as $sale) $this->addEvidence($evidence, 'sale#'.$sale->id, $sale->businessId);

                $businessIds = collect($evidence)->pluck('business_id')->unique()->values();
                $missingBusinessIds = $businessIds->diff(DB::table('business_setups')->whereIn('id', $businessIds)->pluck('id'))->values();
                $issues = [];
                if ($productId && !$product) $issues[] = 'missing_product';
                if (!$productId) $issues[] = 'missing_product_reference';
                if ($supplierId && !$supplier) $issues[] = 'missing_supplier';
                if (!$supplierId) $issues[] = 'missing_supplier_reference';
                if ($supplier && $supplier->deleted_at) $issues[] = 'supplier_soft_deleted';
                if ($headerId && !$header) $issues[] = 'missing_invoice_header';
                if (!$headerId) $issues[] = 'no_invoice_header';
                if ($header && $headerSupplierId && !$headerSupplier) $issues[] = 'header_supplier_missing';
                if ($header && !$headerSupplierId) $issues[] = 'header_supplier_reference_missing';
                if ($headerSupplier && $headerSupplier->deleted_at) $issues[] = 'header_supplier_soft_deleted';
                if ($stocks->isEmpty()) $issues[] = 'missing_stock_row';
                if ($stocks->count() > 1) $issues[] = 'multiple_stock_rows';
                foreach ($stocks as $stock) {
                    if ($productId && $this->id($stock->productId) !== $productId) $issues[] = 'stock_product_mismatch';
                }
                foreach ($serials as $serial) {
                    if ($productId && $this->id($serial->productId) !== $productId) $issues[] = 'serial_product_mismatch';
                    $serialSaleId = $this->id($serial->saleId);
                    if ($serial->saleId !== null && $serial->saleId !== '' && (!$serialSaleId || !$salesById->has($serialSaleId))) {
                        $issues[] = 'serial_missing_sale';
                    }
                    if ($serial->status === 'sold' && !$serialSaleId) $issues[] = 'sold_serial_missing_sale_link';
                    if ($serialSaleId && in_array($serial->status, [null, 'available', 'returned'], true)) {
                        $issues[] = 'available_serial_has_sale_link';
                    }
                }
                foreach ($items as $item) {
                    $itemSaleId = $this->id($item->saleId);
                    if ($item->saleId === null || $item->saleId === '') {
                        $issues[] = 'invoice_item_sale_reference_missing';
                    } elseif (!$itemSaleId || !$salesById->has($itemSaleId)) {
                        $issues[] = 'invoice_item_missing_sale';
                    }
                }
                if ($header && $supplierId && (int) $header->supplier_id !== $supplierId) $issues[] = 'header_supplier_mismatch';
                if ($missingBusinessIds->isNotEmpty()) $issues[] = 'references_missing_business_setup';
                if ($stocksTruncated || $serialsTruncated || $itemsTruncated) $issues[] = 'related_rows_truncated';

                $sharedQuery = $productId ? DB::table('purchase_products')->where('productName', (string) $productId) : null;
                $sharedCount = $sharedQuery ? (clone $sharedQuery)->count() : 0;
                $shared = $sharedQuery ? $sharedQuery->orderBy('id')->limit(100)->get(['id', 'businessId']) : collect();
                $sharedBusinessIds = $productId ? DB::table('purchase_products')->where('productName', (string) $productId)
                    ->whereNotNull('businessId')->select('businessId')->distinct()->orderBy('businessId')->limit(101)->pluck('businessId')
                    ->map(fn ($id) => $this->id($id))->filter()->values() : collect();
                $sharedOwnersTruncated = $sharedBusinessIds->count() > 100;
                $sharedBusinessIds = $sharedBusinessIds->take(100)->unique()->values();
                if ($sharedCount > 1) $issues[] = 'product_used_by_multiple_purchase_rows';
                if ($sharedBusinessIds->count() > 1) $issues[] = 'product_purchase_rows_span_businesses';
                if ($shared->count() < $sharedCount || $sharedOwnersTruncated) $issues[] = 'shared_product_details_truncated';

                $ownerIds = $businessIds->map(fn ($id) => (int) $id)->values();
                $structuralConflict = collect($issues)->contains(fn ($issue) => in_array($issue, [
                    'stock_product_mismatch', 'serial_product_mismatch', 'header_supplier_mismatch',
                    'sold_serial_missing_sale_link', 'available_serial_has_sale_link',
                ], true));
                if ($ownerIds->count() > 1 || $sharedBusinessIds->count() > 1 || $structuralConflict) {
                    $classification = 'Conflicting ownership evidence';
                } elseif ($ownerIds->count() === 1 && $missingBusinessIds->isEmpty()
                    && !$this->hasIncompleteEvidence($issues)
                    && ($this->hasIndependentEvidence($evidence) || $this->id($header->businessId ?? null) !== null)) {
                    $classification = 'Consistent ownership evidence';
                } else {
                    $classification = 'Incomplete or ambiguous ownership';
                }

                $rows[] = [
                    'purchase_id' => (int) $purchase->id,
                    'purchase_business_id' => $purchase->businessId,
                    'invoice_header_id' => $headerId,
                    'header_business_id' => $header->businessId ?? null,
                    'product_id' => $productId,
                    'product_business_id' => $product->businessId ?? null,
                    'product_purchase_ids' => $shared->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                    'product_purchase_owners' => $shared->map(fn ($row) => ['purchase_id' => (int) $row->id, 'business_id' => $row->businessId])->values()->all(),
                    'product_purchase_count' => $sharedCount,
                    'supplier_id' => $supplierId,
                    'supplier_business_id' => $supplier->businessId ?? null,
                    'supplier_deleted' => (bool) ($supplier->deleted_at ?? false),
                    'header_supplier_id' => $headerSupplierId,
                    'header_supplier_business_id' => $headerSupplier->businessId ?? null,
                    'stock' => $stocks->map(fn ($stock) => ['id' => (int) $stock->id, 'product_id' => $this->id($stock->productId), 'business_id' => $stock->businessId, 'qty' => $stock->currentStock])->values()->all(),
                    'serials' => $serials->map(fn ($serial) => ['id' => (int) $serial->id, 'product_id' => $this->id($serial->productId), 'sale_id' => $this->id($serial->saleId), 'status' => $serial->status])->values()->all(),
                    'sale_links' => $saleIds->map(fn ($id) => ['sale_id' => $id, 'business_id' => $salesById->get($id)->businessId ?? null])->all(),
                    'ownership_evidence' => $evidence,
                    'missing_business_setup_ids' => $missingBusinessIds->all(),
                    'classification' => $classification,
                    'issues' => array_values(array_unique($issues)),
                ];
        }

        $hasMore = DB::table('purchase_products')->whereNull('businessId')->where('id', '>', $purchases->last()->id ?? $afterId)->exists();

        $summary = [
            'purchase_records' => $purchaseCount,
            'null_business_ownership' => $nullOwnershipCount,
            'audited_records' => count($rows),
            'limit' => $limit,
            'after_id' => $afterId,
            'has_more' => $hasMore,
            'next_after_id' => $hasMore ? ($purchases->last()->id ?? $afterId) : null,
            'consistent' => collect($rows)->where('classification', 'Consistent ownership evidence')->count(),
            'ambiguous' => collect($rows)->where('classification', 'Incomplete or ambiguous ownership')->count(),
            'conflicting' => collect($rows)->where('classification', 'Conflicting ownership evidence')->count(),
            'records' => $rows,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Purchase', 'Header', 'Product', 'Supplier', 'Stock rows', 'Serials', 'Sales', 'Classification', 'Issues'],
                array_map(fn ($row) => [
                    $row['purchase_id'],
                    $row['invoice_header_id'] ?? '—',
                    $row['product_id'].' / biz '.($row['product_business_id'] ?? 'NULL'),
                    ($row['supplier_id'] ?? 'NULL').' / biz '.($row['supplier_business_id'] ?? 'NULL'),
                    count($row['stock']), count($row['serials']), count($row['sale_links']),
                    $row['classification'], implode(',', $row['issues']) ?: '—',
                ], $rows));
            $this->line('Total purchases: '.$summary['purchase_records'].'; NULL owners: '.$summary['null_business_ownership']);
            $this->line('Consistent: '.$summary['consistent'].'; ambiguous: '.$summary['ambiguous'].'; conflicting: '.$summary['conflicting']);
            $this->line('No purchase or related business records were modified.');
        }

        return self::SUCCESS;
    }

    private function id(mixed $value): ?int
    {
        return $value !== null && $value !== '' && is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function addEvidence(array &$evidence, string $source, mixed $businessId): void
    {
        $id = $this->id($businessId);
        if ($id !== null) $evidence[] = ['source' => $source, 'business_id' => $id];
    }

    private function hasIndependentEvidence(array $evidence): bool
    {
        return count(array_unique(array_column($evidence, 'source'))) >= 2;
    }

    private function hasIncompleteEvidence(array $issues): bool
    {
        return (bool) array_intersect($issues, [
            'missing_product', 'missing_product_reference', 'missing_supplier', 'missing_supplier_reference',
            'missing_invoice_header', 'header_supplier_missing', 'header_supplier_reference_missing',
            'missing_stock_row', 'serial_missing_sale', 'invoice_item_missing_sale', 'invoice_item_sale_reference_missing',
            'references_missing_business_setup', 'related_rows_truncated', 'shared_product_details_truncated',
        ]);
    }
}
