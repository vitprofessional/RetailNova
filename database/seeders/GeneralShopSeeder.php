<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\BusinessTypeSeedable;
use Illuminate\Database\Seeder;

/**
 * General shop demo seeder.
 *
 * Reuses Mobile Shop demo data for broad day-to-day retail inventory,
 * purchase, and sales flows while keeping this seeder idempotent.
 */
class GeneralShopSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $seeder = app()->make(MobileShopSeeder::class);
        $seeder->businessId = $this->businessId;
        app()->call([$seeder, 'run']);
    }
}
