<?php

namespace App\Services\MobileLists;

/** The mobile list pages, by the key the JSON endpoint uses. */
class MobileLists
{
    private const LISTS = [
        'sales' => SalesList::class,
        'products' => ProductsList::class,
        'customers' => CustomersList::class,
        'suppliers' => SuppliersList::class,
        'purchases' => PurchasesList::class,
        'purchase-lines' => PurchaseLinesList::class,
        'expenses' => ExpensesList::class,
    ];

    public function get(string $key): MobileList
    {
        abort_unless(isset(self::LISTS[$key]), 404);

        return app(self::LISTS[$key]);
    }
}
