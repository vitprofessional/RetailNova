<?php

namespace App\Services;

use App\Models\DamageProduct;
use App\Models\Product;
use App\Models\PurchaseProduct;
use App\Models\QuotationItem;
use App\Models\ReturnPurchaseItem;
use App\Models\ReturnSaleItem;
use App\Models\ServiceInvoiceItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Tells which catalog records are still referenced and therefore must not be deleted.
 *
 * - A product is in use when a purchase, sale return, purchase return, damage record or
 *   quotation line points to it. Sales always sell from a purchase lot, so a sold product
 *   always has a purchase row.
 * - A brand, category or unit is in use when a product points to it.
 * - A provided service is in use when a service invoice of the same customer bills the same
 *   service name. (Service invoices write these rows alongside their lines but store no key
 *   to them, so customer + service name is the link; a same-named older row is kept too.)
 *
 * Only rows of the record's own business count, whoever is asking.
 */
class CatalogUsage
{
    public const PRODUCT = 'product';
    public const BRAND = 'brand';
    public const CATEGORY = 'category';
    public const UNIT = 'unit';
    public const PROVIDED_SERVICE = 'provided_service';

    /** @var array<string, array<int, array{0: class-string<Model>, 1: string}>> referencing [model, column] pairs */
    private const REFERENCES = [
        self::PRODUCT => [
            [PurchaseProduct::class, 'productName'],
            [ReturnSaleItem::class, 'productId'],
            [ReturnPurchaseItem::class, 'productId'],
            [DamageProduct::class, 'product_id'],
            [QuotationItem::class, 'product_id'],
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
        if ($type === self::PROVIDED_SERVICE) {
            return $this->providedServicesInUse($records);
        }
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

    /** @param Collection<int, Model> $services provide_services rows */
    private function providedServicesInUse(Collection $services): array
    {
        $inUse = [];

        // Walk-in invoices store no customer on either side, so an empty customer matches an empty one.
        $key = fn ($customerId, $serviceName) => (($customerId === null || $customerId === '') ? '' : (int) $customerId).'|'.$serviceName;

        foreach ($services->groupBy(fn (Model $service) => (string) $service->businessId) as $businessId => $group) {
            $customerIds = $group->pluck('customerName')->filter(fn ($id) => $id !== null && $id !== '')->map(fn ($id) => (int) $id)->unique()->values()->all();
            $hasWalkIn = $group->contains(fn ($service) => $service->customerName === null || $service->customerName === '');

            $billed = ServiceInvoiceItem::query()
                ->withoutGlobalScope('business')
                ->join('service_invoices', 'service_invoices.id', '=', 'service_invoice_items.invoice_id')
                ->when($businessId === '',
                    fn ($q) => $q->whereNull('service_invoices.businessId'),
                    fn ($q) => $q->where('service_invoices.businessId', (int) $businessId))
                ->where(function ($q) use ($customerIds, $hasWalkIn) {
                    $q->whereIn('service_invoices.customer_id', $customerIds);
                    if ($hasWalkIn) {
                        $q->orWhereNull('service_invoices.customer_id');
                    }
                })
                ->whereIn('service_invoice_items.service_name', $group->pluck('serviceName')->filter()->unique()->values()->all())
                ->get(['service_invoices.customer_id', 'service_invoice_items.service_name'])
                ->map(fn ($row) => $key($row->customer_id, $row->service_name))
                ->flip();

            foreach ($group as $service) {
                if (isset($billed[$key($service->customerName, $service->serviceName)])) {
                    $inUse[] = (int) $service->getKey();
                }
            }
        }

        return $inUse;
    }
}
