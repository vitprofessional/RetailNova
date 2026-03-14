<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PharmacyShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            PharmacyShopSeeder::class,
        ]);
    }
}