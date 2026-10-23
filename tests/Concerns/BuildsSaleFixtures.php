<?php

namespace Tests\Concerns;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductStock;
use App\Models\PurchaseProduct;
use App\Models\SaleProduct;
use App\Models\Supplier;

/** Users, a serialized purchase lot and real sales (through the sale form) for feature tests. */
trait BuildsSaleFixtures
{
    private function actor(string $role, int $businessId = 1): AdminUser
    {
        return AdminUser::create([
            'fullName' => 'Fixture '.$role, 'sureName' => 'Test', 'storeName' => 'Test',
            'mail' => uniqid($role).'@example.test', 'contactNumber' => '0000000000',
            'password' => 'unused-test-password', 'role' => $role, 'businessId' => $businessId,
        ]);
    }

    /** One purchase lot of 3 units with 3 available serials. */
    private function inventory(int $businessId = 1): array
    {
        if (!BusinessSetup::find($businessId)) {
            BusinessSetup::forceCreate(['id' => $businessId, 'businessName' => 'Test Business '.$businessId]);
        }
        $product = Product::create(['name' => uniqid('Fixture Product '), 'businessId' => $businessId]);
        $supplier = Supplier::create(['name' => uniqid('Fixture Supplier '), 'businessId' => $businessId]);
        $purchase = PurchaseProduct::create([
            'productName' => $product->id, 'supplier' => $supplier->id, 'businessId' => $businessId,
            'purchase_date' => '2026-10-23', 'invoice' => uniqid('PUR-'), 'qty' => 3,
            'buyPrice' => 100, 'salePriceExVat' => 150, 'totalAmount' => 300,
            'grandTotal' => 300, 'paidAmount' => 300, 'dueAmount' => 0,
        ]);
        $stock = ProductStock::create([
            'productId' => $product->id, 'purchaseId' => $purchase->id,
            'businessId' => $businessId, 'currentStock' => 3,
        ]);
        $serials = [];
        for ($i = 0; $i < 3; $i++) {
            $serials[] = ProductSerial::create([
                'productId' => $product->id, 'purchaseId' => $purchase->id,
                'serialNumber' => 'FIX-'.$purchase->id.'-'.$i, 'status' => 'available',
            ]);
        }
        return compact('product', 'supplier', 'purchase', 'stock', 'serials');
    }

    /** Records a sale through the real sale form as the signed-in user. */
    private function sell(array $inventory, int $qty = 2, bool $backorder = false, ?Customer $customer = null): SaleProduct
    {
        $customer ??= Customer::create(['name' => 'Fixture Customer', 'businessId' => 1]);
        $payload = [
            'date' => '2026-10-23', 'customerId' => $customer->id,
            'purchaseData' => [$inventory['purchase']->id], 'qty' => [$qty],
            'salePrice' => [150], 'buyPrice' => [100], 'warranty_days' => ['365'],
            'totalSaleAmount' => 150 * $qty, 'discountAmount' => 0,
            'grandTotal' => 150 * $qty, 'paidAmount' => 100,
            'dueAmount' => 150 * $qty - 100, 'prevDue' => 0, 'curDue' => 150 * $qty - 100,
        ];
        if ($backorder) {
            $payload['backorder'] = [$inventory['purchase']->id];
        } else {
            $payload['serialId'] = [array_map(fn ($s) => $s->id, array_slice($inventory['serials'], 0, $qty))];
        }
        $this->post(route('saveSale'), $payload)->assertSessionHasNoErrors()->assertRedirect();

        return SaleProduct::query()->latest('id')->firstOrFail();
    }

    private function alertText(): string
    {
        return (string) session('alert.config');
    }
}
