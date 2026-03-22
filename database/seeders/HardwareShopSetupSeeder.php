<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HardwareShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            HardwareShopSeeder::class,
        ]);
    }
}
