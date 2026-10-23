<?php

namespace App\Services;

use App\Models\DamageProduct;
use App\Models\Product;
use App\Models\PurchaseProduct;
use App\Models\ReturnPurchaseItem;
use App\Models\ReturnSaleItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Tells which catalog records are still referenced and therefore must not be deleted.
 *
 * - A product is in use when a purchase, sale return, purchase return or damage record
 *   points to it. Sales always sell from a purchase lot, so a sold product always has a
 *   purchase row.
 * - A brand, category or unit is in use when a product points to it.
 *
 * Only rows of the record's own business count, whoever is asking.
 */
class CatalogUsage
{
    public const PRODUCT = 'product';
    public const BRAND = 'brand';
    public const CATEGORY = 'category';
    public const UNIT = 'unit';

    /** @var array<string, array<int, array{0: class-string<Model>, 1: string}>> referencing [model, column] pairs */
    private const REFERENCES = [
        self::PRODUCT => [
            [PurchaseProduct::class, 'productName'],
            [ReturnSaleItem::class, 'productId'],
            [ReturnPurchaseItem::class, 'productId'],
            [DamageProduct::class, 'product_id'],
        ],
        self::BRAND => [[Product::class, 'brand']],
        self::CATEGORY => [[Product::class, 'category']],
        self::UNIT => [[Product::class, 'unitName']],
    ];

    public function isInUse(string $type, Model $record): bool
    {
        return $this->inUseIds($type, collect([$record])) !== [];
    }

    /**
     * @param Collection<int, Model> $records
     * @return int[] keys of the records that are in use
     */
    public function inUseIds(string $type, Collection $records): array
    {
        $references = self::REFERENCES[$type] ?? throw new InvalidArgumentException("Unknown catalog type [$type].");
        $inUse = [];

        foreach ($records->groupBy(fn (Model $record) => (string) $record->businessId) as $businessId => $group) {
            $ids = $group->map(fn (Model $record) => (int) $record->getKey())->all();
            foreach ($references as [$model, $column]) {
                $used = $model::query()
                    ->withoutGlobalScope('business')
                    ->when($businessId === '', fn ($q) => $q->whereNull('businessId'), fn ($q) => $q->where('businessId', (int) $businessId))
                    ->whereIn($column, $ids)
                    ->distinct()
                    ->pluck($column)
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $inUse = array_merge($inUse, $used);
            }
        }

        return array_values(array_unique($inUse));
    }
}
