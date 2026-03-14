<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ComputerShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            ComputerShopSeeder::class,
        ]);
    }
}