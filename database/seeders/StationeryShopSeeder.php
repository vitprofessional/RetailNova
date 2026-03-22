<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\BusinessTypeSeedable;
use Illuminate\Database\Seeder;

/**
 * Backward-compatible alias for alternate spelling: "stationery".
 */
class StationeryShopSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $seeder = app()->make(StationaryShopSeeder::class);
        $seeder->businessId = $this->businessId;
        app()->call([$seeder, 'run']);
    }
}
