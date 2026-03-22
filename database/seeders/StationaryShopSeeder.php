<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\BusinessTypeSeedable;
use Illuminate\Database\Seeder;

/**
 * Stationary shop demo seeder.
 *
 * Reuses Computer Shop data to provide office-leaning product and
 * transaction demos without duplicating a large dataset.
 */
class StationaryShopSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $seeder = app()->make(ComputerShopSeeder::class);
        $seeder->businessId = $this->businessId;
        app()->call([$seeder, 'run']);
    }
}
