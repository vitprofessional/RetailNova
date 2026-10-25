<?php

namespace Tests\Feature;

use App\Models\BusinessSetup;
use App\Models\Customer;
use App\Models\ProductStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeploymentCommandsTest extends TestCase
{
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    protected function refreshDatabase(): void
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Deployment command tests require testing + SQLite :memory:.');
        }

        $this->refreshIsolatedDatabase();
    }

    public function test_verify_upgrade_writes_business_scoped_totals_and_detects_changes(): void
    {
        BusinessSetup::forceCreate(['id' => 71, 'businessName' => 'Upgrade Test']);
        Customer::withoutGlobalScopes()->forceCreate([
            'id' => 711, 'name' => 'Test Customer', 'businessId' => 71, 'openingBalance' => 25,
        ]);
        \Illuminate\Support\Facades\DB::table('sale_products')->insert([
            'customerId' => 711, 'businessId' => 71, 'totalSale' => '300.00', 'grandTotal' => '295.00',
            'paidAmount' => '100.00', 'invoice' => 'TEST-71', 'created_at' => now(), 'updated_at' => now(),
        ]);
        ProductStock::withoutGlobalScopes()->forceCreate([
            'businessId' => 71, 'productId' => 1, 'purchaseId' => 1, 'currentStock' => 8,
        ]);

        $before = storage_path('app/upgrade-checks/test-before.json');
        $after = storage_path('app/upgrade-checks/test-after.json');
        @unlink($before);
        @unlink($after);

        $this->assertSame(0, Artisan::call('retailnova:verify-upgrade', [
            'action' => 'snapshot', '--output' => 'test-before.json',
        ]));
        $first = json_decode((string) file_get_contents($before), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('295.00', $first['businesses']['71']['totals']['sales_total']);
        $this->assertSame('220.00', $first['businesses']['71']['totals']['customer_due']);
        $this->assertSame('8.00', $first['businesses']['71']['totals']['stock_quantity']);

        \Illuminate\Support\Facades\DB::table('sale_products')->where('invoice', 'TEST-71')->update(['paidAmount' => '150.00']);
        ProductStock::withoutGlobalScopes()->where('businessId', 71)->update(['currentStock' => 6]);
        $this->assertSame(0, Artisan::call('retailnova:verify-upgrade', [
            'action' => 'snapshot', '--output' => 'test-after.json',
        ]));
        $this->assertSame(1, Artisan::call('retailnova:verify-upgrade', [
            'action' => 'compare', 'before' => 'test-before.json', 'after' => 'test-after.json',
        ]));

        $output = Artisan::output();
        $this->assertStringContainsString('totals.customer_due', $output);
        $this->assertStringContainsString('totals.stock_quantity', $output);
    }

    public function test_verify_upgrade_rejects_paths_outside_snapshot_directory(): void
    {
        $this->assertSame(1, Artisan::call('retailnova:verify-upgrade', [
            'action' => 'snapshot', '--output' => '../outside.json',
        ]));
    }

    public function test_preflight_reports_all_checks_and_fails_closed_for_local_nonproduction_settings(): void
    {
        config(['app.debug' => true, 'app.key' => '', 'app.url' => 'http://localhost']);
        $this->assertSame(1, Artisan::call('retailnova:preflight'));
        $output = Artisan::output();

        foreach (['PHP >= 8.2', 'PHP extension pdo_mysql', 'APP_DEBUG is disabled',
            'APP_KEY is configured', 'APP_URL uses HTTPS', 'Pending migrations', 'Scheduler last ran'] as $check) {
            $this->assertStringContainsString($check, $output);
        }
    }
}
