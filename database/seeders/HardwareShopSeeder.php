<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\BusinessTypeSeedable;
use Illuminate\Database\Seeder;

/**
 * Hardware shop demo seeder.
 *
 * Reuses Electronics Parts dataset because it already represents
 * inventory-heavy parts/tools operations with idempotent demo records.
 */
class HardwareShopSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $seeder = app()->make(ElectronicsPartsSeeder::class);
        $seeder->businessId = $this->businessId;
        app()->call([$seeder, 'run']);
    }
}
