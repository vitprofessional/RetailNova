<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ToyShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            ToyShopSeeder::class,
        ]);
    }
}
