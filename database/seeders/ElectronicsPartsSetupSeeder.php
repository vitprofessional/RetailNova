<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ElectronicsPartsSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            ElectronicsPartsSeeder::class,
        ]);
    }
}