<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Supplier;
use App\Models\Customer;
use Carbon\Carbon;

/**
 * Demo data for a Mobile Phone Shop
 */
class MobileShopSeeder extends Seeder
{
    public function run(): void
    {
        // ── Brands ──────────────────────────────────────────────────────────
        foreach (['Xiaomi', 'Vivo', 'Realme', 'Oppo', 'Tecno', 'Nokia'] as $b) {
            DB::table('brands')->insertOrIgnore(['name' => $b, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ── Categories ──────────────────────────────────────────────────────
        $cats = ['Smartphones', 'Feature Phones', 'Phone Cases', 'Screen Protectors',
                 'Chargers & Cables', 'Power Banks', 'Earphones & Headsets'];
        foreach ($cats as $c) {
            DB::table('categories')->insertOrIgnore(['name' => $c, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ── Helpers ──────────────────────────────────────────────────────────
        $brand    = fn(string $n) => (string)(DB::table('brands')->where('name', $n)->value('id') ?? 1);
        $cat      = fn(string $n) => (string)(DB::table('categories')->where('name', $n)->value('id') ?? 1);
        $unit     = fn(string $n) => (string)(DB::table('product_units')->where('name', $n)->value('id') ?? 1);
        $supId    = fn(string $mail) => DB::table('suppliers')->where('mail', $mail)->value('id');
        $custId   = fn(string $mail) => DB::table('customers')->where('mail', $mail)->value('id');
        $purId    = fn(string $inv)  => DB::table('purchase_products')->where('invoice', $inv)->value('id');

        // ── Suppliers ────────────────────────────────────────────────────────
        $suppliers = [
            ['name' => 'Mobile Zone Distributors', 'mail' => 'orders@mobilezone.com',   'mobile' => '0311-2001001', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Banani',     'openingBalance' => 0],
            ['name' => 'HandyTech Wholesale',       'mail' => 'supply@handytech.com',    'mobile' => '0311-2002002', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Agrabad',    'openingBalance' => 8000],
            ['name' => 'PhoneWorld Imports',        'mail' => 'import@phoneworld.com',   'mobile' => '0311-2003003', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Nawabpur',   'openingBalance' => 0],
        ];
        foreach ($suppliers as $s) {
            Supplier::updateOrCreate(['mail' => $s['mail']], $s);
        }

        // ── Customers ────────────────────────────────────────────────────────
        $customers = [
            ['name' => 'Rashid Mobile Center', 'mail' => 'rashid.mobile@email.com', 'mobile' => '0321-3001001', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Mirpur',     'openingBalance' => 0,    'businessId' => 1],
            ['name' => 'Layla Phone Shop',     'mail' => 'layla.phone@email.com',   'mobile' => '0321-3002002', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Badda',      'openingBalance' => 3500, 'businessId' => 1],
            ['name' => 'Jamil Telecom',        'mail' => 'jamil.telecom@email.com', 'mobile' => '0321-3003003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Khulshi',    'openingBalance' => 0,    'businessId' => 1],
            ['name' => 'Asma Begum',           'mail' => 'asma.begum@email.com',    'mobile' => '0321-3004004', 'country' => 'Bangladesh', 'state' => 'Rajshahi',   'city' => 'Rajshahi',   'area' => 'Uposhohor',  'openingBalance' => 1200, 'businessId' => 1],
            ['name' => 'Sajid Khan',           'mail' => 'sajid.khan@email.com',    'mobile' => '0321-3005005', 'country' => 'Bangladesh', 'state' => 'Sylhet',     'city' => 'Sylhet',     'area' => 'Subhanighat','openingBalance' => 0,    'businessId' => 1],
        ];
        foreach ($customers as $c) {
            Customer::updateOrCreate(['mail' => $c['mail']], $c);
        }

        // ── Products ─────────────────────────────────────────────────────────
        $products = [
            ['name' => 'Xiaomi Redmi Note 12 4G',     'brand' => $brand('Xiaomi'),  'category' => $cat('Smartphones'),         'unitName' => $unit('Piece'), 'quantity' => '5',  'details' => '6.67" AMOLED, 4GB/128GB, 50MP Camera',      'barCode' => 'XMI-RN12-001',     'businessId' => 1, 'stock' => 25, 'buy' => 18000, 'sell' => 22000],
            ['name' => 'Vivo Y16',                    'brand' => $brand('Vivo'),    'category' => $cat('Smartphones'),         'unitName' => $unit('Piece'), 'quantity' => '5',  'details' => '6.51" HD+, 4GB/64GB, 5000mAh',             'barCode' => 'VIV-Y16-001',      'businessId' => 1, 'stock' => 20, 'buy' => 14000, 'sell' => 17500],
            ['name' => 'Realme C55',                  'brand' => $brand('Realme'),  'category' => $cat('Smartphones'),         'unitName' => $unit('Piece'), 'quantity' => '5',  'details' => '6.72" FHD+, 8GB/256GB, 64MP Camera',       'barCode' => 'RLM-C55-001',      'businessId' => 1, 'stock' => 18, 'buy' => 20000, 'sell' => 25000],
            ['name' => 'Oppo A57',                    'brand' => $brand('Oppo'),    'category' => $cat('Smartphones'),         'unitName' => $unit('Piece'), 'quantity' => '4',  'details' => '6.56" HD+, 4GB/64GB, 50MP AI Camera',      'barCode' => 'OPP-A57-001',      'businessId' => 1, 'stock' => 15, 'buy' => 22000, 'sell' => 27000],
            ['name' => 'Tecno Spark 20',              'brand' => $brand('Tecno'),   'category' => $cat('Smartphones'),         'unitName' => $unit('Piece'), 'quantity' => '5',  'details' => '6.56" HD+, 8GB/256GB, 50MP Camera',        'barCode' => 'TEC-SP20-001',     'businessId' => 1, 'stock' => 20, 'buy' => 16000, 'sell' => 20000],
            ['name' => 'Nokia 105 (2023)',             'brand' => $brand('Nokia'),   'category' => $cat('Feature Phones'),      'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => '1.8" Display, FM Radio, 800mAh Battery',   'barCode' => 'NOK-105-2023',     'businessId' => 1, 'stock' => 50, 'buy' =>   900, 'sell' =>  1300],
            ['name' => 'Tempered Glass 5D (Pack of 5)','brand' => $brand('Generic'),'category' => $cat('Screen Protectors'),   'unitName' => $unit('Box'),   'quantity' => '20', 'details' => '5D Full Cover Tempered Glass Pack',         'barCode' => 'TG-5D-PACK-001',   'businessId' => 1, 'stock' => 80, 'buy' =>   150, 'sell' =>   300],
            ['name' => 'Phone Case Universal 6.5"',   'brand' => $brand('Generic'), 'category' => $cat('Phone Cases'),         'unitName' => $unit('Piece'), 'quantity' => '15', 'details' => 'Transparent Silicone Case for 6.5" Phones', 'barCode' => 'PC-UNV-65-001',    'businessId' => 1, 'stock' => 60, 'buy' =>   120, 'sell' =>   250],
            ['name' => '33W Fast Charger + Cable',    'brand' => $brand('Xiaomi'),  'category' => $cat('Chargers & Cables'),   'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => '33W USB-C Fast Charger with Type-C Cable',  'barCode' => 'FC-33W-001',       'businessId' => 1, 'stock' => 40, 'buy' =>   500, 'sell' =>   900],
            ['name' => '20000mAh Power Bank',         'brand' => $brand('Xiaomi'),  'category' => $cat('Power Banks'),         'unitName' => $unit('Piece'), 'quantity' => '5',  'details' => '20000mAh 18W Fast Charge, Dual USB Output', 'barCode' => 'PB-20K-001',       'businessId' => 1, 'stock' => 30, 'buy' =>  1200, 'sell' =>  2000],
            ['name' => 'TWS Wireless Earbuds',        'brand' => $brand('Realme'),  'category' => $cat('Earphones & Headsets'),'unitName' => $unit('Piece'), 'quantity' => '5',  'details' => 'True Wireless Stereo Earbuds with 30hr Battery','barCode' => 'TWS-EBD-001',   'businessId' => 1, 'stock' => 35, 'buy' =>   400, 'sell' =>   800],
            ['name' => 'Micro USB Cable (Pack of 3)', 'brand' => $brand('Generic'), 'category' => $cat('Chargers & Cables'),   'unitName' => $unit('Box'),   'quantity' => '20', 'details' => '1.5M Micro USB Data & Charging Cable Pack',  'barCode' => 'USB-MIC-P3-001',   'businessId' => 1, 'stock' => 70, 'buy' =>    90, 'sell' =>   180],
        ];

        foreach ($products as $data) {
            $stock = $data['stock'];
            $buy   = $data['buy'];
            $sell  = $data['sell'];
            unset($data['stock'], $data['buy'], $data['sell']);

            $p = Product::updateOrCreate(['barCode' => $data['barCode']], $data);
            if ($p->stocks()->sum('currentStock') == 0) {
                ProductStock::create(['productId' => $p->id, 'purchaseId' => null, 'currentStock' => $stock, 'businessId' => 1]);
            }
        }

        // ── Purchases ────────────────────────────────────────────────────────
        $pId   = fn($bc) => DB::table('products')->where('barCode', $bc)->value('id');
        $base  = Carbon::now()->subDays(50);

        $purchases = [
            [
                'productName' => $pId('XMI-RN12-001'), 'supplier' => $supId('orders@mobilezone.com'),
                'purchase_date' => $base->copy()->addDays(0)->toDateString(),  'invoice' => 'PUR-MOB-001',
                'reference' => 'REF-MOB-001', 'qty' => 25, 'buyPrice' => '18000', 'salePriceExVat' => '22000',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '24200', 'profit' => '4000',
                'totalAmount' => '450000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '450000', 'paidAmount' => '450000', 'dueAmount' => '0',
                'specialNote' => 'Xiaomi Redmi Note 12 initial stock', 'businessId' => 1,
            ],
            [
                'productName' => $pId('VIV-Y16-001'), 'supplier' => $supId('supply@handytech.com'),
                'purchase_date' => $base->copy()->addDays(3)->toDateString(), 'invoice' => 'PUR-MOB-002',
                'reference' => 'REF-MOB-002', 'qty' => 20, 'buyPrice' => '14000', 'salePriceExVat' => '17500',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '19250', 'profit' => '3500',
                'totalAmount' => '280000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '280000', 'paidAmount' => '280000', 'dueAmount' => '0',
                'specialNote' => 'Vivo Y16 batch', 'businessId' => 1,
            ],
            [
                'productName' => $pId('RLM-C55-001'), 'supplier' => $supId('import@phoneworld.com'),
                'purchase_date' => $base->copy()->addDays(7)->toDateString(), 'invoice' => 'PUR-MOB-003',
                'reference' => 'REF-MOB-003', 'qty' => 18, 'buyPrice' => '20000', 'salePriceExVat' => '25000',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '27500', 'profit' => '5000',
                'totalAmount' => '360000', 'disType' => 'flat', 'disAmount' => '5000', 'disParcent' => '0',
                'grandTotal' => '355000', 'paidAmount' => '200000', 'dueAmount' => '155000',
                'specialNote' => 'Realme C55 – partial payment', 'businessId' => 1,
            ],
            [
                'productName' => $pId('FC-33W-001'), 'supplier' => $supId('orders@mobilezone.com'),
                'purchase_date' => $base->copy()->addDays(12)->toDateString(), 'invoice' => 'PUR-MOB-004',
                'reference' => 'REF-MOB-004', 'qty' => 40, 'buyPrice' => '500', 'salePriceExVat' => '900',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '990', 'profit' => '400',
                'totalAmount' => '20000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '20000', 'paidAmount' => '20000', 'dueAmount' => '0',
                'specialNote' => '33W charger accessories lot', 'businessId' => 1,
            ],
            [
                'productName' => $pId('TWS-EBD-001'), 'supplier' => $supId('supply@handytech.com'),
                'purchase_date' => $base->copy()->addDays(18)->toDateString(), 'invoice' => 'PUR-MOB-005',
                'reference' => 'REF-MOB-005', 'qty' => 35, 'buyPrice' => '400', 'salePriceExVat' => '800',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '880', 'profit' => '400',
                'totalAmount' => '14000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '14000', 'paidAmount' => '14000', 'dueAmount' => '0',
                'specialNote' => 'TWS earbuds batch', 'businessId' => 1,
            ],
            [
                'productName' => $pId('OPP-A57-001'), 'supplier' => $supId('import@phoneworld.com'),
                'purchase_date' => $base->copy()->addDays(22)->toDateString(), 'invoice' => 'PUR-MOB-006',
                'reference' => 'REF-MOB-006', 'qty' => 15, 'buyPrice' => '22000', 'salePriceExVat' => '27000',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '29700', 'profit' => '5000',
                'totalAmount' => '330000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '330000', 'paidAmount' => '330000', 'dueAmount' => '0',
                'specialNote' => 'Oppo A57 stock', 'businessId' => 1,
            ],
        ];

        $now = now();
        foreach ($purchases as $row) {
            $existing = DB::table('purchase_products')->where('invoice', $row['invoice'])->first();
            if ($existing) {
                DB::table('purchase_products')->where('id', $existing->id)->update(array_merge($row, ['updated_at' => $now]));
                $id = $existing->id;
            } else {
                $id = DB::table('purchase_products')->insertGetId(array_merge($row, ['created_at' => $now, 'updated_at' => $now]));
            }
            $stock = ProductStock::where('productId', $row['productName'])->whereNull('purchaseId')->first();
            if ($stock) $stock->update(['purchaseId' => $id]);
        }

        // ── Sales ────────────────────────────────────────────────────────────
        $base = Carbon::now()->subDays(40);

        $sales = [
            [
                'header' => [
                    'date' => $base->copy()->addDays(2)->toDateString(), 'invoice' => 'INV-MOB-001',
                    'customerId' => $custId('rashid.mobile@email.com'), 'reference' => '',
                    'note' => 'Xiaomi Redmi Note 12 x2', 'totalSale' => '44000', 'discountAmount' => '0',
                    'grandTotal' => '44000', 'paidAmount' => '44000', 'invoiceDue' => '0',
                    'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => 1,
                ],
                'items' => [[
                    'purchaseId' => $purId('PUR-MOB-001'), 'qty' => 2, 'salePrice' => '22000', 'buyPrice' => '18000',
                    'totalSale' => '44000', 'totalPurchase' => '36000', 'profitTotal' => '8000', 'profitMargin' => '18.18', 'warranty_days' => '365',
                ]],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(6)->toDateString(), 'invoice' => 'INV-MOB-002',
                    'customerId' => $custId('layla.phone@email.com'), 'reference' => '',
                    'note' => 'Realme C55 + TWS Earbuds', 'totalSale' => '26300', 'discountAmount' => '300',
                    'grandTotal' => '26000', 'paidAmount' => '26000', 'invoiceDue' => '0',
                    'prevDue' => '3500', 'curDue' => '3500', 'status' => 'complete', 'businessId' => 1,
                ],
                'items' => [
                    [
                        'purchaseId' => $purId('PUR-MOB-003'), 'qty' => 1, 'salePrice' => '25000', 'buyPrice' => '20000',
                        'totalSale' => '25000', 'totalPurchase' => '20000', 'profitTotal' => '5000', 'profitMargin' => '20.00', 'warranty_days' => '365',
                    ],
                    [
                        'purchaseId' => $purId('PUR-MOB-005'), 'qty' => 1, 'salePrice' => '800', 'buyPrice' => '400',
                        'totalSale' => '800', 'totalPurchase' => '400', 'profitTotal' => '400', 'profitMargin' => '50.00', 'warranty_days' => null,
                    ],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(10)->toDateString(), 'invoice' => 'INV-MOB-003',
                    'customerId' => $custId('jamil.telecom@email.com'), 'reference' => '',
                    'note' => 'Vivo Y16 x3 wholesale', 'totalSale' => '52500', 'discountAmount' => '1500',
                    'grandTotal' => '51000', 'paidAmount' => '30000', 'invoiceDue' => '21000',
                    'prevDue' => '0', 'curDue' => '21000', 'status' => 'partial', 'businessId' => 1,
                ],
                'items' => [[
                    'purchaseId' => $purId('PUR-MOB-002'), 'qty' => 3, 'salePrice' => '17500', 'buyPrice' => '14000',
                    'totalSale' => '52500', 'totalPurchase' => '42000', 'profitTotal' => '10500', 'profitMargin' => '20.00', 'warranty_days' => '365',
                ]],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(15)->toDateString(), 'invoice' => 'INV-MOB-004',
                    'customerId' => $custId('asma.begum@email.com'), 'reference' => '',
                    'note' => 'Accessories – charger & earbuds', 'totalSale' => '2700', 'discountAmount' => '0',
                    'grandTotal' => '2700', 'paidAmount' => '2700', 'invoiceDue' => '0',
                    'prevDue' => '1200', 'curDue' => '1200', 'status' => 'complete', 'businessId' => 1,
                ],
                'items' => [
                    [
                        'purchaseId' => $purId('PUR-MOB-004'), 'qty' => 1, 'salePrice' => '900', 'buyPrice' => '500',
                        'totalSale' => '900', 'totalPurchase' => '500', 'profitTotal' => '400', 'profitMargin' => '44.44', 'warranty_days' => '90',
                    ],
                    [
                        'purchaseId' => $purId('PUR-MOB-005'), 'qty' => 2, 'salePrice' => '800', 'buyPrice' => '400',
                        'totalSale' => '1600', 'totalPurchase' => '800', 'profitTotal' => '800', 'profitMargin' => '50.00', 'warranty_days' => null,
                    ],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(22)->toDateString(), 'invoice' => 'INV-MOB-005',
                    'customerId' => $custId('sajid.khan@email.com'), 'reference' => '',
                    'note' => 'Oppo A57 – credit sale', 'totalSale' => '27000', 'discountAmount' => '0',
                    'grandTotal' => '27000', 'paidAmount' => '0', 'invoiceDue' => '27000',
                    'prevDue' => '0', 'curDue' => '27000', 'status' => 'due', 'businessId' => 1,
                ],
                'items' => [[
                    'purchaseId' => $purId('PUR-MOB-006'), 'qty' => 1, 'salePrice' => '27000', 'buyPrice' => '22000',
                    'totalSale' => '27000', 'totalPurchase' => '22000', 'profitTotal' => '5000', 'profitMargin' => '18.52', 'warranty_days' => '365',
                ]],
            ],
        ];

        $now = now();
        foreach ($sales as $sale) {
            $invoice = $sale['header']['invoice'];
            $existing = DB::table('sale_products')->where('invoice', $invoice)->select('id')->first();
            if ($existing) {
                DB::table('sale_products')->where('id', $existing->id)->update(array_merge($sale['header'], ['updated_at' => $now]));
                DB::table('invoice_items')->where('saleId', $existing->id)->delete();
                $saleId = $existing->id;
            } else {
                $saleId = DB::table('sale_products')->insertGetId(array_merge($sale['header'], ['created_at' => $now, 'updated_at' => $now]));
            }
            foreach ($sale['items'] as $item) {
                DB::table('invoice_items')->insert(array_merge($item, ['saleId' => $saleId, 'created_at' => $now, 'updated_at' => $now]));
            }
        }
    }
}
