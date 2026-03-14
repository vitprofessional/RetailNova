<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MobileShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            MobileShopSeeder::class,
        ]);
    }
}