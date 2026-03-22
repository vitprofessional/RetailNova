<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DellershipShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            DealershipShopSeeder::class,
        ]);
    }
}
