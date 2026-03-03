<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ProductStock;
use Carbon\Carbon;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $product = fn(string $barCode) => DB::table('products')->where('barCode', $barCode)->value('id');
        $supplier = fn(string $mail) => DB::table('suppliers')->where('mail', $mail)->value('id');

        // Base date for spread-out demo purchases
        $base = Carbon::now()->subDays(60);

        $purchases = [
            [
                'productName'      => $product('SMG-A54-001'),
                'supplier'         => $supplier('orders@techsource.com'),
                'purchase_date'    => $base->copy()->addDays(0)->toDateString(),
                'invoice'          => 'PUR-2026-0001',
                'reference'        => 'REF-001',
                'qty'              => 30,
                'buyPrice'         => '25000',
                'salePriceExVat'   => '32000',
                'vatStatus'        => 'exclusive',
                'salePriceInVat'   => '35200',
                'profit'           => '7000',
                'totalAmount'      => '750000',
                'disType'          => 'flat',
                'disAmount'        => '0',
                'disParcent'       => '0',
                'grandTotal'       => '750000',
                'paidAmount'       => '750000',
                'dueAmount'        => '0',
                'specialNote'      => 'Samsung Galaxy A54 stock purchase',
                'businessId'       => 1,
            ],
            [
                'productName'      => $product('APL-IP14-001'),
                'supplier'         => $supplier('supply@globalgadgets.com'),
                'purchase_date'    => $base->copy()->addDays(5)->toDateString(),
                'invoice'          => 'PUR-2026-0002',
                'reference'        => 'REF-002',
                'qty'              => 15,
                'buyPrice'         => '85000',
                'salePriceExVat'   => '105000',
                'vatStatus'        => 'exclusive',
                'salePriceInVat'   => '115500',
                'profit'           => '20000',
                'totalAmount'      => '1275000',
                'disType'          => 'flat',
                'disAmount'        => '25000',
                'disParcent'       => '2',
                'grandTotal'       => '1250000',
                'paidAmount'       => '1250000',
                'dueAmount'        => '0',
                'specialNote'      => 'iPhone 14 batch',
                'businessId'       => 1,
            ],
            [
                'productName'      => $product('DEL-INS15-001'),
                'supplier'         => $supplier('info@primeelectronics.pk'),
                'purchase_date'    => $base->copy()->addDays(10)->toDateString(),
                'invoice'          => 'PUR-2026-0003',
                'reference'        => 'REF-003',
                'qty'              => 12,
                'buyPrice'         => '60000',
                'salePriceExVat'   => '75000',
                'vatStatus'        => 'exclusive',
                'salePriceInVat'   => '82500',
                'profit'           => '15000',
                'totalAmount'      => '720000',
                'disType'          => 'flat',
                'disAmount'        => '0',
                'disParcent'       => '0',
                'grandTotal'       => '720000',
                'paidAmount'       => '360000',
                'dueAmount'        => '360000',
                'specialNote'      => 'Dell laptops – partial payment',
                'businessId'       => 1,
            ],
            [
                'productName'      => $product('LG-TV43-001'),
                'supplier'         => $supplier('sales@swiftimports.pk'),
                'purchase_date'    => $base->copy()->addDays(15)->toDateString(),
                'invoice'          => 'PUR-2026-0004',
                'reference'        => 'REF-004',
                'qty'              => 10,
                'buyPrice'         => '55000',
                'salePriceExVat'   => '70000',
                'vatStatus'        => 'exclusive',
                'salePriceInVat'   => '77000',
                'profit'           => '15000',
                'totalAmount'      => '550000',
                'disType'          => 'flat',
                'disAmount'        => '0',
                'disParcent'       => '0',
                'grandTotal'       => '550000',
                'paidAmount'       => '550000',
                'dueAmount'        => '0',
                'specialNote'      => 'LG Smart TV stock',
                'businessId'       => 1,
            ],
            [
                'productName'      => $product('SNY-WH1000XM5-001'),
                'supplier'         => $supplier('contact@horizontrading.pk'),
                'purchase_date'    => $base->copy()->addDays(20)->toDateString(),
                'invoice'          => 'PUR-2026-0005',
                'reference'        => 'REF-005',
                'qty'              => 20,
                'buyPrice'         => '28000',
                'salePriceExVat'   => '38000',
                'vatStatus'        => 'exclusive',
                'salePriceInVat'   => '41800',
                'profit'           => '10000',
                'totalAmount'      => '560000',
                'disType'          => 'flat',
                'disAmount'        => '0',
                'disParcent'       => '0',
                'grandTotal'       => '560000',
                'paidAmount'       => '560000',
                'dueAmount'        => '0',
                'specialNote'      => 'Sony headphones batch',
                'businessId'       => 1,
            ],
            [
                'productName'      => $product('GEN-USBCHDMI-2M'),
                'supplier'         => $supplier('orders@techsource.com'),
                'purchase_date'    => $base->copy()->addDays(25)->toDateString(),
                'invoice'          => 'PUR-2026-0006',
                'reference'        => 'REF-006',
                'qty'              => 60,
                'buyPrice'         => '500',
                'salePriceExVat'   => '900',
                'vatStatus'        => 'exclusive',
                'salePriceInVat'   => '990',
                'profit'           => '400',
                'totalAmount'      => '30000',
                'disType'          => 'flat',
                'disAmount'        => '0',
                'disParcent'       => '0',
                'grandTotal'       => '30000',
                'paidAmount'       => '30000',
                'dueAmount'        => '0',
                'specialNote'      => 'Accessor cables lot',
                'businessId'       => 1,
            ],
        ];

        foreach ($purchases as $row) {
            $now = now();
            $id = DB::table('purchase_products')->insertGetId(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            // Link purchase to the product_stock record if no purchaseId yet
            $stockRecord = ProductStock::where('productId', $row['productName'])
                ->whereNull('purchaseId')
                ->first();
            if ($stockRecord) {
                $stockRecord->update(['purchaseId' => $id]);
            }
        }
    }
}
