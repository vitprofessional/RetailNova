<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read-only, resource-bounded evidence report for legacy purchase dependencies. */
class AuditLegacyPurchaseDependencies extends Command
{
    private const MAX_PURCHASES = 10;
    private const MAX_RELATED_PER_PURCHASE = 50;
    private const MAX_RELATED_TOTAL = 500;
    private const MAX_AUDIT_ROWS = 100;
    private const MAX_OUTPUT_BYTES = 524288;

    protected $signature = 'ownership:audit-legacy-dependencies {--limit=10 : Purchases per page (1-10)} {--after-id=0 : Continue after purchase ID} {--related-limit=50 : Maximum related rows per purchase (1-50)} {--json : Emit JSON}';
    protected $description = 'Read-only audit of legacy purchase returns, sale items and available inventory history';

    public function handle(): int
    {
        $schema = $this->discoverSchema();
        if (isset($schema['error'])) return $this->schemaFailure($schema['error']);
        $columns = $schema['columns'];

        $limit = min(self::MAX_PURCHASES, max(1, (int) $this->option('limit')));
        $relatedLimit = min(self::MAX_RELATED_PER_PURCHASE, max(1, (int) $this->option('related-limit')));
        $afterId = max(0, (int) $this->option('after-id'));
        $relatedUsed = 0;
        $globalIssues = [];
        $purchases = DB::table('purchase_products')->whereNull('businessId')->where('id', '>', $afterId)
            ->orderBy('id')->limit($limit + 1)->get(['id', 'businessId', 'productName', 'supplier', 'qty']);
        $hasMorePurchases = $purchases->count() > $limit;
        $purchases = $purchases->take($limit)->values();
        $result = [];

        foreach ($purchases as $purchase) {
            $id = (int) $purchase->id;
            $recordIssues = ['purchase_business_unresolved'];
            $remaining = max(0, self::MAX_RELATED_TOTAL - $relatedUsed);
            $perPurchaseCap = min($relatedLimit, $remaining);

            $headerForeign = $columns['purchase_returns']['purchase'];
            [$returnRows, $returnsTruncated] = $this->boundedRows(
                DB::table('purchase_returns')->where($headerForeign, (string) $id)->orderBy('id'),
                $perPurchaseCap
            );
            $relatedUsed += count($returnRows);
            $returnIds = $returnRows->pluck('id')->map(fn ($v) => (int) $v)->all();

            $itemPurchase = $columns['return_purchase_items']['purchase'];
            $itemReturn = $columns['return_purchase_items']['return'];
            $remaining = max(0, self::MAX_RELATED_TOTAL - $relatedUsed);
            [$returnItems, $returnItemsTruncated] = $this->boundedRows(
                DB::table('return_purchase_items')->where(function ($q) use ($itemPurchase, $itemReturn, $id, $returnIds) {
                    $q->where($itemPurchase, (string) $id);
                    if ($returnIds) $q->orWhereIn($itemReturn, $returnIds);
                })->orderBy('id'), min($relatedLimit, $remaining)
            );
            $relatedUsed += count($returnItems);
            $returnProductIds = $returnItems->map(fn ($item) => $this->positiveId($item->{$columns['return_purchase_items']['product']}))->filter()->unique()->values()->all();
            $returnSupplierIds = $returnItems->map(fn ($item) => $this->optionalId($item, $columns['return_purchase_items']['all'], ['supplierId', 'supplier_id']))->filter()->unique()->values()->all();
            [$returnProducts, $returnProductsTruncated] = $returnProductIds
                ? $this->boundedRows(DB::table('products')->whereIn('id', $returnProductIds)->orderBy('id'), max(0, self::MAX_RELATED_TOTAL - $relatedUsed), ['id', 'businessId'])
                : [collect(), false];
            $returnProducts = $returnProducts->keyBy('id');
            $relatedUsed += $returnProducts->count();
            if ($returnProductsTruncated) { $recordIssues[] = 'return_product_lookup_truncated'; $globalIssues[] = 'return_product_lookup_truncated'; }
            foreach ($returnProductIds as $relatedProductId) if (!$returnProducts->has($relatedProductId) && !$returnProductsTruncated) $recordIssues[] = 'return_item_product_missing';
            [$returnSuppliers, $returnSuppliersTruncated] = $returnSupplierIds
                ? $this->boundedRows(DB::table('suppliers')->whereIn('id', $returnSupplierIds)->orderBy('id'), max(0, self::MAX_RELATED_TOTAL - $relatedUsed), ['id', 'businessId'])
                : [collect(), false];
            $returnSuppliers = $returnSuppliers->keyBy('id');
            $relatedUsed += $returnSuppliers->count();
            if ($returnSuppliersTruncated) { $recordIssues[] = 'return_supplier_lookup_truncated'; $globalIssues[] = 'return_supplier_lookup_truncated'; }
            foreach ($returnSupplierIds as $relatedSupplierId) if (!$returnSuppliers->has($relatedSupplierId) && !$returnSuppliersTruncated) $recordIssues[] = 'return_item_supplier_missing';

            $invoicePurchase = $columns['invoice_items']['purchase'];
            $invoiceSale = $columns['invoice_items']['sale'];
            $remaining = max(0, self::MAX_RELATED_TOTAL - $relatedUsed);
            [$invoiceRows, $invoiceTruncated] = $this->boundedRows(
                DB::table('invoice_items')->where($invoicePurchase, (string) $id)->orderBy('id'), min($relatedLimit, $remaining)
            );
            $relatedUsed += count($invoiceRows);
            $saleIds = $invoiceRows->pluck($invoiceSale)->map(fn ($v) => $this->positiveId($v))->filter()->unique()->values()->all();
            [$sales, $salesTruncated] = $saleIds
                ? $this->boundedRows(DB::table('sale_products')->whereIn('id', $saleIds)->orderBy('id'), max(0, self::MAX_RELATED_TOTAL - $relatedUsed), ['id', $columns['sale_products']['business']])
                : [collect(), false];
            $sales = $sales->keyBy('id');
            $relatedUsed += $sales->count();
            if ($salesTruncated) { $recordIssues[] = 'sale_lookup_truncated'; $globalIssues[] = 'sale_lookup_truncated'; }

            $remaining = max(0, self::MAX_RELATED_TOTAL - $relatedUsed);
            [$stockRows, $stockTruncated] = $this->boundedRows(
                DB::table('product_stocks')->where('purchaseId', $id)->orderBy('id'), min($relatedLimit, $remaining),
                ['id', 'productId', 'businessId', 'currentStock']
            );
            $relatedUsed += count($stockRows);

            $productId = $this->positiveId($purchase->productName);
            [$productRows, $productLookupTruncated] = $productId
                ? $this->boundedRows(DB::table('products')->where('id', $productId)->orderBy('id'), max(0, self::MAX_RELATED_TOTAL - $relatedUsed), ['id', 'businessId'])
                : [collect(), false];
            $product = $productRows->first();
            $relatedUsed += $productRows->count();
            if ($productLookupTruncated) { $recordIssues[] = 'purchase_product_lookup_truncated'; $globalIssues[] = 'purchase_product_lookup_truncated'; }
            if (!$productId) $recordIssues[] = 'purchase_product_reference_missing';
            elseif (!$product && !$productLookupTruncated) $recordIssues[] = 'purchase_product_missing';
            $productBusinessId = $this->positiveId($product->businessId ?? null);
            if ($product && !$productBusinessId) $recordIssues[] = 'purchase_product_business_unresolved';
            if ($productBusinessId && $this->positiveId($purchase->businessId) && $productBusinessId !== $this->positiveId($purchase->businessId)) $recordIssues[] = 'purchase_product_business_conflict';

            foreach ($returnItems as $row) {
                $rowPurchaseId = $this->positiveId($row->{$itemPurchase});
                $rowReturnId = $this->positiveId($row->{$itemReturn});
                if ($rowPurchaseId !== $id) $recordIssues[] = 'return_item_purchase_mismatch';
                if (!$rowReturnId || !in_array($rowReturnId, $returnIds, true)) $recordIssues[] = 'return_item_header_missing_or_mismatched';
                $itemProductId = $this->positiveId($row->{$columns['return_purchase_items']['product']});
                if (!$itemProductId) $recordIssues[] = 'return_item_product_reference_missing';
                elseif ($itemProductId !== $productId) $recordIssues[] = 'return_item_product_mismatch';
                if ((int) ($row->qty ?? 0) <= 0) $recordIssues[] = 'return_item_qty_invalid';
                $itemSupplierId = $this->optionalId($row, $columns['return_purchase_items']['all'], ['supplierId', 'supplier_id']);
                if ($itemSupplierId && $itemSupplierId !== $this->positiveId($purchase->supplier)) $recordIssues[] = 'return_item_supplier_mismatch';
                $itemBusiness = $this->optionalId($row, $columns['return_purchase_items']['all'], ['businessId', 'business_id']);
                $header = $returnRows->firstWhere('id', $rowReturnId);
                $headerBusiness = $header ? $this->optionalId($header, $columns['purchase_returns']['all'], ['businessId', 'business_id']) : null;
                if ($itemBusiness && $headerBusiness && $itemBusiness !== $headerBusiness) $recordIssues[] = 'return_business_conflict';
            }
            foreach ($invoiceRows as $row) {
                $saleId = $this->positiveId($row->{$invoiceSale});
                if (!$saleId) $recordIssues[] = 'invoice_item_sale_reference_missing';
                elseif (!$sales->has($saleId) && !$salesTruncated) $recordIssues[] = 'invoice_item_sale_missing';
                if ((int) ($row->qty ?? 0) <= 0) $recordIssues[] = 'invoice_item_qty_invalid';
                if ($saleId && $sales->has($saleId)) {
                    $saleBusiness = $this->positiveId($sales->get($saleId)->{$columns['sale_products']['business']});
                    $itemBusiness = $this->optionalId($row, $columns['invoice_items']['all'], ['businessId', 'business_id']);
                    if ($saleBusiness && $itemBusiness && $saleBusiness !== $itemBusiness) $recordIssues[] = 'invoice_item_sale_business_conflict';
                }
            }
            foreach ($stockRows as $stock) {
                if ($this->positiveId($stock->productId) !== $productId) $recordIssues[] = 'stock_product_mismatch';
                if (!$this->positiveId($stock->businessId)) $recordIssues[] = 'stock_business_unresolved';
                elseif ($productBusinessId && $this->positiveId($stock->businessId) !== $productBusinessId) $recordIssues[] = 'purchase_product_business_conflict';
            }
            if ((int) $purchase->qty > 0 && $returnItems->sum(fn ($item) => max(0, (int) ($item->qty ?? 0))) > (int) $purchase->qty) $recordIssues[] = 'returned_quantity_exceeds_purchase_quantity';

            $remaining = max(0, self::MAX_AUDIT_ROWS - ($schema['audit_rows_used'] ?? 0));
            $history = $this->auditHistory($schema['audit_available'], $id, $stockRows->pluck('id')->map(fn ($v) => (int) $v)->all(), min($relatedLimit, $remaining));
            $schema['audit_rows_used'] = ($schema['audit_rows_used'] ?? 0) + count($history['rows']);
            $globalIssues = array_merge($globalIssues, $history['issues']);
            $auditTruncated = in_array('audit_history_truncated', $history['issues'], true);
            $truncationIssues = [];
            if ($returnsTruncated) $truncationIssues[] = 'purchase_returns_truncated';
            if ($returnItemsTruncated) $truncationIssues[] = 'return_items_truncated';
            if ($invoiceTruncated) $truncationIssues[] = 'sale_invoice_items_truncated';
            if ($stockTruncated) $truncationIssues[] = 'stock_rows_truncated';
            if ($auditTruncated) $truncationIssues[] = 'audit_history_truncated';
            if ($returnProductsTruncated) $truncationIssues[] = 'return_product_lookup_truncated';
            if ($returnSuppliersTruncated) $truncationIssues[] = 'return_supplier_lookup_truncated';
            if ($salesTruncated) $truncationIssues[] = 'sale_lookup_truncated';
            if ($productLookupTruncated) $truncationIssues[] = 'purchase_product_lookup_truncated';
            if ($relatedUsed >= self::MAX_RELATED_TOTAL && ($returnsTruncated || $returnItemsTruncated || $invoiceTruncated || $stockTruncated || $returnProductsTruncated || $returnSuppliersTruncated || $salesTruncated || $productLookupTruncated)) $truncationIssues[] = 'total_related_record_budget_exhausted';
            if ($truncationIssues) $recordIssues = array_merge($recordIssues, $truncationIssues);
            if ($truncationIssues) $globalIssues = array_merge($globalIssues, $truncationIssues);

            $result[] = [
                'purchase_id' => $id,
                'purchase_business_id' => $purchase->businessId,
                'product_id' => $productId,
                'product_business_id' => $productBusinessId,
                'supplier_id' => $this->positiveId($purchase->supplier),
                'purchase_returns' => $returnRows->map(fn ($r) => ['id' => (int) $r->id, 'purchase_id' => $this->positiveId($r->{$headerForeign}), 'business_id' => $this->optionalId($r, $columns['purchase_returns']['all'], ['businessId', 'business_id'])])->all(),
                'return_items' => $returnItems->map(function ($r) use ($itemReturn, $itemPurchase, $columns, $returnProducts, $returnSuppliers) {
                    $relatedProductId = $this->positiveId($r->{$columns['return_purchase_items']['product']});
                    $supplierId = $this->optionalId($r, $columns['return_purchase_items']['all'], ['supplierId', 'supplier_id']);
                    return ['id' => (int) $r->id, 'return_id' => $this->positiveId($r->{$itemReturn}), 'purchase_id' => $this->positiveId($r->{$itemPurchase}), 'product_id' => $relatedProductId, 'product_business_id' => $this->positiveId($returnProducts->get($relatedProductId)->businessId ?? null), 'supplier_id' => $supplierId, 'supplier_business_id' => $this->positiveId($returnSuppliers->get($supplierId)->businessId ?? null), 'business_id' => $this->optionalId($r, $columns['return_purchase_items']['all'], ['businessId', 'business_id']), 'qty' => $this->optionalValue($r, ['qty'])];
                })->all(),
                'sale_invoice_items' => $invoiceRows->map(function ($r) use ($invoicePurchase, $invoiceSale, $sales, $columns) {
                    $saleId = $this->positiveId($r->{$invoiceSale});
                    $sale = $saleId ? $sales->get($saleId) : null;
                    return ['id' => (int) $r->id, 'purchase_id' => $this->positiveId($r->{$invoicePurchase}), 'sale_id' => $saleId, 'qty' => $this->optionalValue($r, ['qty']), 'business_id' => $this->optionalId($r, $columns['invoice_items']['all'], ['businessId', 'business_id']), 'sale_found' => (bool) $sale, 'sale_business_id' => $sale ? $this->positiveId($sale->{$columns['sale_products']['business']}) : null];
                })->all(),
                'stock' => $stockRows->map(fn ($r) => ['id' => (int) $r->id, 'product_id' => $this->positiveId($r->productId), 'business_id' => $this->positiveId($r->businessId), 'qty' => $r->currentStock])->all(),
                'history' => $history,
                'evidence_complete' => !$truncationIssues && !$history['issues'],
                'issues' => array_values(array_unique(array_merge($recordIssues, $history['issues']))),
            ];
        }

        if ($hasMorePurchases) $globalIssues[] = 'purchase_page_truncated';
        $report = ['read_only' => true, 'complete' => !$hasMorePurchases && !$globalIssues, 'ownership_complete' => false, 'incomplete_categories' => array_values(array_unique($globalIssues)),
            'limits' => ['purchases_per_page' => self::MAX_PURCHASES, 'related_per_purchase' => self::MAX_RELATED_PER_PURCHASE, 'related_total' => self::MAX_RELATED_TOTAL, 'audit_history_total' => self::MAX_AUDIT_ROWS, 'audit_value_bytes' => 16384, 'output_bytes' => self::MAX_OUTPUT_BYTES, 'estimated_max_selects' => 180],
            'resolved_schema' => ['purchase_return_business' => $columns['purchase_returns']['business'] ?? null, 'return_item_business' => $columns['return_purchase_items']['business'] ?? null, 'return_item_supplier' => $columns['return_purchase_items']['supplier'] ?? null, 'invoice_item_business' => $columns['invoice_items']['business'] ?? null],
            'limit' => $limit, 'related_limit' => $relatedLimit, 'related_records_used' => $relatedUsed, 'after_id' => $afterId,
            'has_more' => $hasMorePurchases, 'next_after_id' => $hasMorePurchases ? (int) $purchases->last()->id : null,
            'dedicated_stock_movement_tables' => $this->ledgerTables(), 'audit_history_available' => $schema['audit_available'], 'records' => $result];
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($json) > self::MAX_OUTPUT_BYTES) {
            $report['complete'] = false;
            $report['incomplete_categories'][] = 'output_size_budget_exceeded';
            $report['omitted_purchase_ids'] = array_column($result, 'purchase_id');
            $report['records'] = [];
            $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
        $this->line($json);
        return self::SUCCESS;
    }

