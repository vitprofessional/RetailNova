<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ProductStock;
use App\Models\PurchaseProduct;
use Illuminate\Support\Collection;

/**
 * Read-only product cards for the mobile sale screen: name, category, stock on hand and the
 * selling price of the latest purchase lot. Adding a card to the sale still goes through the
 * existing product selector, so prices, lots, serials and stock rules stay the server's.
 */
class PosCatalog
{
    /**
     * @param iterable<\App\Models\Product> $products products already offered by the sale page
     * @return array{cards: array<int, array>, categories: array<int, array{id: int, name: string}>}
     */
    public function cards(iterable $products): array
    {
        $products = collect($products);
        $ids = $products->pluck('id')->all();
        if ($ids === []) {
            return ['cards' => [], 'categories' => []];
        }

        $stock = ProductStock::query()
            ->whereIn('productId', $ids)
            ->selectRaw('productId, SUM(currentStock) as total')
            ->groupBy('productId')
            ->pluck('total', 'productId');
        $prices = PurchaseProduct::query()
            ->whereIn('productName', $ids)
            ->orderByDesc('id')
            ->get(['productName', 'salePriceExVat'])
            ->unique('productName')
            ->pluck('salePriceExVat', 'productName');
        $categoryNames = Category::query()
            ->whereIn('id', $products->pluck('category')->filter()->unique()->all())
            ->pluck('name', 'id');

        $cards = $products->map(fn ($product) => [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'category' => $product->category ? (int) $product->category : null,
            'stock' => (int) ($stock[$product->id] ?? 0),
            'price' => isset($prices[$product->id]) ? (float) $prices[$product->id] : null,
            'barcode' => (string) ($product->barCode ?? ''),
        ])->sortByDesc(fn ($card) => $card['stock'] > 0)->values();

        $categories = $cards->pluck('category')->filter()->unique()
            ->map(fn ($id) => ['id' => $id, 'name' => (string) ($categoryNames[$id] ?? '')])
            ->filter(fn ($category) => $category['name'] !== '')
            ->sortBy('name')->values();

        return ['cards' => $cards->all(), 'categories' => $categories->all()];
    }
}
