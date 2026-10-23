<?php

namespace App\Support;

use Database\Seeders\ComputerShopSeeder;
use Database\Seeders\DealershipShopSeeder;
use Database\Seeders\DellershipShopSeeder;
use Database\Seeders\ElectronicsPartsSeeder;
use Database\Seeders\GarmentsShopSeeder;
use Database\Seeders\GeneralShopSeeder;
use Database\Seeders\HardwareShopSeeder;
use Database\Seeders\MobileShopSeeder;
use Database\Seeders\PharmacyShopSeeder;
use Database\Seeders\StationaryShopSeeder;
use Database\Seeders\StationeryShopSeeder;
use Database\Seeders\ToyShopSeeder;
use Database\Seeders\VehicleShopSeeder;

/** Single source of truth for business type values, their labels, and their demo seeders. */
final class BusinessTypes
{
    /** Labels live in lang/{locale}/business_types.php under the type value. */
    private const LABEL_GROUP = 'business_types';

    /** @var array<string, class-string> Display order matters: it is the select order. */
    private const SEEDERS = [
        'mobile_shop' => MobileShopSeeder::class,
        'vehicle_shop' => VehicleShopSeeder::class,
        'computer_shop' => ComputerShopSeeder::class,
        'electronics_parts_shop' => ElectronicsPartsSeeder::class,
        'hardware_shop' => HardwareShopSeeder::class,
        'dealership_shop' => DealershipShopSeeder::class,
        // Legacy alias for a historical typo; kept so saved businesses stay valid.
        'dellership_shop' => DellershipShopSeeder::class,
        'general_shop' => GeneralShopSeeder::class,
        'stationary_shop' => StationaryShopSeeder::class,
        // Spelling-variant alias of stationary_shop; kept so saved businesses stay valid.
        'stationery_shop' => StationeryShopSeeder::class,
        'garments_shop' => GarmentsShopSeeder::class,
        'pharmacy_shop' => PharmacyShopSeeder::class,
        'toy_shop' => ToyShopSeeder::class,
    ];

    /** @return list<string> */
    public static function values(): array
    {
        return array_keys(self::SEEDERS);
    }

    public static function labelKey(string $value): string
    {
        return self::LABEL_GROUP . '.' . $value;
    }

    public static function label(string $value): string
    {
        return __(self::labelKey($value));
    }

    /** @return array<string, string> Type value => translated label. */
    public static function options(): array
    {
        $options = [];
        foreach (self::values() as $value) {
            $options[$value] = self::label($value);
        }

        return $options;
    }

    /** @return class-string|null */
    public static function seeder(string $value): ?string
    {
        return self::SEEDERS[$value] ?? null;
    }
}
