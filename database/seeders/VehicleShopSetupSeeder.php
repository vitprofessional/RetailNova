<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VehicleShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            VehicleShopSeeder::class,
        ]);
    }
}