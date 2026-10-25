<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JsonException;
use Throwable;

class VerifyUpgrade extends Command
{
    protected $signature = 'retailnova:verify-upgrade
        {action : snapshot or compare}
        {before? : Before-upgrade snapshot filename, for compare}
        {after? : After-upgrade snapshot filename, for compare}
        {--output= : Snapshot filename (inside storage/app/upgrade-checks)}';

    protected $description = 'Snapshot and compare business totals before and after an upgrade.';

    public function handle(): int
    {
        return match (strtolower((string) $this->argument('action'))) {
            'snapshot' => $this->snapshot(),
            'compare' => $this->compare(),
            default => $this->invalid('Action must be snapshot or compare.'),
        };
    }

    private function snapshot(): int
    {
        $filename = (string) ($this->option('output') ?: 'snapshot-'.now()->format('Ymd-His').'.json');
        $path = $this->snapshotPath($filename);
        if ($path === null) {
            return $this->invalid('Use a simple .json filename; snapshots are stored only under storage/app/upgrade-checks.');
        }

        try {
            $snapshot = [
                'format_version' => 1,
                'generated_at' => now()->toIso8601String(),
                'businesses' => $this->businessSnapshots(),
            ];
            $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
            $directory = dirname($path);
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                return $this->invalid('Cannot create the snapshot directory.');
            }
            $temporary = $path.'.'.getmypid().'.tmp';
            if (file_put_contents($temporary, $json, LOCK_EX) === false || !rename($temporary, $path)) {
                @unlink($temporary);
                return $this->invalid('Cannot write the snapshot file.');
            }
        } catch (Throwable $exception) {
            return $this->invalid('Snapshot failed: '.$exception::class.' (no credentials or record data were printed).');
        }

        $this->info('Snapshot saved: '.$path);
        $this->line('Businesses included: '.count($snapshot['businesses']));

        return self::SUCCESS;
    }

    private function compare(): int
    {
        $beforeName = (string) $this->argument('before');
        $afterName = (string) $this->argument('after');
        $beforePath = $this->snapshotPath($beforeName);
        $afterPath = $this->snapshotPath($afterName);
        if ($beforePath === null || $afterPath === null || !is_file($beforePath) || !is_file($afterPath)) {
            return $this->invalid('Provide two existing .json filenames inside storage/app/upgrade-checks.');
        }

        try {
            $before = json_decode((string) file_get_contents($beforePath), true, 512, JSON_THROW_ON_ERROR);
            $after = json_decode((string) file_get_contents($afterPath), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($before['businesses'] ?? null) || !is_array($after['businesses'] ?? null)) {
                return $this->invalid('Snapshot format is missing a businesses object.');
            }
        } catch (JsonException) {
            return $this->invalid('A snapshot is not valid JSON.');
        }

        $differences = $this->diffSnapshots($before['businesses'], $after['businesses']);
        if ($differences === []) {
            $this->info('No differences found across business totals and record counts.');
            return self::SUCCESS;
        }

        $this->warn('Upgrade differences found; review them before accepting the upgrade:');
        foreach ($differences as $difference) {
            $this->line('• '.$difference);
        }

        return self::FAILURE;
    }

    /** @return array<string, array<string, mixed>> */
    private function businessSnapshots(): array
    {
        $result = [];
        $tables = Schema::getTableListing(null, false);
        sort($tables);

        foreach (DB::table('business_setups')->orderBy('id')->get(['id', 'businessName']) as $business) {
            $id = (string) $business->id;
            $counts = ['business_setups' => 1];

            foreach ($tables as $table) {
                if ($table === 'business_setups' || !Schema::hasColumn($table, 'businessId')) {
                    continue;
                }
                $counts[$table] = (int) DB::table($table)->where('businessId', $business->id)->count();
            }
            ksort($counts);

            $sales = DB::table('sale_products')->where('businessId', $business->id);
            if (Schema::hasColumn('sale_products', 'deleted_at')) {
                $sales->whereNull('deleted_at');
            }
            $salesColumn = Schema::hasColumn('sale_products', 'grandTotal') ? 'grandTotal'
                : (Schema::hasColumn('sale_products', 'totalAmount') ? 'totalAmount'
                    : (Schema::hasColumn('sale_products', 'totalSale') ? 'totalSale' : null));
            $salesTotal = $salesColumn ? (float) (clone $sales)->sum($salesColumn) : 0.0;
            $dueTotal = 0.0;
            if ($salesColumn !== null && Schema::hasColumn('sale_products', 'paidAmount')) {
                $dueTotal = (float) (clone $sales)->whereRaw(
                    'COALESCE('.$salesColumn.', 0) > COALESCE(paidAmount, 0)'
                )->selectRaw(
                    'COALESCE(SUM(COALESCE('.$salesColumn.', 0) - COALESCE(paidAmount, 0)), 0) AS aggregate'
                )->value('aggregate');
            }
            $openingBalance = Schema::hasTable('customers') && Schema::hasColumn('customers', 'openingBalance')
                ? (float) DB::table('customers')->where('businessId', $business->id)->sum('openingBalance') : 0.0;
            $stockQuantity = Schema::hasTable('product_stocks') && Schema::hasColumn('product_stocks', 'currentStock')
                ? (float) DB::table('product_stocks')->where('businessId', $business->id)->sum('currentStock') : 0.0;

            $result[$id] = [
                'business_name' => (string) $business->businessName,
                'totals' => [
                    'sales_total' => $this->decimal($salesTotal),
                    'customer_due' => $this->decimal($openingBalance + $dueTotal),
                    'customer_opening_balance' => $this->decimal($openingBalance),
                    'unpaid_sales_due' => $this->decimal($dueTotal),
                    'stock_quantity' => $this->decimal($stockQuantity),
                ],
                'record_counts' => $counts,
            ];
        }

        return $result;
    }

    /** @param array<string, array<string, mixed>> $before @param array<string, array<string, mixed>> $after @return list<string> */
    private function diffSnapshots(array $before, array $after): array
    {
        $differences = [];
        $businessIds = array_unique(array_merge(array_keys($before), array_keys($after)));
        sort($businessIds, SORT_NATURAL);

        foreach ($businessIds as $id) {
            if (!isset($before[$id])) {
                $differences[] = "business {$id}: added";
                continue;
            }
            if (!isset($after[$id])) {
                $differences[] = "business {$id}: missing after upgrade";
                continue;
            }
            foreach (['business_name' => 'business_name'] as $key => $label) {
                if (($before[$id][$key] ?? null) !== ($after[$id][$key] ?? null)) {
                    $differences[] = "business {$id} {$label}: ".json_encode($before[$id][$key] ?? null).' → '.json_encode($after[$id][$key] ?? null);
                }
            }
            foreach (['totals', 'record_counts'] as $section) {
                $keys = array_unique(array_merge(
                    array_keys($before[$id][$section] ?? []),
                    array_keys($after[$id][$section] ?? [])
                ));
                sort($keys);
                foreach ($keys as $key) {
                    $old = $before[$id][$section][$key] ?? null;
                    $new = $after[$id][$section][$key] ?? null;
                    if ($old !== $new) {
                        $differences[] = "business {$id} {$section}.{$key}: ".json_encode($old).' → '.json_encode($new);
                    }
                }
            }
        }

        return $differences;
    }

    private function decimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function snapshotPath(string $filename): ?string
    {
        if (!preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.json\z/', $filename)) {
            return null;
        }

        return storage_path('app/upgrade-checks/'.$filename);
    }

    private function invalid(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }
}
