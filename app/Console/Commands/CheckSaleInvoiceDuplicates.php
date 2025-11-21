<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckSaleInvoiceDuplicates extends Command
{
    protected $signature = 'sale:invoice:duplicates {--csv= : Optional CSV output path}';

    protected $description = 'Report duplicate sale invoice numbers and blank invoices.';

    public function handle(): int
    {
        if (!DB::getSchemaBuilder()->hasTable('sale_products')) {
            $this->error('Table sale_products not found.');
            return self::FAILURE;
        }
        if (!DB::getSchemaBuilder()->hasColumn('sale_products','invoice')) {
            $this->error('Column invoice not found on sale_products.');
            return self::FAILURE;
        }

        $this->info('Scanning sale_products.invoice for duplicates...');

        $duplicates = DB::table('sale_products')
            ->selectRaw('invoice, COUNT(*) as cnt, GROUP_CONCAT(id ORDER BY id) as ids')
            ->whereNotNull('invoice')
            ->where('invoice','<>','')
            ->groupBy('invoice')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('cnt')
            ->get();

        $blanks = DB::table('sale_products')
            ->selectRaw('COUNT(*) as cnt')
            ->where(function($q){
                $q->whereNull('invoice')->orWhere('invoice','');
            })
            ->first();

        $this->newLine();
        $this->line('<info>Duplicate Invoices</info>');
        if ($duplicates->isEmpty()) {
            $this->line('  (none)');
        } else {
            $this->table(['Invoice','Count','IDs'], $duplicates->map(fn($r)=>[$r->invoice,$r->cnt,$r->ids])->toArray());
        }

        $this->newLine();
        $this->line('<info>Blank / NULL Invoices</info>');
        $this->line('  Total blank/null rows: '.($blanks? $blanks->cnt : 0));

        $csvPath = $this->option('csv');
        if ($csvPath !== null) {
            $path = trim((string)$csvPath);
            if ($path === '') {
                $path = storage_path('app/sale_invoice_duplicates_'.date('Ymd_His').'.csv');
            } elseif (!preg_match('/^([a-zA-Z]:\\\\|\\\\|\/)/', $path)) {
                $path = storage_path('app/'.ltrim($path,'\\/'));
            }
            try {
                $fh = fopen($path,'w');
                if ($fh === false) throw new \RuntimeException('Cannot open file for writing');
                fputcsv($fh,['type','invoice','count','ids']);
                if ($duplicates->isEmpty()) {
                    fputcsv($fh,['duplicate','(none)',0,'']);
                } else {
                    foreach($duplicates as $row){
                        fputcsv($fh,['duplicate',$row->invoice,$row->cnt,$row->ids]);
                    }
                }
                fputcsv($fh,['blank_total','', ($blanks? $blanks->cnt : 0), '']);
                fclose($fh);
                $this->info('CSV written: '.$path);
            } catch (\Throwable $e) {
                $this->error('Failed CSV write: '.$e->getMessage());
                return self::FAILURE;
            }
        }

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate invoices found.');
        } else {
            $this->warn('Duplicate invoices detected. Consider remediation before adding unique index.');
        }

        return self::SUCCESS;
    }
}
