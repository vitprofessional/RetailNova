<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateDemoInvoice extends Command
{
    protected $signature = 'demo:invoice';
    protected $description = 'Generate a demo sale + invoice to verify Code (barcode) and Unit display';

    public function handle(): int
    {
        DB::beginTransaction();
        try {
            $unitId = DB::table('product_units')->insertGetId([
                'name' => 'UnitX',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $productId = DB::table('products')->insertGetId([
                'name' => 'Demo Item',
                'unitName' => $unitId,
                'barCode' => 'SKU-DEMO-001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $purchaseId = DB::table('purchase_products')->insertGetId([
                'productName' => $productId,
                'qty' => 2,
                'buyPrice' => 100,
                'salePriceExVat' => 150,
                'purchase_date' => now()->toDateString(),
                'invoice' => 'PINV-DEMO',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $saleId = DB::table('sale_products')->insertGetId([
                'date' => now()->toDateString(),
                'invoice' => 'SINV-DEMO',
                'grandTotal' => 300,
                'paidAmount' => 100,
                'curDue' => 200,
                'status' => 'Ordered',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('invoice_items')->insert([
                'saleId' => $saleId,
                'purchaseId' => $purchaseId,
                'qty' => 2,
                'salePrice' => 150,
                'buyPrice' => 100,
                'warranty_days' => '365',
                'totalSale' => 300,
                'totalPurchase' => 200,
                'profitTotal' => 100,
                'profitMargin' => '33.33',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            $url = url()->to('/generate/invoice/'.$saleId);
            $this->info('Demo sale created.');
            $this->line('Invoice URL: '.$url);
            $this->line('Expected Code: SKU-DEMO-001');
            $this->line('Expected Unit: UnitX');
            $this->warn('Note: Route requires admin login (SuperAdmin).');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
