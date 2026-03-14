<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orchestrates all business-type demo seeders.
 *
 * Each sub-seeder is fully idempotent – safe to re-run.
 *
 * Business types covered:
 *   1. Mobile Shop
 *   2. Vehicle / Auto Parts Shop
 *   3. Computer / PC Components Shop
 *   4. Electronics Parts Shop
 *   5. Garments / Clothing & Fabric Shop
 *   6. Pharmacy / Medicine Shop
 */
class BusinessDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MobileShopSeeder::class,
            VehicleShopSeeder::class,
            ComputerShopSeeder::class,
            ElectronicsPartsSeeder::class,
            GarmentsShopSeeder::class,
            PharmacyShopSeeder::class,
        ]);
    }
}
