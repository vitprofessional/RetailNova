<?php

namespace App\Services;

use App\Models\{BusinessSetup, Product, ProductSerial, PurchaseProduct, SaleProduct};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class SalesSerialPolicy
{
    public function required(?int $businessId, bool $lock = false): bool
    {
        // Unassigned/legacy businesses retain the mandatory default.
        $query = BusinessSetup::whereKey($businessId);
        if ($lock) { $query->lockForUpdate(); }
        return (bool) ($query->first()?->sales_serial_selection_required ?? true);
    }

    public function available(Builder $query): Builder
    {
        return $query->whereNull('saleId')->where(function ($q) {
            $q->whereNull('status')->orWhere('status', 'available');
        });
    }

    public function serialized(PurchaseProduct $purchase): bool
    {
        // There is no product-level serialization flag in this schema.
        return ProductSerial::where(function ($query) use ($purchase) {
            $query->where('purchaseId', $purchase->id)->orWhere('productId', $purchase->productName);
        })->exists();
    }

    public function purchase(int $purchaseId, ?int $businessId, string $field): PurchaseProduct
    {
        $purchase = PurchaseProduct::withoutGlobalScopes()->lockForUpdate()->find($purchaseId);
        $actor = auth('admin')->user();
        $platform = $actor && in_array(strtolower(trim($actor->role)), ['admin', 'superadmin'], true);
        $product = $purchase ? Product::withoutGlobalScopes()->find($purchase->productName) : null;
        if (!$purchase || !$actor || (!$platform && ((int) $actor->businessId <= 0
            || (int) $actor->businessId !== (int) $purchase->businessId))
            || (int) $purchase->businessId !== (int) $businessId
            || !$product || (int) $product->businessId !== (int) $purchase->businessId) {
            $this->fail($field, __('sales.the_purchase_is_unavailable_for_this_business'));
        }
        $stock = \App\Models\ProductStock::withoutGlobalScopes()->where('purchaseId', $purchaseId)->lockForUpdate()->first();
        if ($stock && ((int)$stock->businessId !== (int)$purchase->businessId
            || (int)$stock->productId !== (int)$purchase->productName)) {
            $this->fail($field, __('sales.stock_ownership_does_not_match_this_purchase'));
        }
        return $purchase;
    }

    /** Called inside the enclosing stock/sale transaction. No partial assignment escapes rollback. */
    public function sync(SaleProduct $sale, int $purchaseId, int $qty, array $ids, array $numbers,
        string $field, bool $editing = false, bool $backorder = false): void
    {
        $purchase = $this->purchase($purchaseId, $sale->businessId, $field);
        if ($backorder) {
            if ($ids || $numbers) { $this->fail($field, __('sales.backorders_cannot_allocate_serials_before_fulfillment')); }
            return;
        }
        if (!$this->serialized($purchase)) {
            if ($ids || $numbers) { $this->fail($field, __('sales.this_purchase_has_no_serial_inventory')); }
            return;
        }
        $required = $this->required($purchase->businessId, true);
        $ids = array_values(array_map('intval', $ids));
        if (!$ids && $numbers) {
            if (count(array_unique($numbers)) !== count($numbers)) { $this->fail($field, __('sales.duplicate_serial_selection')); }
            foreach ($numbers as $number) {
                $matches = ProductSerial::where('purchaseId', $purchaseId)->where('serialNumber', $number)->pluck('id');
                if ($matches->count() !== 1) { $this->fail($field, __('sales.invalid_or_ambiguous_serial_number')); }
                $ids[] = (int) $matches->first();
            }
        }
        if ($qty === 0 && $editing) { $ids = []; }
        if (count(array_unique($ids)) !== count($ids) || in_array(0, $ids, true)) {
            $this->fail($field, __('sales.duplicate_or_invalid_serial_selection'));
        }
        $current = $editing ? ProductSerial::where('purchaseId', $purchaseId)->where('saleId', $sale->id)
            ->orderBy('id')->lockForUpdate()->get() : new \Illuminate\Database\Eloquent\Collection();
        foreach ($current as $serial) {
            if ($serial->status !== 'sold' || (int)$serial->productId !== (int)$purchase->productName) {
                $this->fail($field, __('sales.existing_serial_allocation_is_inconsistent_review_it_before_editing_this_item'));
            }
        }
        // Unchanged edits can retain their existing valid allocation without opening the selector.
        if (!$ids && $editing && $current->count() === $qty) { $ids = $current->modelKeys(); }
        if (!$ids && !$required) {
            $ids = $current->take($qty)->modelKeys();
            $needed = $qty - count($ids);
            if ($needed > 0) {
                $ids = array_merge($ids, $this->available(ProductSerial::where('purchaseId', $purchaseId)
                    ->where('productId', $purchase->productName))
                    ->orderBy('id')->limit($needed)->lockForUpdate()->get()->modelKeys());
            }
        }
        if (count($ids) !== $qty) {
            $this->fail($field, __($required ? 'sales.select_exact' : 'sales.needs_available', ['qty' => $qty]));
        }
        $rows = ProductSerial::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        if ($rows->count() !== count($ids)) { $this->fail($field, __('sales.invalid_serial_selection')); }
        foreach ($rows as $serial) {
            $retained = $editing && (int) $serial->saleId === (int) $sale->id && $serial->status === 'sold';
            if ((int) $serial->purchaseId !== $purchaseId || (int) $serial->productId !== (int) $purchase->productName
                || (!$retained && ($serial->saleId !== null || !in_array($serial->status, [null, 'available'], true)))) {
                $this->fail($field, __('sales.a_selected_serial_is_unavailable_already_sold_or_belongs_to_another_purchase'));
            }
        }
        foreach ($current as $serial) {
            if (!in_array((int) $serial->id, array_map('intval', $ids), true)) {
                $serial->update(['saleId' => null, 'status' => 'available', 'sold_at' => null]);
            }
        }
        foreach ($rows as $serial) {
            if ((int) $serial->saleId !== (int) $sale->id) {
                $serial->update(['saleId' => $sale->id, 'status' => 'sold', 'sold_at' => now()]);
            }
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
