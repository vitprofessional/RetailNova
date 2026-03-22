<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DealershipShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            DealershipShopSeeder::class,
        ]);
    }
}
