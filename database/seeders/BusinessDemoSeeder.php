<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orchestrates all business-type demo seeders.
 *
 * Each sub-seeder is fully idempotent and safe to re-run.
 *
 * Business types covered:
 *   1. Mobile Shop
 *   2. Vehicle / Auto Parts Shop
 *   3. Computer / PC Components Shop
 *   4. Electronics Parts Shop
 *   5. Hardware Shop
 *   6. Dealership Shop
 *   7. General Shop
 *   8. Stationary Shop
 *   9. Garments / Clothing & Fabric Shop
 *  10. Pharmacy / Medicine Shop
 *  11. Toy Shop
 */
class BusinessDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreBusinessSetupSeeder::class,
            MobileShopSeeder::class,
            VehicleShopSeeder::class,
            ComputerShopSeeder::class,
            ElectronicsPartsSeeder::class,
            HardwareShopSeeder::class,
            DealershipShopSeeder::class,
            GeneralShopSeeder::class,
            StationaryShopSeeder::class,
            GarmentsShopSeeder::class,
            PharmacyShopSeeder::class,
            ToyShopSeeder::class,
        ]);
    }
}
