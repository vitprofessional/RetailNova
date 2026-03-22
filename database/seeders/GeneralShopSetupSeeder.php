<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GeneralShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            GeneralShopSeeder::class,
        ]);
    }
}
