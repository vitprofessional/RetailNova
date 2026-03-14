<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GarmentsShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            GarmentsShopSeeder::class,
        ]);
    }
}