<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StationeryShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            StationaryShopSeeder::class,
        ]);
    }
}
