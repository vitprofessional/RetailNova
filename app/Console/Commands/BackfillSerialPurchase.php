<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\ProductSerial;
use App\Models\PurchaseProduct;

class BackfillSerialPurchase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serials:backfill-purchase {--product-id=} {--limit=0} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill product_serials.purchaseId by inferring from latest purchase for the same product';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! Schema::hasColumn('product_serials', 'purchaseId')) {
            $this->error('product_serials.purchaseId column is missing. Run migrations first.');
            return 1;
        }

        $productId = $this->option('product-id');
        $limit = (int) $this->option('limit');
        $dry = (bool) $this->option('dry-run');

        $q = ProductSerial::query()
            ->whereNull('purchaseId')
            ->whereNotNull('productId');

        if ($productId) {
            $q->where('productId', $productId);
        }

        $count = (clone $q)->count();
        if ($count === 0) {
            $this->info('No serials need backfill.');
            return 0;
        }

        $this->info("Found {$count} serial(s) without purchaseId.");
        $processed = 0; $updated = 0; $skipped = 0;

        $q->orderBy('id')->chunk(500, function($chunk) use (&$processed, &$updated, &$skipped, $limit, $dry) {
            foreach ($chunk as $serial) {
                if ($limit && $processed >= $limit) return false; // break chunking

                $processed++;
                // Find latest purchase for this product
                $purchase = PurchaseProduct::where('productName', $serial->productId)
                    ->orderBy('id', 'desc')
                    ->first();

                if (! $purchase) {
                    $this->line("- Serial #{$serial->id} ({$serial->serialNumber}) product {$serial->productId}: no purchase found -> skip");
                    $skipped++;
                    continue;
                }

                if ($dry) {
                    $this->line("~ Would set serial #{$serial->id} purchaseId => {$purchase->id}");
                    $updated++;
                    continue;
                }

                $serial->purchaseId = $purchase->id;
                $serial->save();
                $this->line("+ Set serial #{$serial->id} purchaseId => {$purchase->id}");
                $updated++;
            }
        });

        $this->info("Processed: {$processed}, Updated: {$updated}, Skipped: {$skipped}");
        if ($dry) $this->info('Dry-run completed. Re-run without --dry-run to apply changes.');
        return 0;
    }
}
