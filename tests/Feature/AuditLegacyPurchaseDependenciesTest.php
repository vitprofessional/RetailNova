<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class AuditLegacyPurchaseDependenciesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_json_reports_returns_sale_items_and_sale_ownership_without_mutating_rows(): void
    {
        $purchase = $this->purchase(13, 1);
        DB::table('products')->where('id', 13)->update(['businessId' => 2]);
        $returnId = DB::table('purchase_returns')->insertGetId(['purchaseId' => $purchase]);
        $returnItemId = DB::table('return_purchase_items')->insertGetId([
            'returnId' => $returnId, 'purchaseId' => $purchase, 'supplierId' => 1, 'productId' => 13, 'qty' => 2,
        ]);
        $saleId = DB::table('sale_products')->insertGetId(['businessId' => 2]);
        $invoiceItemId = DB::table('invoice_items')->insertGetId([
            'saleId' => $saleId, 'purchaseId' => $purchase, 'qty' => 3, 'businessId' => 2,
        ]);
        DB::table('product_stocks')->insert(['purchaseId' => $purchase, 'productId' => 13, 'businessId' => null, 'currentStock' => 4]);

        $before = $this->snapshot();
        $report = $this->runJson();
        $after = $this->snapshot();

        $this->assertSame($before, $after);
        $row = collect($report['records'])->firstWhere('purchase_id', $purchase);
        $this->assertSame($returnId, $row['purchase_returns'][0]['id']);
        $this->assertSame($returnItemId, $row['return_items'][0]['id']);
        $this->assertSame(2, $row['return_items'][0]['qty']);
        $this->assertSame($invoiceItemId, $row['sale_invoice_items'][0]['id']);
        $this->assertSame($saleId, $row['sale_invoice_items'][0]['sale_id']);
        $this->assertSame(2, $row['sale_invoice_items'][0]['sale_business_id']);
        $this->assertSame(2, $row['sale_invoice_items'][0]['business_id']);
        $this->assertSame(2, $row['product_business_id']);
        $this->assertNotContains('invoice_item_sale_business_conflict', $row['issues']);
        $this->assertContains('purchase_business_unresolved', $row['issues']);
        $this->assertTrue($report['read_only']);
    }

    public function test_snake_case_foreign_keys_and_missing_sale_reference_are_reported(): void
    {
        $this->replaceReturnTablesWithSnakeCase();
        $purchase = $this->purchase(15, 4);
        $returnId = DB::table('purchase_returns')->insertGetId(['purchase_id' => $purchase]);
        $itemId = DB::table('return_purchase_items')->insertGetId([
            'return_id' => $returnId, 'purchase_id' => $purchase, 'product_id' => 15, 'qty' => 1,
        ]);
        $invoiceItemId = DB::table('invoice_items')->insertGetId(['saleId' => 9999, 'purchaseId' => $purchase, 'qty' => 1]);

        $report = $this->runJson();
        $row = collect($report['records'])->firstWhere('purchase_id', $purchase);
        $this->assertSame($returnId, $row['purchase_returns'][0]['id']);
        $this->assertSame($itemId, $row['return_items'][0]['id']);
        $this->assertSame($invoiceItemId, $row['sale_invoice_items'][0]['id']);
        $this->assertFalse($row['sale_invoice_items'][0]['sale_found']);
        $this->assertContains('invoice_item_sale_missing', $row['issues']);
    }

    public function test_relationship_rows_are_bounded_and_purchase_pagination_returns_cursor(): void
    {
        $first = $this->purchase(1, 1);
        $second = $this->purchase(2, 1);
        $third = $this->purchase(3, 1);
        DB::table('invoice_items')->insert([
            ['purchaseId' => $first, 'saleId' => null, 'qty' => 1],
            ['purchaseId' => $first, 'saleId' => null, 'qty' => 1],
        ]);

        $one = $this->runJson(['--limit' => 2, '--related-limit' => 1]);
        $this->assertCount(2, $one['records']);
        $this->assertTrue($one['has_more']);
        $this->assertSame($second, $one['next_after_id']);
        $firstRow = collect($one['records'])->firstWhere('purchase_id', $first);
        $this->assertCount(1, $firstRow['sale_invoice_items']);
        $this->assertContains('sale_invoice_items_truncated', $firstRow['issues']);

        $two = $this->runJson(['--limit' => 2, '--after-id' => $one['next_after_id']]);
        $this->assertSame([$third], collect($two['records'])->pluck('purchase_id')->all());
        $this->assertFalse($two['has_more']);
    }

    public function test_audit_history_includes_only_inventory_fields_and_excludes_personal_request_data(): void
    {
        $purchase = $this->purchase(21, 5);
        $stockId = DB::table('product_stocks')->insertGetId([
            'purchaseId' => $purchase, 'productId' => 21, 'businessId' => null, 'currentStock' => 2,
        ]);
        DB::table('audits')->insert([
            'event' => 'updated', 'auditable_type' => \App\Models\ProductStock::class,
            'auditable_id' => $stockId, 'old_values' => json_encode(['currentStock' => 4, 'businessId' => 2, 'qty' => ['nested' => 'secret'], 'supplier' => 'user@example.com']),
            'new_values' => json_encode(['currentStock' => 2, 'businessId' => 2, 'qty' => str_repeat('9', 5000), 'productName' => 'https://private.invalid credential=secret']),
            'url' => 'https://private.invalid/path', 'ip_address' => '192.0.2.1',
            'user_agent' => 'private agent data', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('audits')->insert([
            'event' => 'updated', 'auditable_type' => \App\Models\ProductStock::class,
            'auditable_id' => $stockId, 'old_values' => json_encode(['qty' => 5]), 'new_values' => json_encode(['qty' => 2]),
            'created_at' => now()->addSecond(), 'updated_at' => now()->addSecond(),
        ]);

        $report = $this->runJson();
        $json = json_encode($report);
        $row = collect($report['records'])->firstWhere('purchase_id', $purchase);
        $this->assertSame(4, $row['history']['rows'][0]['changed_fields']['currentStock']['old']);
        $this->assertSame(2, $row['history']['rows'][0]['changed_fields']['currentStock']['new']);
        $this->assertStringNotContainsString('private.invalid', $json);
        $this->assertStringNotContainsString('192.0.2.1', $json);
        $this->assertStringNotContainsString('private agent data', $json);
        $this->assertStringNotContainsString('user@example.com', $json);
        $this->assertStringNotContainsString('credential=secret', $json);
        $this->assertStringNotContainsString('nested', $json);
        $this->assertStringNotContainsString(str_repeat('9', 100), $json);
        $this->assertSame(2, $row['history']['rows'][0]['changed_fields']['businessId']['old']);
        $this->assertSame(5, $row['history']['rows'][1]['changed_fields']['qty']['old']);
        $this->assertSame(2, $row['history']['rows'][1]['changed_fields']['qty']['new']);
    }

    public function test_unexpected_foreign_key_schema_fails_closed(): void
    {
        Schema::drop('purchase_returns');
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_ref')->nullable();
        });

        $output = new BufferedOutput();
        $exit = app(\Illuminate\Contracts\Console\Kernel::class)->call('ownership:audit-legacy-dependencies', ['--json' => true], $output);
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('unsupported schema', $output->fetch());
    }

    public function test_invoice_ownership_reports_null_and_detects_conflicting_business_ids(): void
    {
        $purchase = $this->purchase(31, 1);
        $sale = DB::table('sale_products')->insertGetId(['businessId' => 7]);
        DB::table('invoice_items')->insert(['purchaseId' => $purchase, 'saleId' => $sale, 'qty' => 1, 'businessId' => 2]);
        DB::table('invoice_items')->insert(['purchaseId' => $purchase, 'saleId' => $sale, 'qty' => 1, 'businessId' => null]);

        $row = collect($this->runJson()['records'])->firstWhere('purchase_id', $purchase);
        $this->assertSame(2, $row['sale_invoice_items'][0]['business_id']);
        $this->assertNull($row['sale_invoice_items'][1]['business_id']);
        $this->assertContains('invoice_item_sale_business_conflict', $row['issues']);
    }

    public function test_primary_product_missing_null_and_stock_conflicting_ownership_are_reported(): void
    {
        $purchase = $this->purchase(41, 1);
        DB::table('products')->where('id', 41)->update(['businessId' => 2]);
        DB::table('product_stocks')->insert(['purchaseId' => $purchase, 'productId' => 41, 'businessId' => 9, 'currentStock' => 3]);
        $conflictRow = collect($this->runJson()['records'])->firstWhere('purchase_id', $purchase);
        $this->assertSame(2, $conflictRow['product_business_id']);
        $this->assertContains('purchase_product_business_conflict', $conflictRow['issues']);

        $nullOwner = $this->purchase(42, 1);
        DB::table('products')->where('id', 42)->update(['businessId' => null]);
        $nullOwnerRow = collect($this->runJson()['records'])->firstWhere('purchase_id', $nullOwner);
        $this->assertNull($nullOwnerRow['product_business_id']);
        $this->assertContains('purchase_product_business_unresolved', $nullOwnerRow['issues']);

        $missing = $this->purchase(9999, 1);
        DB::table('products')->where('id', 9999)->delete();
        $missingRow = collect($this->runJson()['records'])->firstWhere('purchase_id', $missing);
        $this->assertNull($missingRow['product_business_id']);
        $this->assertContains('purchase_product_missing', $missingRow['issues']);

        $emptyReference = DB::table('purchase_products')->insertGetId(['productName' => null, 'supplier' => '1', 'businessId' => null, 'qty' => 1]);
        $emptyRow = collect($this->runJson()['records'])->firstWhere('purchase_id', $emptyReference);
        $this->assertNull($emptyRow['product_id']);
        $this->assertContains('purchase_product_reference_missing', $emptyRow['issues']);
    }

    public function test_missing_optional_invoice_ownership_is_reported_as_null(): void
    {
        DB::statement('DROP INDEX invoice_items_businessid_index');
        Schema::table('invoice_items', fn (Blueprint $table) => $table->dropColumn('businessId'));
        $purchase = $this->purchase(51, 1);
        DB::table('invoice_items')->insert(['purchaseId' => $purchase, 'saleId' => null, 'qty' => 1]);
        $report = $this->runJson();
        $row = collect($report['records'])->firstWhere('purchase_id', $purchase);
        $this->assertNull($row['sale_invoice_items'][0]['business_id']);
        $this->assertNull($report['resolved_schema']['invoice_item_business']);
        $this->assertContains('invoice_item_sale_reference_missing', $row['issues']);
    }

    public function test_optional_schema_variants_are_rejected_when_ambiguous(): void
    {
        Schema::table('invoice_items', fn (Blueprint $table) => $table->unsignedBigInteger('business_id')->nullable());
        $output = new BufferedOutput();
        $exit = app(\Illuminate\Contracts\Console\Kernel::class)->call('ownership:audit-legacy-dependencies', ['--json' => true], $output);
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('ambiguous schema', $output->fetch());
    }

    public function test_global_related_row_budget_marks_report_incomplete(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $purchase = $this->purchase(100 + $i, 1);
            $rows = [];
            for ($j = 0; $j < 50; $j++) $rows[] = ['purchaseId' => $purchase, 'saleId' => null, 'qty' => 1];
            DB::table('invoice_items')->insert($rows);
        }
        DB::table('product_stocks')->insert(['purchaseId' => 10, 'productId' => 109, 'businessId' => 2, 'currentStock' => 1]);
        $report = $this->runJson(['--limit' => 10]);
        $this->assertLessThanOrEqual(500, $report['related_records_used']);
        $this->assertFalse($report['complete']);
        $this->assertContains('total_related_record_budget_exhausted', $report['incomplete_categories']);
        $this->assertContains('total_related_record_budget_exhausted', collect($report['records'])->last()['issues']);
    }

    private function runJson(array $options = []): array
    {
        $output = new BufferedOutput();
        $exit = app(\Illuminate\Contracts\Console\Kernel::class)->call('ownership:audit-legacy-dependencies', array_merge(['--json' => true], $options), $output);
        $json = $output->fetch();
        $this->assertSame(0, $exit, $json);
        $decoded = json_decode($json, true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Invalid command JSON: '.substr($json, 0, 1000));
        return $decoded;
    }

    private function purchase(int $productId, int $supplierId): int
    {
        if (!DB::table('products')->where('id', $productId)->exists()) DB::table('products')->insert(['id' => $productId, 'businessId' => 2]);
        return DB::table('purchase_products')->insertGetId([
            'productName' => $productId, 'supplier' => $supplierId, 'businessId' => null,
        ]);
    }

    private function replaceReturnTablesWithSnakeCase(): void
    {
        Schema::drop('return_purchase_items');
        Schema::create('return_purchase_items', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('return_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable(); $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('qty')->default(0);
        });
        Schema::drop('purchase_returns');
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('purchase_id')->nullable();
        });
    }

    private function snapshot(): array
    {
        return collect(['purchase_products', 'purchase_returns', 'return_purchase_items', 'invoice_items', 'sale_products', 'product_stocks', 'products', 'suppliers', 'audits'])
            ->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all()])->all();
    }
}
