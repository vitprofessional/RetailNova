<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\ProductStock;
use App\Models\PurchaseProduct;
use App\Models\Product;
use App\Models\BusinessSetup;
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
            $qty = (int)$returnItem->qty;
            if($qty <= 0){
                return false;
            }

            $stock = ProductStock::where('purchaseId', $returnItem->purchaseId)->lockForUpdate()->first();
            if(!$stock){
                return false;
            }
            $stock->currentStock = (int)$stock->currentStock + $qty;
            $stock->save();

            $invoiceItem = InvoiceItem::where(['saleId' => $returnItem->saleId, 'purchaseId' => $returnItem->purchaseId])->lockForUpdate()->first();
            if(!$invoiceItem){
                return false;
            }

            $oldQty = (int)$invoiceItem->qty;
            if($oldQty < $qty){
                return false;
            }

            $newQty = $oldQty - $qty;
            $invoiceItem->qty = $newQty;

            // Recalculate totals based on new quantity
            $salePrice = (float)($invoiceItem->salePrice ?? 0);
            $buyPrice = (float)($invoiceItem->buyPrice ?? 0);
            $invoiceItem->totalSale = $newQty * $salePrice;
            $invoiceItem->totalPurchase = $newQty * $buyPrice;
            $invoiceItem->profitTotal = $invoiceItem->totalSale - $invoiceItem->totalPurchase;
            $invoiceItem->profitMargin = $invoiceItem->totalSale > 0
                ? round(($invoiceItem->profitTotal / $invoiceItem->totalSale) * 100, 2)
                : 0;

            $invoiceItem->save();
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
            $purchase = app(PurchaseWorkflow::class)->line($purchaseId);
            $purchase = PurchaseProduct::withoutGlobalScopes()->lockForUpdate()->find($purchaseId);
            if(!$purchase){
                return ['success'=>false,'message'=>'Purchase not found'];
            }
            $businessId = filter_var($purchase->businessId, FILTER_VALIDATE_INT);
            $product = Product::withoutGlobalScopes()->find($purchase->productName);
            if($businessId === false || (int)$businessId <= 0
                || !BusinessSetup::whereKey((int)$businessId)->exists()
                || !$product || (int)$product->businessId !== (int)$businessId){
                return ['success'=>false,'message'=>'Purchase ownership is incomplete or inconsistent'];
            }
            $returnedQty = (int)ReturnPurchaseItem::where('purchaseId', $purchaseId)->sum('qty');
            if($newQty < $returnedQty){
                return ['success'=>false,'message'=>'New quantity cannot be less than returned quantity ('.$returnedQty.')'];
            }
            $delta = $newQty - $oldQty;
            $stock = ProductStock::where('purchaseId', $purchaseId)->lockForUpdate()->first();
            if($stock){
                if ((int)$stock->businessId !== (int)$businessId
                    || (int)$stock->productId !== (int)$purchase->productName) {
                    return ['success'=>false,'message'=>'Stock ownership is inconsistent'];
                }
                $stock->currentStock = max(0, (int)$stock->currentStock + $delta);
                $stock->save();
            } else {
                // Create stock if missing and delta positive
                $newStockQty = max(0, $newQty);
                $stock = new ProductStock();
                $stock->purchaseId = $purchaseId;
                $stock->productId = $purchase->productName; // existing schema field
                $stock->businessId = (int)$businessId;
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