    private function discoverSchema(): array
    {
        $definitions = [
            'purchase_returns' => ['id' => [['id']], 'purchase' => [['purchaseId', 'purchase_id']], 'optional' => [['businessId', 'business_id']]],
            'return_purchase_items' => ['id' => [['id']], 'return' => [['returnId', 'return_id']], 'purchase' => [['purchaseId', 'purchase_id']], 'product' => [['productId', 'product_id']], 'optional' => [['businessId', 'business_id'], ['supplierId', 'supplier_id']]],
            'invoice_items' => ['id' => [['id']], 'purchase' => [['purchaseId', 'purchase_id']], 'sale' => [['saleId', 'sale_id']], 'qty' => [['qty']], 'optional' => [['businessId', 'business_id']]],
            'sale_products' => ['id' => [['id']], 'business' => [['businessId', 'business_id']]],
            'purchase_products' => ['required' => ['id', 'businessId', 'productName', 'supplier', 'qty']],
            'product_stocks' => ['required' => ['id', 'purchaseId', 'productId', 'businessId', 'currentStock']],
            'products' => ['required' => ['id', 'businessId']],
            'suppliers' => ['required' => ['id', 'businessId']],
        ];
        $columns = [];
        foreach ($definitions as $table => $definition) {
            if (!Schema::hasTable($table)) return ['error' => "required table missing: {$table}"];
            $available = Schema::getColumnListing($table);
            $columns[$table] = ['all' => $available];
            foreach ($definition['required'] ?? [] as $required) if (!in_array($required, $available, true)) return ['error' => "required column missing: {$table}.{$required}"];
            foreach ($definition as $name => $groups) {
                if ($name === 'required') continue;
                foreach ($groups as $index => $candidates) {
                    $present = array_values(array_intersect($candidates, $available));
                    if ($name === 'optional') {
                        if (count($present) > 1) return ['error' => "ambiguous schema: {$table} optional ownership/reference variants"];
                        if (!$present) continue;
                        $key = $index === 0 ? 'business' : 'supplier';
                    } else {
                        if (!$present) return ['error' => "unsupported schema: {$table} has no supported {$name} column"];
                        if (count($present) > 1) return ['error' => "ambiguous schema: {$table} has multiple {$name} variants"];
                        $key = $name;
                    }
                    $columns[$table][$key] = $present[0];
                }
            }
        }
        $auditAvailable = Schema::hasTable('audits');
        if ($auditAvailable) {
            $auditColumns = Schema::getColumnListing('audits');
            foreach (['id', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'created_at'] as $required) {
                if (!in_array($required, $auditColumns, true)) return ['error' => 'unsupported audits schema'];
            }
        }
        return ['columns' => $columns, 'audit_available' => $auditAvailable, 'audit_rows_used' => 0];
    }

