<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class BarcodeService
{
    /**
     * Generate a unique barcode for a product
     * Uses Code128 format (alphanumeric)
     */
    public function generateBarcode(): string
    {
        // Format: PREFIX-TIMESTAMP-RANDOM
        // Example: RN-20260324-ABC123
        $prefix = 'RN'; // RetailNova
        $timestamp = date('Ymd');
        $random = strtoupper(Str::random(6));
        
        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Generate barcode with product ID
     * Format: ProductID-CheckDigit
     */
    public function generateBarcodeFromProductId(int $productId): string
    {
        $barcode = 'PRD' . str_pad($productId, 8, '0', STR_PAD_LEFT);
        return $barcode;
    }

    /**
     * Find product by barcode
     */
    public function findProductByBarcode(string $barcode): ?Product
    {
        return Product::where('barCode', $barcode)
            ->orWhere('barCode', trim($barcode))
            ->first();
    }

    /**
     * Assign barcode to product
     */
    public function assignBarcodeToProduct(int $productId, string $barcode = null): ?Product
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return null;
        }

        // Generate barcode if not provided
        if (!$barcode) {
            $barcode = $this->generateBarcodeFromProductId($productId);
        }

        $product->update(['barCode' => $barcode]);
        return $product;
    }

    /**
     * Generate barcode for all products without one
     */
    public function generateMissingBarcodes(): int
    {
        $count = 0;
        $products = Product::whereNull('barCode')
            ->orWhere('barCode', '')
            ->get();

        foreach ($products as $product) {
            $barcode = $this->generateBarcodeFromProductId($product->id);
            $product->update(['barCode' => $barcode]);
            $count++;
        }

        return $count;
    }

    /**
     * Validate barcode format
     */
    public function isValidBarcode(string $barcode): bool
    {
        // Basic validation: not empty and reasonable length (5-50 chars)
        return !empty(trim($barcode)) && 
               strlen(trim($barcode)) >= 5 && 
               strlen(trim($barcode)) <= 50;
    }

    /**
     * Check if barcode is unique
     */
    public function isBarcodeUnique(string $barcode, int $excludeProductId = null): bool
    {
        $query = Product::where('barCode', $barcode);
        
        if ($excludeProductId) {
            $query->where('id', '!=', $excludeProductId);
        }

        return $query->count() === 0;
    }
}
