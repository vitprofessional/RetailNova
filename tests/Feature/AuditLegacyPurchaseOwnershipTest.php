<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLegacyPurchaseOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        foreach ([1, 2] as $id) {
            DB::table('business_setups')->insert([
                'id' => $id, 'businessName' => 'Synthetic Business '.$id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function test_json_reports_consistent_conflicting_ambiguous_and_shared_product_evidence_read_only(): void
    {
        $consistent = $this->relatedPurchase(1, 1, 1, true);
        $conflicting = $this->relatedPurchase(2, 1, 2, true);
        $ambiguous = $this->relatedPurchase(null, null, null, true);
        $sharedProduct = $this->product(null);
        $shared = $this->purchase($sharedProduct, null, null);
        $this->purchase($sharedProduct, null, 2);

        DB::table('product_serials')->insert([
            'purchaseId' => $consistent, 'productId' => 1, 'serialNumber' => 'PRIVATE-SERIAL-VALUE',
            'status' => 'available', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('customers')->insert([
            'name' => 'Private Customer Name', 'businessId' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $before = $this->snapshot();
        $report = $this->runJson(['--limit' => 100]);
        $after = $this->snapshot();

        $this->assertSame($before, $after, 'The audit command must not modify database records.');
        $byId = collect($report['records'])->keyBy('purchase_id');
        $this->assertSame('Consistent ownership evidence', $byId[$consistent]['classification']);
        $this->assertSame('Conflicting ownership evidence', $byId[$conflicting]['classification']);
        $this->assertSame('Incomplete or ambiguous ownership', $byId[$ambiguous]['classification']);
        $this->assertSame('Incomplete or ambiguous ownership', $byId[$shared]['classification']);
        $this->assertContains('product_used_by_multiple_purchase_rows', $byId[$shared]['issues']);
        $this->assertSame([$shared, $shared + 1], $byId[$shared]['product_purchase_ids']);
        $this->assertStringNotContainsString('PRIVATE-SERIAL-VALUE', json_encode($report));
        $this->assertStringNotContainsString('Private Customer Name', json_encode($report));
    }

    public function test_json_flags_missing_references_and_does_not_promote_incomplete_evidence(): void
    {
        $purchase = $this->purchase(9999, 8888, null);
        $report = $this->runJson();
        $row = collect($report['records'])->firstWhere('purchase_id', $purchase);

        $this->assertSame('Incomplete or ambiguous ownership', $row['classification']);
        $this->assertContains('missing_product', $row['issues']);
        $this->assertContains('missing_supplier', $row['issues']);
        $this->assertContains('no_invoice_header', $row['issues']);
        $this->assertContains('missing_stock_row', $row['issues']);
    }

    public function test_pagination_is_bounded_and_returns_a_cursor(): void
    {
        $this->purchase(null, null, null);
        $this->purchase(null, null, null);
        $this->purchase(null, null, null);

        $first = $this->runJson(['--limit' => 2]);
        $this->assertCount(2, $first['records']);
        $this->assertTrue($first['has_more']);
        $this->assertSame($first['records'][1]['purchase_id'], $first['next_after_id']);

        $second = $this->runJson(['--limit' => 2, '--after-id' => $first['next_after_id']]);
        $this->assertCount(1, $second['records']);
        $this->assertFalse($second['has_more']);
        $this->assertGreaterThan($first['next_after_id'], $second['records'][0]['purchase_id']);
    }

    public function test_limit_is_clamped_to_a_hundred_rows(): void
    {
        $report = $this->runJson(['--limit' => 1000]);
        $this->assertSame(100, $report['limit']);
    }

    private function runJson(array $options = []): array
    {
        Artisan::call('ownership:audit-legacy-purchases', array_merge(['--json' => true], $options));
        return json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function product(?int $businessId): int
    {
        return DB::table('products')->insertGetId([
            'name' => 'Synthetic product', 'businessId' => $businessId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function supplier(?int $businessId): int
    {
        return DB::table('suppliers')->insertGetId([
            'name' => 'Synthetic supplier', 'businessId' => $businessId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function purchase(?int $productId, ?int $supplierId, ?int $businessId, ?int $headerId = null): int
    {
        return DB::table('purchase_products')->insertGetId([
            'productName' => $productId, 'supplier' => $supplierId,
            'businessId' => $businessId, 'purchase_invoice_id' => $headerId,
            'qty' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function relatedPurchase(?int $headerBusinessId, ?int $supplierBusinessId, ?int $productBusinessId, bool $stock): int
    {
        $supplierId = $this->supplier($supplierBusinessId);
        $productId = $this->product($productBusinessId);
        $headerId = $headerBusinessId === null ? null : DB::table('purchase_invoices')->insertGetId([
            'businessId' => $headerBusinessId, 'supplier_id' => $supplierId,
            'purchase_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $purchaseId = $this->purchase($productId, $supplierId, null, $headerId);
        if ($stock) {
            DB::table('product_stocks')->insert([
                'purchaseId' => $purchaseId, 'productId' => $productId,
                'businessId' => $productBusinessId, 'currentStock' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return $purchaseId;
    }

    private function snapshot(): array
    {
        return collect([
            'business_setups', 'purchase_products', 'purchase_invoices', 'products', 'suppliers',
            'product_stocks', 'product_serials', 'invoice_items', 'sale_products', 'customers',
        ])->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()
            ->map(fn ($row) => (array) $row)->all()])->all();
    }
}