    private function boundedRows($query, int $cap, ?array $select = null): array
    {
        $rows = $query->limit($cap + 1)->get($select ?? ['*']);
        $truncated = $rows->count() > $cap;
        return [$rows->take($cap)->values(), $truncated];
    }

    private function auditHistory(bool $auditAvailable, int $purchaseId, array $stockIds, int $limit): array
    {
        if (!$auditAvailable) return ['available' => false, 'rows' => [], 'issues' => ['audit_history_unavailable']];
        $types = [
            'purchase' => ['ids' => [$purchaseId], 'names' => [\App\Models\PurchaseProduct::class]],
            'stock' => ['ids' => $stockIds, 'names' => [\App\Models\ProductStock::class]],
        ];
        foreach (Relation::morphMap() as $alias => $class) {
            if ($class === \App\Models\PurchaseProduct::class) $types['purchase']['names'][] = $alias;
            if ($class === \App\Models\ProductStock::class) $types['stock']['names'][] = $alias;
        }
        $rows = [];
        $issues = [];
        foreach ($types as $entity => $mapping) {
            if (!$mapping['ids']) continue;
            $remaining = max(0, $limit - count($rows));
            $metadata = DB::table('audits')->whereIn('auditable_type', array_values(array_unique($mapping['names'])))->whereIn('auditable_id', $mapping['ids'])->orderBy('id')->limit($remaining + 1)
                ->get(['id', 'event', 'auditable_type', 'auditable_id', 'created_at', DB::raw("LENGTH(COALESCE(old_values, '')) AS old_bytes"), DB::raw("LENGTH(COALESCE(new_values, '')) AS new_bytes")]);
            if ($metadata->count() > $remaining) $issues[] = 'audit_history_truncated';
            $metadata = $metadata->take($remaining)->values();
            $safeIds = $metadata->filter(function ($row) use (&$issues) {
                $safe = (int) $row->old_bytes <= 16384 && (int) $row->new_bytes <= 16384;
                if (!$safe) $issues[] = 'audit_history_value_too_large';
                return $safe;
            })->pluck('id')->all();
            $payloads = $safeIds ? DB::table('audits')->whereIn('id', $safeIds)
                ->whereRaw("LENGTH(COALESCE(old_values, '')) <= 16384 AND LENGTH(COALESCE(new_values, '')) <= 16384")
                ->get(['id', 'old_values', 'new_values'])->keyBy('id') : collect();
            foreach ($metadata as $row) {
                if ((int) $row->old_bytes > 16384 || (int) $row->new_bytes > 16384) continue;
                $payload = $payloads->get($row->id);
                if (!$payload) { $issues[] = 'audit_history_changed_during_read'; continue; }
                $rows[] = ['audit_id' => (int) $row->id, 'entity_type' => $entity, 'entity_id' => (int) $row->auditable_id, 'event' => in_array($row->event, ['created', 'updated', 'deleted', 'restored'], true) ? $row->event : 'other', 'changed_fields' => $this->safeHistory($this->jsonObject($payload->old_values), $this->jsonObject($payload->new_values)), 'created_at' => $row->created_at];
            }
        }
        return ['available' => true, 'rows' => $rows, 'issues' => array_values(array_unique($issues))];
    }

