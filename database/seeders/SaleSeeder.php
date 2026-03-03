<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $customer = fn(string $mail) => DB::table('customers')->where('mail', $mail)->value('id');
        $purchase = fn(string $invoice) => DB::table('purchase_products')->where('invoice', $invoice)->value('id');

        $base = Carbon::now()->subDays(45);

        /**
         * Each sale has: header data + items.
         * invoice_items.saleId  = sale_products.id
         * invoice_items.purchaseId = purchase_products.id
         */
        $sales = [
            [
                'header' => [
                    'date'           => $base->copy()->addDays(2)->toDateString(),
                    'invoice'        => 'INV-2026-0001',
                    'customerId'     => $customer('ahmed.ali@email.com'),
                    'reference'      => '',
                    'note'           => 'Walk-in sale',
                    'totalSale'      => '64000',
                    'discountAmount' => '0',
                    'grandTotal'     => '64000',
                    'paidAmount'     => '64000',
                    'invoiceDue'     => '0',
                    'prevDue'        => '0',
                    'curDue'         => '0',
                    'status'         => 'complete',
                    'businessId'     => 1,
                ],
                'items' => [
                    [
                        'purchaseId'    => $purchase('PUR-2026-0001'),
                        'qty'           => 2,
                        'salePrice'     => '32000',
                        'buyPrice'      => '25000',
                        'totalSale'     => '64000',
                        'totalPurchase' => '50000',
                        'profitTotal'   => '14000',
                        'profitMargin'  => '21.88',
                        'warranty_days' => '365',
                    ],
                ],
            ],
            [
                'header' => [
                    'date'           => $base->copy()->addDays(5)->toDateString(),
                    'invoice'        => 'INV-2026-0002',
                    'customerId'     => $customer('sara.khan@email.com'),
                    'reference'      => 'REF-ONLINE-001',
                    'note'           => 'iPhone sale with trade-in credit',
                    'totalSale'      => '115500',
                    'discountAmount' => '5500',
                    'grandTotal'     => '110000',
                    'paidAmount'     => '110000',
                    'invoiceDue'     => '0',
                    'prevDue'        => '2500',
                    'curDue'         => '2500',
                    'status'         => 'complete',
                    'businessId'     => 1,
                ],
                'items' => [
                    [
                        'purchaseId'    => $purchase('PUR-2026-0002'),
                        'qty'           => 1,
                        'salePrice'     => '115500',
                        'buyPrice'      => '85000',
                        'totalSale'     => '115500',
                        'totalPurchase' => '85000',
                        'profitTotal'   => '30500',
                        'profitMargin'  => '26.41',
                        'warranty_days' => '365',
                    ],
                ],
            ],
            [
                'header' => [
                    'date'           => $base->copy()->addDays(8)->toDateString(),
                    'invoice'        => 'INV-2026-0003',
                    'customerId'     => $customer('usman.malik@email.com'),
                    'reference'      => '',
                    'note'           => 'TV + Headphones combo',
                    'totalSale'      => '118800',
                    'discountAmount' => '2800',
                    'grandTotal'     => '116000',
                    'paidAmount'     => '80000',
                    'invoiceDue'     => '36000',
                    'prevDue'        => '0',
                    'curDue'         => '36000',
                    'status'         => 'partial',
                    'businessId'     => 1,
                ],
                'items' => [
                    [
                        'purchaseId'    => $purchase('PUR-2026-0004'),
                        'qty'           => 1,
                        'salePrice'     => '77000',
                        'buyPrice'      => '55000',
                        'totalSale'     => '77000',
                        'totalPurchase' => '55000',
                        'profitTotal'   => '22000',
                        'profitMargin'  => '28.57',
                        'warranty_days' => '365',
                    ],
                    [
                        'purchaseId'    => $purchase('PUR-2026-0005'),
                        'qty'           => 1,
                        'salePrice'     => '41800',
                        'buyPrice'      => '28000',
                        'totalSale'     => '41800',
                        'totalPurchase' => '28000',
                        'profitTotal'   => '13800',
                        'profitMargin'  => '33.01',
                        'warranty_days' => '180',
                    ],
                ],
            ],
            [
                'header' => [
                    'date'           => $base->copy()->addDays(12)->toDateString(),
                    'invoice'        => 'INV-2026-0004',
                    'customerId'     => $customer('bilal.hassan@email.com'),
                    'reference'      => '',
                    'note'           => 'Dell laptop',
                    'totalSale'      => '82500',
                    'discountAmount' => '0',
                    'grandTotal'     => '82500',
                    'paidAmount'     => '82500',
                    'invoiceDue'     => '0',
                    'prevDue'        => '0',
                    'curDue'         => '0',
                    'status'         => 'complete',
                    'businessId'     => 1,
                ],
                'items' => [
                    [
                        'purchaseId'    => $purchase('PUR-2026-0003'),
                        'qty'           => 1,
                        'salePrice'     => '82500',
                        'buyPrice'      => '60000',
                        'totalSale'     => '82500',
                        'totalPurchase' => '60000',
                        'profitTotal'   => '22500',
                        'profitMargin'  => '27.27',
                        'warranty_days' => '365',
                    ],
                ],
            ],
            [
                'header' => [
                    'date'           => $base->copy()->addDays(18)->toDateString(),
                    'invoice'        => 'INV-2026-0005',
                    'customerId'     => $customer('nadia.hussain@email.com'),
                    'reference'      => '',
                    'note'           => 'Cables and accessories',
                    'totalSale'      => '4950',
                    'discountAmount' => '0',
                    'grandTotal'     => '4950',
                    'paidAmount'     => '4950',
                    'invoiceDue'     => '0',
                    'prevDue'        => '3000',
                    'curDue'         => '3000',
                    'status'         => 'complete',
                    'businessId'     => 1,
                ],
                'items' => [
                    [
                        'purchaseId'    => $purchase('PUR-2026-0006'),
                        'qty'           => 5,
                        'salePrice'     => '990',
                        'buyPrice'      => '500',
                        'totalSale'     => '4950',
                        'totalPurchase' => '2500',
                        'profitTotal'   => '2450',
                        'profitMargin'  => '49.49',
                        'warranty_days' => null,
                    ],
                ],
            ],
            [
                'header' => [
                    'date'           => $base->copy()->addDays(25)->toDateString(),
                    'invoice'        => 'INV-2026-0006',
                    'customerId'     => $customer('fatima.riaz@email.com'),
                    'reference'      => '',
                    'note'           => 'Two Samsung phones',
                    'totalSale'      => '70400',
                    'discountAmount' => '400',
                    'grandTotal'     => '70000',
                    'paidAmount'     => '70000',
                    'invoiceDue'     => '0',
                    'prevDue'        => '1000',
                    'curDue'         => '1000',
                    'status'         => 'complete',
                    'businessId'     => 1,
                ],
                'items' => [
                    [
                        'purchaseId'    => $purchase('PUR-2026-0001'),
                        'qty'           => 2,
                        'salePrice'     => '35200',
                        'buyPrice'      => '25000',
                        'totalSale'     => '70400',
                        'totalPurchase' => '50000',
                        'profitTotal'   => '20400',
                        'profitMargin'  => '28.98',
                        'warranty_days' => '365',
                    ],
                ],
            ],
            [
                'header' => [
                    'date'           => $base->copy()->addDays(30)->toDateString(),
                    'invoice'        => 'INV-2026-0007',
                    'customerId'     => $customer('tariq.mehmood@email.com'),
                    'reference'      => '',
                    'note'           => 'Samsung tablet',
                    'totalSale'      => '38000',
                    'discountAmount' => '0',
                    'grandTotal'     => '38000',
                    'paidAmount'     => '0',
                    'invoiceDue'     => '38000',
                    'prevDue'        => '0',
                    'curDue'         => '38000',
                    'status'         => 'due',
                    'businessId'     => 1,
                ],
                'items' => [
                    [
                        'purchaseId'    => null,  // no direct purchase link (seeded via opening stock)
                        'qty'           => 1,
                        'salePrice'     => '38000',
                        'buyPrice'      => '22000',
                        'totalSale'     => '38000',
                        'totalPurchase' => '22000',
                        'profitTotal'   => '16000',
                        'profitMargin'  => '42.11',
                        'warranty_days' => '365',
                    ],
                ],
            ],
            [
                'header' => [
                    'date'           => $base->copy()->addDays(38)->toDateString(),
                    'invoice'        => 'INV-2026-0008',
                    'customerId'     => $customer('zainab.qureshi@email.com'),
                    'reference'      => '',
                    'note'           => 'Canon camera + headphones',
                    'totalSale'      => '79800',
                    'discountAmount' => '5000',
                    'grandTotal'     => '74800',
                    'paidAmount'     => '74800',
                    'invoiceDue'     => '0',
                    'prevDue'        => '500',
                    'curDue'         => '500',
                    'status'         => 'complete',
                    'businessId'     => 1,
                ],
                'items' => [
                    [
                        'purchaseId'    => $purchase('PUR-2026-0005'),
                        'qty'           => 1,
                        'salePrice'     => '41800',
                        'buyPrice'      => '28000',
                        'totalSale'     => '41800',
                        'totalPurchase' => '28000',
                        'profitTotal'   => '13800',
                        'profitMargin'  => '33.01',
                        'warranty_days' => '180',
                    ],
                    [
                        'purchaseId'    => null,
                        'qty'           => 1,
                        'salePrice'     => '38000',
                        'buyPrice'      => '28000',
                        'totalSale'     => '38000',
                        'totalPurchase' => '28000',
                        'profitTotal'   => '10000',
                        'profitMargin'  => '26.32',
                        'warranty_days' => '365',
                    ],
                ],
            ],
        ];

        $now = now();
        foreach ($sales as $sale) {
            $saleId = DB::table('sale_products')->insertGetId(array_merge($sale['header'], [
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            foreach ($sale['items'] as $item) {
                DB::table('invoice_items')->insert(array_merge($item, [
                    'saleId'     => $saleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }
}
