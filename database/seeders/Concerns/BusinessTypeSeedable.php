<?php

namespace Database\Seeders\Concerns;

/**
 * Allows a demo seeder to target a specific business ID rather than the
 * hard-coded default of 1.  Usage:
 *
 *   $seeder = app(MobileShopSeeder::class);
 *   $seeder->businessId = $targetId;
 *   app()->call([$seeder, 'run']);
 */
trait BusinessTypeSeedable
{
    public int $businessId = 1;
}
