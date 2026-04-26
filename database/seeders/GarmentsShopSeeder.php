<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Supplier;
use App\Models\Customer;
use Carbon\Carbon;
use Database\Seeders\Concerns\BusinessTypeSeedable;

/**
 * Demo data for a Garments / Clothing & Fabric Sales Shop
 */
class GarmentsShopSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $bid = $this->businessId;

        // ── Brands ──────────────────────────────────────────────────────────
        foreach (['Cotton Comfort', 'Linen House', 'Dhaka Textile', 'FashionWear', 'Classic Cotton', 'Premium Fabric'] as $b) {
            DB::table('brands')->insertOrIgnore(['name' => $b, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ── Categories ──────────────────────────────────────────────────────
        $cats = ["Men's Wear", "Women's Wear", "Kids Wear", 'Traditional Wear',
                 'Fabric & Cloth', 'Undergarments', 'Formal & Office Wear'];
        foreach ($cats as $c) {
            DB::table('categories')->insertOrIgnore(['name' => $c, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ── Units (add Yard for fabric) ──────────────────────────────────────
        DB::table('product_units')->insertOrIgnore(['name' => 'Yard', 'created_at' => now(), 'updated_at' => now()]);

        // ── Helpers ──────────────────────────────────────────────────────────
        $brand   = fn(string $n) => (string)(DB::table('brands')->where('name', $n)->value('id') ?? 1);
        $cat     = fn(string $n) => (string)(DB::table('categories')->where('name', $n)->value('id') ?? 1);
        $unit    = fn(string $n) => (string)(DB::table('product_units')->where('name', $n)->value('id') ?? 1);
        $supId   = fn(string $mail) => DB::table('suppliers')->where('businessId', $bid)->where('mail', $mail)->value('id');
        $custId  = fn(string $mail) => DB::table('customers')->where('businessId', $bid)->where('mail', $mail)->value('id');
        $purId   = fn(string $inv)  => DB::table('purchase_products')->where('businessId', $bid)->where('invoice', $inv)->value('id');

        // ── Suppliers ────────────────────────────────────────────────────────
        $suppliers = [
            ['name' => 'Dhaka Textile Mills',  'mail' => 'orders@dhakatextile.com',  'mobile' => '0315-1101001', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Tejgaon',    'openingBalance' => 0],
            ['name' => 'FabricWorld Traders',  'mail' => 'supply@fabricworld.com',   'mobile' => '0315-1102002', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Narayanganj','area' => 'Fatullah',   'openingBalance' => 20000],
            ['name' => 'Garments Source BD',   'mail' => 'sales@garmentsource.com',  'mobile' => '0315-1103003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'CEPZ',       'openingBalance' => 0],
        ];
        foreach ($suppliers as $s) {
            $s['businessId'] = $bid;
            Supplier::updateOrCreate(['mail' => $s['mail'], 'businessId' => $bid], $s);
        }

        // ── Customers ────────────────────────────────────────────────────────
        $customers = [
            ['name' => 'Fashion Hub Retail',  'mail' => 'fashionhub@retail.com',    'mobile' => '0325-1201001', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Dhanmondi',  'openingBalance' => 0,    'businessId' => $bid],
            ['name' => 'Style Zone Shop',     'mail' => 'stylezone@shop.com',       'mobile' => '0325-1202002', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Gulshan',    'openingBalance' => 6000, 'businessId' => $bid],
            ['name' => 'Trend Wear Store',    'mail' => 'trendwear@store.com',      'mobile' => '0325-1203003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Nasirabad',  'openingBalance' => 0,    'businessId' => $bid],
            ['name' => 'Layla Fashion',       'mail' => 'layla.fashion@email.com',  'mobile' => '0325-1204004', 'country' => 'Bangladesh', 'state' => 'Sylhet',     'city' => 'Sylhet',     'area' => 'Bondor',     'openingBalance' => 2500, 'businessId' => $bid],
            ['name' => 'Modern Boutique',     'mail' => 'modern.boutique@email.com','mobile' => '0325-1205005', 'country' => 'Bangladesh', 'state' => 'Rajshahi',   'city' => 'Rajshahi',   'area' => 'New Market', 'openingBalance' => 0,    'businessId' => $bid],
        ];
        foreach ($customers as $c) {
            $c['businessId'] = $bid;
            Customer::updateOrCreate(['mail' => $c['mail'], 'businessId' => $bid], $c);
        }

        // ── Products ─────────────────────────────────────────────────────────
        $products = [
            ['name' => "Men's Formal Shirt White (L)",     'brand' => $brand('Cotton Comfort'), 'category' => $cat("Men's Wear"),          'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => '100% Cotton Formal Dress Shirt White Size L',                  'barCode' => 'MS-FRM-WHT-L-001',  'businessId' => $bid, 'stock' => 50,  'buy' =>  350, 'sell' =>  650],
            ['name' => "Men's Formal Shirt Blue (L)",      'brand' => $brand('Cotton Comfort'), 'category' => $cat("Men's Wear"),          'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => '100% Cotton Formal Dress Shirt Sky Blue Size L',               'barCode' => 'MS-FRM-BLU-L-001',  'businessId' => $bid, 'stock' => 45,  'buy' =>  350, 'sell' =>  650],
            ['name' => "Men's Casual Polo T-Shirt (M)",    'brand' => $brand('FashionWear'),    'category' => $cat("Men's Wear"),          'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => 'Pique Cotton Polo T-Shirt Short Sleeve Size M',                 'barCode' => 'MS-PLO-M-001',      'businessId' => $bid, 'stock' => 60,  'buy' =>  250, 'sell' =>  480],
            ['name' => 'Denim Jeans Men 32"',              'brand' => $brand('Classic Cotton'), 'category' => $cat("Men's Wear"),          'unitName' => $unit('Piece'), 'quantity' => '5',  'details' => 'Slim Fit Stretch Denim Jeans Waist 32 Inches',                  'barCode' => 'DN-JNS-32-001',     'businessId' => $bid, 'stock' => 40,  'buy' =>  600, 'sell' => 1100],
            ["name" => "Women's Salwar Kameez Set (M)",    'brand' => $brand('Linen House'),    'category' => $cat("Women's Wear"),        'unitName' => $unit('Set'),   'quantity' => '10', 'details' => 'Printed Salwar Kameez 3-Piece Set Cotton Blend Size M',          'barCode' => 'WS-SK-M-001',       'businessId' => $bid, 'stock' => 50,  'buy' =>  500, 'sell' =>  950],
            ['name' => 'Ladies Printed Kurti (L)',         'brand' => $brand('FashionWear'),    'category' => $cat("Women's Wear"),        'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => 'Floral Block Print Kurti Knee Length Cotton Size L',             'barCode' => 'WS-KRT-L-001',      'businessId' => $bid, 'stock' => 55,  'buy' =>  350, 'sell' =>  680],
            ['name' => 'Kids Cotton T-Shirt (Age 5-6)',    'brand' => $brand('Cotton Comfort'), 'category' => $cat("Kids Wear"),           'unitName' => $unit('Piece'), 'quantity' => '20', 'details' => '100% Soft Cotton Kids T-Shirt Age 5-6 Years Assorted Colors',   'barCode' => 'KT-CTN-56-001',     'businessId' => $bid, 'stock' => 80,  'buy' =>  150, 'sell' =>  300],
            ['name' => 'Boys School Pants (Age 8-10)',     'brand' => $brand('Classic Cotton'), 'category' => $cat("Kids Wear"),           'unitName' => $unit('Piece'), 'quantity' => '15', 'details' => 'Dark Navy School Uniform Pants Age 8-10 Years',                 'barCode' => 'KB-PANT-810-001',   'businessId' => $bid, 'stock' => 60,  'buy' =>  250, 'sell' =>  500],
            ['name' => 'Traditional Panjabi (L)',          'brand' => $brand('Dhaka Textile'),  'category' => $cat("Traditional Wear"),    'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => 'Premium Cotton Embroidered Panjabi Eid/Festival Size L',         'barCode' => 'TR-PNJ-L-001',      'businessId' => $bid, 'stock' => 35,  'buy' =>  400, 'sell' =>  750],
            ['name' => 'Cotton Fabric per Yard',           'brand' => $brand('Dhaka Textile'),  'category' => $cat("Fabric & Cloth"),      'unitName' => $unit('Yard'),  'quantity' => '50', 'details' => '40-count Woven Cotton Fabric 44" Width Per Yard',               'barCode' => 'FAB-CTN-YD-001',    'businessId' => $bid, 'stock' => 500, 'buy' =>   80, 'sell' =>  150],
            ['name' => 'Premium Silk Fabric per Yard',     'brand' => $brand('Premium Fabric'), 'category' => $cat("Fabric & Cloth"),      'unitName' => $unit('Yard'),  'quantity' => '20', 'details' => 'Pure Mulberry Silk Fabric 44" Width Smooth Finish Per Yard',    'barCode' => 'FAB-SLK-YD-001',    'businessId' => $bid, 'stock' => 200, 'buy' =>  350, 'sell' =>  650],
            ["name" => "Men's Undergarment Set",           'brand' => $brand('Cotton Comfort'), 'category' => $cat("Undergarments"),       'unitName' => $unit('Set'),   'quantity' => '20', 'details' => "Men's 100% Cotton Boxer + Vest Combo Set Free Size",             'barCode' => 'UG-MEN-SET-001',    'businessId' => $bid, 'stock' => 70,  'buy' =>  150, 'sell' =>  350],
        ];

        foreach ($products as $data) {
            $stock = $data['stock']; unset($data['stock'], $data['buy'], $data['sell']);
            $p = Product::updateOrCreate(['barCode' => $data['barCode'], 'businessId' => $bid], $data);
            if ($p->stocks()->sum('currentStock') == 0) {
                ProductStock::create(['productId' => $p->id, 'purchaseId' => null, 'currentStock' => $stock, 'businessId' => $bid]);
            }
        }

        // ── Purchases ────────────────────────────────────────────────────────
        $pId  = fn($bc) => DB::table('products')->where('businessId', $bid)->where('barCode', $bc)->value('id');
        $base = Carbon::now()->subDays(45);

        $purchases = [
            [
                'productName' => $pId('MS-FRM-WHT-L-001'), 'supplier' => $supId('orders@dhakatextile.com'),
                'purchase_date' => $base->copy()->addDays(0)->toDateString(), 'invoice' => 'PUR-GAR-001',
                'reference' => 'REF-GAR-001', 'qty' => 50, 'buyPrice' => '350', 'salePriceExVat' => '650',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '715', 'profit' => '300',
                'totalAmount' => '17500', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '17500', 'paidAmount' => '17500', 'dueAmount' => '0',
                'specialNote' => 'White formal shirts initial stock', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('WS-SK-M-001'), 'supplier' => $supId('supply@fabricworld.com'),
                'purchase_date' => $base->copy()->addDays(5)->toDateString(), 'invoice' => 'PUR-GAR-002',
                'reference' => 'REF-GAR-002', 'qty' => 50, 'buyPrice' => '500', 'salePriceExVat' => '950',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '1045', 'profit' => '450',
                'totalAmount' => '25000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '25000', 'paidAmount' => '15000', 'dueAmount' => '10000',
                'specialNote' => 'Salwar kameez sets – partial payment', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('FAB-CTN-YD-001'), 'supplier' => $supId('orders@dhakatextile.com'),
                'purchase_date' => $base->copy()->addDays(10)->toDateString(), 'invoice' => 'PUR-GAR-003',
                'reference' => 'REF-GAR-003', 'qty' => 500, 'buyPrice' => '80', 'salePriceExVat' => '150',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '165', 'profit' => '70',
                'totalAmount' => '40000', 'disType' => 'flat', 'disAmount' => '2000', 'disParcent' => '0',
                'grandTotal' => '38000', 'paidAmount' => '38000', 'dueAmount' => '0',
                'specialNote' => 'Cotton fabric bulk lot', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('TR-PNJ-L-001'), 'supplier' => $supId('sales@garmentsource.com'),
                'purchase_date' => $base->copy()->addDays(15)->toDateString(), 'invoice' => 'PUR-GAR-004',
                'reference' => 'REF-GAR-004', 'qty' => 35, 'buyPrice' => '400', 'salePriceExVat' => '750',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '825', 'profit' => '350',
                'totalAmount' => '14000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '14000', 'paidAmount' => '14000', 'dueAmount' => '0',
                'specialNote' => 'Traditional Panjabi stock for Eid', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('DN-JNS-32-001'), 'supplier' => $supId('supply@fabricworld.com'),
                'purchase_date' => $base->copy()->addDays(20)->toDateString(), 'invoice' => 'PUR-GAR-005',
                'reference' => 'REF-GAR-005', 'qty' => 40, 'buyPrice' => '600', 'salePriceExVat' => '1100',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '1210', 'profit' => '500',
                'totalAmount' => '24000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '24000', 'paidAmount' => '24000', 'dueAmount' => '0',
                'specialNote' => 'Denim jeans stock', 'businessId' => $bid,
            ],
        ];

        $now = now();
        foreach ($purchases as $row) {
            $existing = DB::table('purchase_products')->where('businessId', $bid)->where('invoice', $row['invoice'])->first();
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
        $base = Carbon::now()->subDays(35);

        $sales = [
            [
                'header' => [
                    'date' => $base->copy()->addDays(2)->toDateString(), 'invoice' => 'INV-GAR-001',
                    'customerId' => $custId('fashionhub@retail.com'), 'reference' => '',
                    'note' => 'Formal shirts x10 + Polo shirts x10 bulk', 'totalSale' => '11300', 'discountAmount' => '300',
                    'grandTotal' => '11000', 'paidAmount' => '11000', 'invoiceDue' => '0',
                    'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => $bid,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-GAR-001'), 'qty' => 10, 'salePrice' => '650', 'buyPrice' => '350', 'totalSale' => '6500', 'totalPurchase' => '3500', 'profitTotal' => '3000', 'profitMargin' => '46.15', 'warranty_days' => null],
                    ['purchaseId' => null,                  'qty' => 10, 'salePrice' => '480', 'buyPrice' => '250', 'totalSale' => '4800', 'totalPurchase' => '2500', 'profitTotal' => '2300', 'profitMargin' => '47.92', 'warranty_days' => null],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(6)->toDateString(), 'invoice' => 'INV-GAR-002',
                    'customerId' => $custId('stylezone@shop.com'), 'reference' => '',
                    'note' => "Women's sets x5 + Kurti x5", 'totalSale' => '8150', 'discountAmount' => '650',
                    'grandTotal' => '7500', 'paidAmount' => '7500', 'invoiceDue' => '0',
                    'prevDue' => '6000', 'curDue' => '6000', 'status' => 'complete', 'businessId' => $bid,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-GAR-002'), 'qty' => 5, 'salePrice' => '950', 'buyPrice' => '500', 'totalSale' => '4750', 'totalPurchase' => '2500', 'profitTotal' => '2250', 'profitMargin' => '47.37', 'warranty_days' => null],
                    ['purchaseId' => null,                  'qty' => 5, 'salePrice' => '680', 'buyPrice' => '350', 'totalSale' => '3400', 'totalPurchase' => '1750', 'profitTotal' => '1650', 'profitMargin' => '48.53', 'warranty_days' => null],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(12)->toDateString(), 'invoice' => 'INV-GAR-003',
                    'customerId' => $custId('trendwear@store.com'), 'reference' => '',
                    'note' => 'Panjabi Eid collection x15', 'totalSale' => '11250', 'discountAmount' => '750',
                    'grandTotal' => '10500', 'paidAmount' => '6000', 'invoiceDue' => '4500',
                    'prevDue' => '0', 'curDue' => '4500', 'status' => 'partial', 'businessId' => $bid,
                ],
                'items' => [[
                    'purchaseId' => $purId('PUR-GAR-004'), 'qty' => 15, 'salePrice' => '750', 'buyPrice' => '400',
                    'totalSale' => '11250', 'totalPurchase' => '6000', 'profitTotal' => '5250', 'profitMargin' => '46.67', 'warranty_days' => null,
                ]],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(18)->toDateString(), 'invoice' => 'INV-GAR-004',
                    'customerId' => $custId('layla.fashion@email.com'), 'reference' => '',
                    'note' => 'Fabric per yard x100 + Kids T-shirts', 'totalSale' => '17000', 'discountAmount' => '0',
                    'grandTotal' => '17000', 'paidAmount' => '17000', 'invoiceDue' => '0',
                    'prevDue' => '2500', 'curDue' => '2500', 'status' => 'complete', 'businessId' => $bid,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-GAR-003'), 'qty' => 100,'salePrice' => '150', 'buyPrice' => '80',  'totalSale' => '15000', 'totalPurchase' => '8000', 'profitTotal' => '7000', 'profitMargin' => '46.67', 'warranty_days' => null],
                    ['purchaseId' => null,                  'qty' => 20, 'salePrice' => '300', 'buyPrice' => '150', 'totalSale' => '6000',  'totalPurchase' => '3000', 'profitTotal' => '3000', 'profitMargin' => '50.00', 'warranty_days' => null],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(26)->toDateString(), 'invoice' => 'INV-GAR-005',
                    'customerId' => $custId('modern.boutique@email.com'), 'reference' => '',
                    'note' => 'Denim jeans x8 credit order', 'totalSale' => '8800', 'discountAmount' => '0',
                    'grandTotal' => '8800', 'paidAmount' => '0', 'invoiceDue' => '8800',
                    'prevDue' => '0', 'curDue' => '8800', 'status' => 'due', 'businessId' => $bid,
                ],
                'items' => [[
                    'purchaseId' => $purId('PUR-GAR-005'), 'qty' => 8, 'salePrice' => '1100', 'buyPrice' => '600',
                    'totalSale' => '8800', 'totalPurchase' => '4800', 'profitTotal' => '4000', 'profitMargin' => '45.45', 'warranty_days' => null,
                ]],
            ],
        ];

        $now = now();
        foreach ($sales as $sale) {
            $invoice = $sale['header']['invoice'];
            $existing = DB::table('sale_products')->where('businessId', $bid)->where('invoice', $invoice)->select('id')->first();
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




