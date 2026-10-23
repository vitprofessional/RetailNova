<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\InvoiceItem;
use App\Models\ProductSerial;
use App\Models\SaleProduct;
use Illuminate\Support\Facades\DB;

/**
 * Cancels sales while keeping their history.
 *
 * Inside one transaction, with the sale row locked: the quantity the sale still
 * holds goes back to its purchase lot (backorder lines never took stock), its
 * serials become available again, and the sale and its items are soft-deleted
 * with who cancelled it, when and why. Any failure rolls back everything,
 * including the other sales of a bulk cancel.
 *
 * Callers authorize first; the tenant scope still hides other businesses' sales.
 */
class SaleCancellation
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /** @throws SaleCancellationException */
    public function cancel(int $saleId, string $reason, AdminUser $actor): SaleProduct
    {
        return DB::transaction(fn () => $this->cancelLocked($saleId, $reason, $actor));
    }

    /**
     * All or nothing: if one sale fails, none is cancelled.
     *
     * @param int[] $saleIds
     * @throws SaleCancellationException naming the sale that failed
     */
    public function cancelMany(array $saleIds, string $reason, AdminUser $actor): int
    {
        return DB::transaction(function () use ($saleIds, $reason, $actor) {
            foreach ($saleIds as $saleId) {
                $this->cancelLocked((int) $saleId, $reason, $actor);
            }

            return count($saleIds);
        });
    }

    private function cancelLocked(int $saleId, string $reason, AdminUser $actor): SaleProduct
    {
        $sale = SaleProduct::withTrashed()->lockForUpdate()->find($saleId);
        if (!$sale) {
            throw SaleCancellationException::notFound($saleId);
        }
        if ($sale->trashed() || $sale->cancelled_at !== null) {
            throw SaleCancellationException::alreadyCancelled($sale);
        }

        $items = InvoiceItem::where('saleId', $sale->id)->lockForUpdate()->get();
        foreach ($items as $item) {
            // qty is what the sale still holds: partial returns have already reduced it.
            $qty = (int) $item->qty;
            if ($item->isBackorder || $qty <= 0) {
                continue;
            }
            if (!$this->stock->increaseStockForSaleReturn((int) $item->purchaseId, $qty)) {
                throw SaleCancellationException::stockNotRestored($sale);
            }
        }

        ProductSerial::where('saleId', $sale->id)
            ->update(['saleId' => null, 'status' => 'available', 'sold_at' => null]);

        $sale->cancel_reason = $reason;
        $sale->cancelled_by = $actor->id;
        $sale->cancelled_at = now();
        $sale->save();

        foreach ($items as $item) {
            $item->delete();
        }
        $sale->delete();

        return $sale;
    }
}
