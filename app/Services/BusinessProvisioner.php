<?php

namespace App\Services;

use Database\Seeders\AccountSeeder;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Support\Facades\DB;

/**
 * Gives a newly created business its own starter lists, now that units,
 * chart of accounts and expense categories belong to one business each:
 * common product units, a default chart of accounts and expense categories.
 * Safe to run more than once.
 */
class BusinessProvisioner
{
    public const DEFAULT_UNITS = ['Piece', 'Box', 'Pair', 'Set', 'Dozen', 'Kg', 'Litre'];

    public function provision(int $businessId): void
    {
        foreach (self::DEFAULT_UNITS as $name) {
            $exists = DB::table('product_units')->where('businessId', $businessId)->where('name', $name)->exists();
            if (!$exists) {
                DB::table('product_units')->insert([
                    'name' => $name, 'businessId' => $businessId, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        foreach ([AccountSeeder::class, ExpenseCategorySeeder::class] as $seederClass) {
            $seeder = app()->make($seederClass);
            $seeder->businessId = $businessId;
            app()->call([$seeder, 'run']);
        }
    }
}
