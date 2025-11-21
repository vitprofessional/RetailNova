<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\ProductStock;
use App\Models\PurchaseProduct;
use App\Models\InvoiceItem;
use App\Models\ReturnPurchaseItem;
use App\Models\ReturnSaleItem;

class StockService
{
    public function increaseStockForSaleReturn(int $purchaseId, int $qty): bool
    {
        return DB::transaction(function () use ($purchaseId, $qty) {
            $stock = ProductStock::where('purchaseId', $purchaseId)->lockForUpdate()->first();
            if(!$stock){
                return false;
            }
            $stock->currentStock = (int)$stock->currentStock + $qty;
            return $stock->save();
        });
    }

    public function decreaseStockForPurchaseReturn(int $purchaseId, int $qty): bool
    {
        return DB::transaction(function () use ($purchaseId, $qty) {
            $stock = ProductStock::where('purchaseId', $purchaseId)->lockForUpdate()->first();
            if(!$stock){
                return false;
            }
            $newStock = (int)$stock->currentStock - $qty;
            if($newStock < 0){
                $newStock = 0;
            }
            $stock->currentStock = $newStock;
            $stock->save();

            $purchase = PurchaseProduct::find($purchaseId);
            if($purchase){
                $purchase->qty = max(0, (int)$purchase->qty - $qty);
                $purchase->save();
            }
            return true;
        });
    }

    public function validatePurchaseReturn(int $purchaseId, int $qty): bool
    {
        $purchase = PurchaseProduct::find($purchaseId);
        if(!$purchase){
            return false;
        }
        return $qty > 0 && $qty <= (int)$purchase->qty;
    }

    public function applySaleReturnItem(ReturnSaleItem $returnItem): bool
    {
        return DB::transaction(function () use ($returnItem) {
            $stock = ProductStock::where('purchaseId', $returnItem->purchaseId)->lockForUpdate()->first();
            if($stock){
                $stock->currentStock = (int)$stock->currentStock + (int)$returnItem->qty;
                $stock->save();
            }
            $invoiceItem = InvoiceItem::where(['saleId' => $returnItem->saleId, 'purchaseId' => $returnItem->purchaseId])->lockForUpdate()->first();
            if($invoiceItem){
                $invoiceItem->qty = max(0, (int)$invoiceItem->qty - (int)$returnItem->qty);
                $invoiceItem->save();
            }
            return true;
        });
    }

    /**
     * Adjust stock when a purchase quantity changes.
     * Ensures new quantity is not less than total returned quantity.
     * Returns array: ['success' => bool, 'message' => string]
     */
    public function adjustStockForPurchaseQtyChange(int $purchaseId, int $oldQty, int $newQty): array
    {
        return DB::transaction(function () use ($purchaseId, $oldQty, $newQty) {
            $purchase = PurchaseProduct::lockForUpdate()->find($purchaseId);
            if(!$purchase){
                return ['success'=>false,'message'=>'Purchase not found'];
            }
            $returnedQty = (int)ReturnPurchaseItem::where('purchaseId', $purchaseId)->sum('qty');
            if($newQty < $returnedQty){
                return ['success'=>false,'message'=>'New quantity cannot be less than returned quantity ('.$returnedQty.')'];
            }
            $delta = $newQty - $oldQty;
            $stock = ProductStock::where('purchaseId', $purchaseId)->lockForUpdate()->first();
            if($stock){
                $stock->currentStock = max(0, (int)$stock->currentStock + $delta);
                $stock->save();
            } else {
                // Create stock if missing and delta positive
                $newStockQty = max(0, $newQty);
                $stock = new ProductStock();
                $stock->purchaseId = $purchaseId;
                $stock->productId = $purchase->productName; // existing schema field
                $stock->currentStock = $newStockQty;
                $stock->save();
            }
            return ['success'=>true,'message'=>'Stock adjusted'];
        });
    }

    /**
     * Decrease stock for a sale without modifying the original purchase record.
     * Returns true on success, false if stock insufficient or purchase/stock not found.
     */
    public function decreaseStockForSale(int $purchaseId, int $qty): bool
    {
        return DB::transaction(function () use ($purchaseId, $qty) {
            $stock = ProductStock::where('purchaseId', $purchaseId)->lockForUpdate()->first();
            if (!$stock) {
                return false;
            }
            $current = (int)$stock->currentStock;
            if ($qty <= 0) {
                return false;
            }
            if ($current < $qty) {
                // Not enough stock to fulfill sale from this purchase row
                return false;
            }
            $stock->currentStock = $current - $qty;
            $stock->save();
            return true;
        });
    }
}
