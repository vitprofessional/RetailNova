<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScanDuplicates extends Command
{
    protected $signature = 'app:scan-duplicates {--include-deleted : Include soft-deleted customers in the scan} {--csv= : Output results to CSV (optional path)}';

    protected $description = 'Scan for duplicate emails/mobiles in customers and suppliers';

    public function handle(): int
    {
        $includeDeleted = (bool)$this->option('include-deleted');

        $this->info('Scanning suppliers for duplicate email/mobile...');
        $supplierMailDup = $this->findDuplicates('suppliers', 'mail');
        $supplierMobileDup = $this->findDuplicates('suppliers', 'mobile');

        $this->renderDupTable('Suppliers (mail)', $supplierMailDup, 'mail');
        $this->renderDupTable('Suppliers (mobile)', $supplierMobileDup, 'mobile');

        $this->newLine();
        $this->info('Scanning customers for duplicate email/mobile...');
        $customerMailDup = $this->findDuplicates('customers', 'mail', $includeDeleted ? null : 'deleted_at');
        $customerMobileDup = $this->findDuplicates('customers', 'mobile', $includeDeleted ? null : 'deleted_at');

        $this->renderDupTable('Customers (mail)'.($includeDeleted?' [including deleted]':''), $customerMailDup, 'mail');
        $this->renderDupTable('Customers (mobile)'.($includeDeleted?' [including deleted]':''), $customerMobileDup, 'mobile');

        $anyDup = collect([$supplierMailDup,$supplierMobileDup,$customerMailDup,$customerMobileDup])
            ->flatten(1)->count() > 0;
        if(!$anyDup){
            $this->info('No duplicates found.');
        } else {
            $this->warn('Duplicates detected. Please review the tables above.');
        }

        // Optional CSV export
        $csvPath = $this->option('csv');
        if ($csvPath !== null) {
            $path = trim((string)$csvPath);
            if ($path === '') {
                $path = storage_path('app/duplicate_scan_'.date('Ymd_His').'.csv');
            } elseif (!preg_match('/^([a-zA-Z]:\\\\|\\\\|\/)/', $path)) {
                // If not absolute, treat as relative to storage/app
                $path = storage_path('app/'.ltrim($path, '\\/'));
            }
            try {
                $fh = fopen($path, 'w');
                if ($fh === false) {
                    throw new \RuntimeException('Unable to open file for writing');
                }
                // Header
                fputcsv($fh, ['section','value','count','ids']);
                $this->writeCsvSection($fh, 'Suppliers (mail)', $supplierMailDup);
                $this->writeCsvSection($fh, 'Suppliers (mobile)', $supplierMobileDup);
                $this->writeCsvSection($fh, 'Customers (mail)'.($includeDeleted?' [including deleted]':''), $customerMailDup);
                $this->writeCsvSection($fh, 'Customers (mobile)'.($includeDeleted?' [including deleted]':''), $customerMobileDup);
                fclose($fh);
                $this->info('CSV written: '.$path);
            } catch (\Throwable $e) {
                $this->error('Failed to write CSV: '.$e->getMessage());
                return self::FAILURE;
            }
        }
        return self::SUCCESS;
    }

    /**
     * @param string $table
     * @param string $column
     * @param string|null $nullIfColumn If provided, rows with NOT NULL in this column will be included only when NULL (e.g., exclude soft-deleted when using 'deleted_at')
     * @return array<int, array{value:string,count:int,ids:string}>
     */
    protected function findDuplicates(string $table, string $column, ?string $nullIfColumn = null): array
    {
        if (!DB::getSchemaBuilder()->hasTable($table) || !DB::getSchemaBuilder()->hasColumn($table, $column)) {
            return [];
        }

        $query = DB::table($table)
            ->selectRaw($column.' as value, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids')
            ->whereNotNull($column);

        if ($nullIfColumn) {
            if (DB::getSchemaBuilder()->hasColumn($table, $nullIfColumn)) {
                $query->whereNull($nullIfColumn);
            }
        }

        return $query
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('count','desc')
            ->get()
            ->map(function($row){
                return [
                    'value' => (string)$row->value,
                    'count' => (int)$row->count,
                    'ids'   => (string)$row->ids,
                ];
            })
            ->toArray();
    }

    protected function renderDupTable(string $title, array $rows, string $columnLabel): void
    {
        $this->newLine();
        $this->line('<info>'.$title.'</info>');
        if (empty($rows)) {
            $this->line('  (none)');
            return;
        }
        $this->table([
            ucfirst($columnLabel), 'Count', 'IDs'
        ], array_map(function($r){
            return [$r['value'], $r['count'], $r['ids']];
        }, $rows));
    }

    protected function writeCsvSection($fh, string $section, array $rows): void
    {
        if (empty($rows)) {
            // still write a placeholder row indicating none
            fputcsv($fh, [$section, '(none)', 0, '']);
            return;
        }
        foreach ($rows as $r) {
            fputcsv($fh, [$section, $r['value'], $r['count'], $r['ids']]);
        }
    }
}
