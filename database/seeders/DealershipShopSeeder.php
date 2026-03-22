<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\BusinessTypeSeedable;
use Illuminate\Database\Seeder;

/**
 * Dealership demo seeder.
 *
 * Reuses Vehicle Shop dataset so dealership users get complete
 * inventory/purchase/sales demo flows without duplicate maintenance.
 */
class DealershipShopSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $seeder = app()->make(VehicleShopSeeder::class);
        $seeder->businessId = $this->businessId;
        app()->call([$seeder, 'run']);
    }
}