    private function safeHistory(?array $old, ?array $new): array
    {
        $safe = ['businessId' => 'id', 'business_id' => 'id', 'currentStock' => 'number', 'qty' => 'number', 'productId' => 'id', 'purchaseId' => 'id'];
        $result = [];
        foreach ($safe as $key => $type) {
            if (!is_array($old) || !is_array($new) || (!array_key_exists($key, $old) && !array_key_exists($key, $new))) continue;
            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;
            if (!$this->safeScalar($oldValue, $type) || !$this->safeScalar($newValue, $type)) continue;
            $result[$key] = ['old' => $oldValue, 'new' => $newValue];
        }
        return $result;
    }

    private function jsonObject(?string $json): ?array
    {
        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function safeScalar(mixed $value, string $type): bool
    {
        if ($value === null) return true;
        if (!is_int($value) && !is_float($value) && !(is_string($value) && strlen($value) <= 20 && is_numeric($value))) return false;
        if (strlen((string) $value) > 20) return false;
        return $type !== 'id' || (is_numeric($value) && (float) $value >= 0 && floor((float) $value) === (float) $value);
    }

    private function ledgerTables(): array
    {
        return array_values(array_filter(Schema::getTableListing(), fn ($table) => preg_match('/(stock|inventory).*(movement|transaction|ledger|history)|(movement|transaction|ledger|history).*(stock|inventory)/i', $table)));
    }

    private function schemaFailure(string $message): int
    {
        $this->error($message.'; no report generated.');
        return self::FAILURE;
    }

    private function positiveId(mixed $value): ?int
    {
        if (is_int($value)) return $value > 0 ? $value : null;
        if (!is_string($value) || $value === '' || strlen($value) > 19 || !ctype_digit($value)) return null;
        $integer = (int) $value;
        return $integer > 0 ? $integer : null;
    }

    private function optionalId(object $row, array $columns, array $candidates): ?int
    {
        foreach ($candidates as $column) if (in_array($column, $columns, true)) return $this->positiveId($row->{$column});
        return null;
    }

    private function optionalValue(object $row, array $candidates): mixed
    {
        foreach ($candidates as $column) if (property_exists($row, $column)) return $row->{$column};
        return null;
    }
}
