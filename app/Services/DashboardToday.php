<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SaleProduct;
use App\Support\BusinessContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only figures for the mobile home screen: today's sales, cash received, today's due,
 * profit, low-stock products and the latest sales.
 *
 * Always limited to the signed-in user's own business (a platform owner without a business
 * sees the sole business of the installation, if there is exactly one).
 */
class DashboardToday
{
    public function summary(?AdminUser $actor, ?Carbon $day = null): array
    {
        $day ??= Carbon::today();
        // Without an own business (platform user, several businesses) the home shows nothing.
        $businessId = BusinessContext::validId($actor?->businessId) ?? BusinessContext::soleBusinessId() ?? BusinessContext::NO_BUSINESS;

        $todaySales = $this->own(SaleProduct::query(), $businessId)->whereDate('date', $day->toDateString());
        $saleIds = (clone $todaySales)->pluck('id');

        $stock = $this->own(ProductStock::query(), $businessId)
            ->selectRaw('productId, SUM(currentStock) as total')
            ->groupBy('productId')
            ->pluck('total', 'productId');
        $products = $this->own(Product::query(), $businessId)->get(['id', 'name', 'quantity']);
        $low = $products
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'stock' => (int) ($stock[$product->id] ?? 0),
                'alert' => (int) $product->quantity,
            ])
            ->filter(fn ($row) => $row['alert'] > 0 && $row['stock'] < $row['alert'])
            ->sortBy('stock')
            ->values();

        return [
            'sales' => (float) (clone $todaySales)->sum('grandTotal'),
            'cash' => (float) (clone $todaySales)->sum('paidAmount'),
            'due' => (float) (clone $todaySales)->sum('invoiceDue'),
            'profit' => (float) $this->own(InvoiceItem::query(), $businessId)->whereIn('saleId', $saleIds)->sum('profitTotal'),
            'count' => $saleIds->count(),
            'low_stock_count' => $low->count(),
            'out_of_stock_count' => $low->where('stock', '<=', 0)->count(),
            'low_stock' => $low->take(3)->all(),
            'recent' => $this->own(SaleProduct::query()->with('customer'), $businessId)
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(fn (SaleProduct $sale) => [
                    'id' => $sale->id,
                    'invoice' => (string) $sale->invoice,
                    'customer' => optional($sale->customer)->name,
                    'total' => (float) $sale->grandTotal,
                    'due' => (float) $sale->invoiceDue,
                    'date' => $sale->date ? Carbon::parse($sale->date) : $sale->created_at,
                ])
                ->all(),
        ];
    }

    /** The tenant scope already limits business users; this also pins platform users to one business. */
    private function own(Builder $query, int $businessId): Builder
    {
        return $query->withoutGlobalScope('business')->where($query->getModel()->qualifyColumn('businessId'), $businessId);
    }
}
