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
 * Demo data for a Computer / PC Components Shop
 */
class ComputerShopSeeder extends Seeder
{
    public function run(): void
    {
        // ── Brands ──────────────────────────────────────────────────────────
        foreach (['Intel', 'AMD', 'ASUS', 'Kingston', 'Seagate', 'WD', 'TP-Link', 'Corsair', 'Cooler Master', 'Logitech'] as $b) {
            DB::table('brands')->insertOrIgnore(['name' => $b, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ── Categories ──────────────────────────────────────────────────────
        $cats = ['Processors', 'Motherboards', 'RAM', 'Storage (HDD/SSD)',
                 'Graphics Cards', 'Networking Equipment', 'PC Peripherals', 'Power Supplies', 'CPU Cooling', 'Monitors'];
        foreach ($cats as $c) {
            DB::table('categories')->insertOrIgnore(['name' => $c, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ── Helpers ──────────────────────────────────────────────────────────
        $brand   = fn(string $n) => (string)(DB::table('brands')->where('name', $n)->value('id') ?? 1);
        $cat     = fn(string $n) => (string)(DB::table('categories')->where('name', $n)->value('id') ?? 1);
        $unit    = fn(string $n) => (string)(DB::table('product_units')->where('name', $n)->value('id') ?? 1);
        $supId   = fn(string $mail) => DB::table('suppliers')->where('mail', $mail)->value('id');
        $custId  = fn(string $mail) => DB::table('customers')->where('mail', $mail)->value('id');
        $purId   = fn(string $inv)  => DB::table('purchase_products')->where('invoice', $inv)->value('id');

        // ── Suppliers ────────────────────────────────────────────────────────
        $suppliers = [
            ['name' => 'TechPoint Distributors', 'mail' => 'orders@techpointbd.com',  'mobile' => '0313-6001001', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'IDB Bhaban', 'openingBalance' => 0],
            ['name' => 'PC Components Hub',       'mail' => 'supply@pccomphub.com',    'mobile' => '0313-6002002', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Multiplan',  'openingBalance' => 15000],
            ['name' => 'Digital Traders Co',      'mail' => 'sales@digitaltraders.com','mobile' => '0313-6003003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Agrabad',    'openingBalance' => 0],
        ];
        foreach ($suppliers as $s) {
            Supplier::updateOrCreate(['mail' => $s['mail']], $s);
        }

        // ── Customers ────────────────────────────────────────────────────────
        $customers = [
            ['name' => 'CyberZone Computer',  'mail' => 'cyberzone@computer.com',    'mobile' => '0323-7001001', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Elephant Road', 'openingBalance' => 0,     'businessId' => 1],
            ['name' => 'Pixel Tech Shop',     'mail' => 'pixeltech@shop.com',        'mobile' => '0323-7002002', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Panthapath',    'openingBalance' => 7500,  'businessId' => 1],
            ['name' => 'DataSoft Solutions',  'mail' => 'datasoft@solutions.com',    'mobile' => '0323-7003003', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'BSEC',          'openingBalance' => 0,     'businessId' => 1],
            ['name' => 'Ahmed IT Center',     'mail' => 'ahmedit@center.com',        'mobile' => '0323-7004004', 'country' => 'Bangladesh', 'state' => 'Sylhet',     'city' => 'Sylhet',     'area' => 'Chouhatta',     'openingBalance' => 4000,  'businessId' => 1],
            ['name' => 'Smart Office Systems','mail' => 'smartoffice@systems.com',   'mobile' => '0323-7005005', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'GEC Circle',    'openingBalance' => 0,     'businessId' => 1],
        ];
        foreach ($customers as $c) {
            Customer::updateOrCreate(['mail' => $c['mail']], $c);
        }

        // ── Products ─────────────────────────────────────────────────────────
        $products = [
            ['name' => 'Intel Core i5-12400F',          'brand' => $brand('Intel'),        'category' => $cat('Processors'),         'unitName' => $unit('Piece'), 'quantity' => '2',  'details' => '6 Cores/12 Threads, 2.5GHz Base, 4.4GHz Boost, LGA1700',          'barCode' => 'INT-I5-12400F-001',  'businessId' => 1, 'stock' => 12, 'buy' => 20000, 'sell' => 25000],
            ['name' => 'AMD Ryzen 5 5600G',             'brand' => $brand('AMD'),          'category' => $cat('Processors'),         'unitName' => $unit('Piece'), 'quantity' => '2',  'details' => '6 Cores/12 Threads, 3.9GHz Base, Radeon Vega 7 Integrated GPU',   'barCode' => 'AMD-R5-5600G-001',   'businessId' => 1, 'stock' => 10, 'buy' => 18000, 'sell' => 22500],
            ['name' => 'ASUS Prime B660M-K Motherboard','brand' => $brand('ASUS'),         'category' => $cat('Motherboards'),       'unitName' => $unit('Piece'), 'quantity' => '2',  'details' => 'LGA1700, DDR4, PCIe 4.0, USB 3.2, Micro-ATX',                     'barCode' => 'ASU-B660MK-001',     'businessId' => 1, 'stock' => 8,  'buy' => 14000, 'sell' => 18000],
            ['name' => 'Kingston 8GB DDR4 3200MHz',     'brand' => $brand('Kingston'),     'category' => $cat('RAM'),                'unitName' => $unit('Piece'), 'quantity' => '5',  'details' => 'ValueRAM 8GB DDR4 3200MHz CL22 DIMM Desktop Memory',               'barCode' => 'KNG-DDR4-8G-001',    'businessId' => 1, 'stock' => 30, 'buy' =>  3500, 'sell' =>  4800],
            ['name' => 'Seagate 1TB Barracuda HDD',     'brand' => $brand('Seagate'),      'category' => $cat('Storage (HDD/SSD)'), 'unitName' => $unit('Piece'), 'quantity' => '3',  'details' => '1TB 7200RPM 3.5" SATA III Internal Hard Drive',                    'barCode' => 'SEA-1TB-BRC-001',    'businessId' => 1, 'stock' => 20, 'buy' =>  5000, 'sell' =>  6500],
            ['name' => 'WD 480GB Green SSD',            'brand' => $brand('WD'),           'category' => $cat('Storage (HDD/SSD)'), 'unitName' => $unit('Piece'), 'quantity' => '3',  'details' => '480GB 2.5" SATA SSD, 545MB/s Read Speed',                          'barCode' => 'WD-480-GRN-001',     'businessId' => 1, 'stock' => 18, 'buy' =>  4200, 'sell' =>  5800],
            ['name' => 'TP-Link Archer C6 Router',      'brand' => $brand('TP-Link'),      'category' => $cat('Networking Equipment'),'unitName' => $unit('Piece'),'quantity' => '3',  'details' => 'AC1200 Dual Band Wi-Fi Router, 4x LAN, 1x WAN',                    'barCode' => 'TPL-C6-001',         'businessId' => 1, 'stock' => 15, 'buy' =>  2200, 'sell' =>  3500],
            ['name' => 'Corsair CV650 650W PSU',        'brand' => $brand('Corsair'),      'category' => $cat('Power Supplies'),    'unitName' => $unit('Piece'), 'quantity' => '2',  'details' => '650W 80+ Bronze ATX Power Supply, Single Rail',                     'barCode' => 'CRS-CV650-001',      'businessId' => 1, 'stock' => 12, 'buy' =>  5500, 'sell' =>  7200],
            ['name' => 'Logitech G102 Gaming Mouse',    'brand' => $brand('Logitech'),     'category' => $cat('PC Peripherals'),    'unitName' => $unit('Piece'), 'quantity' => '5',  'details' => 'Wired Gaming Mouse, 200–8000 DPI, RGB, 6 Buttons',                 'barCode' => 'LOG-G102-001',       'businessId' => 1, 'stock' => 25, 'buy' =>  1400, 'sell' =>  2000],
            ['name' => 'Mechanical Keyboard Blue Switch','brand' => $brand('Generic'),     'category' => $cat('PC Peripherals'),    'unitName' => $unit('Piece'), 'quantity' => '3',  'details' => 'TKL 87-Key Mechanical Keyboard Blue Tactile Switch, RGB',           'barCode' => 'MK-BLUE-001',        'businessId' => 1, 'stock' => 15, 'buy' =>  2000, 'sell' =>  3200],
            ['name' => 'Cooler Master Hyper 212 Black', 'brand' => $brand('Cooler Master'),'category' => $cat('CPU Cooling'),       'unitName' => $unit('Piece'), 'quantity' => '2',  'details' => 'CPU Air Cooler, 120mm PWM Fan, TDP 150W, Intel & AMD Compatible',   'barCode' => 'CM-212BLK-001',      'businessId' => 1, 'stock' => 10, 'buy' =>  3000, 'sell' =>  4200],
            ['name' => '24" FHD Monitor 75Hz',          'brand' => $brand('Generic'),      'category' => $cat('Monitors'),          'unitName' => $unit('Piece'), 'quantity' => '2',  'details' => '24 Inch Full HD IPS Panel 75Hz, HDMI+VGA, Slim Bezel',             'barCode' => 'MON-24FHD-001',      'businessId' => 1, 'stock' => 10, 'buy' => 14000, 'sell' => 18500],
        ];

        foreach ($products as $data) {
            $stock = $data['stock']; unset($data['stock'], $data['buy'], $data['sell']);
            $p = Product::updateOrCreate(['barCode' => $data['barCode']], $data);
            if ($p->stocks()->sum('currentStock') == 0) {
                ProductStock::create(['productId' => $p->id, 'purchaseId' => null, 'currentStock' => $stock, 'businessId' => 1]);
            }
        }

        // ── Purchases ────────────────────────────────────────────────────────
        $pId  = fn($bc) => DB::table('products')->where('barCode', $bc)->value('id');
        $base = Carbon::now()->subDays(48);

        $purchases = [
            [
                'productName' => $pId('INT-I5-12400F-001'), 'supplier' => $supId('orders@techpointbd.com'),
                'purchase_date' => $base->copy()->addDays(0)->toDateString(), 'invoice' => 'PUR-COM-001',
                'reference' => 'REF-COM-001', 'qty' => 12, 'buyPrice' => '20000', 'salePriceExVat' => '25000',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '27500', 'profit' => '5000',
                'totalAmount' => '240000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '240000', 'paidAmount' => '240000', 'dueAmount' => '0',
                'specialNote' => 'Intel i5-12400F initial stock', 'businessId' => 1,
            ],
            [
                'productName' => $pId('KNG-DDR4-8G-001'), 'supplier' => $supId('supply@pccomphub.com'),
                'purchase_date' => $base->copy()->addDays(5)->toDateString(), 'invoice' => 'PUR-COM-002',
                'reference' => 'REF-COM-002', 'qty' => 30, 'buyPrice' => '3500', 'salePriceExVat' => '4800',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '5280', 'profit' => '1300',
                'totalAmount' => '105000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '105000', 'paidAmount' => '105000', 'dueAmount' => '0',
                'specialNote' => 'Kingston RAM lot', 'businessId' => 1,
            ],
            [
                'productName' => $pId('SEA-1TB-BRC-001'), 'supplier' => $supId('sales@digitaltraders.com'),
                'purchase_date' => $base->copy()->addDays(10)->toDateString(), 'invoice' => 'PUR-COM-003',
                'reference' => 'REF-COM-003', 'qty' => 20, 'buyPrice' => '5000', 'salePriceExVat' => '6500',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '7150', 'profit' => '1500',
                'totalAmount' => '100000', 'disType' => 'flat', 'disAmount' => '5000', 'disParcent' => '0',
                'grandTotal' => '95000', 'paidAmount' => '50000', 'dueAmount' => '45000',
                'specialNote' => 'Seagate HDD batch – partial payment', 'businessId' => 1,
            ],
            [
                'productName' => $pId('MON-24FHD-001'), 'supplier' => $supId('orders@techpointbd.com'),
                'purchase_date' => $base->copy()->addDays(15)->toDateString(), 'invoice' => 'PUR-COM-004',
                'reference' => 'REF-COM-004', 'qty' => 10, 'buyPrice' => '14000', 'salePriceExVat' => '18500',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '20350', 'profit' => '4500',
                'totalAmount' => '140000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '140000', 'paidAmount' => '140000', 'dueAmount' => '0',
                'specialNote' => '24" FHD Monitors stock', 'businessId' => 1,
            ],
            [
                'productName' => $pId('TPL-C6-001'), 'supplier' => $supId('supply@pccomphub.com'),
                'purchase_date' => $base->copy()->addDays(20)->toDateString(), 'invoice' => 'PUR-COM-005',
                'reference' => 'REF-COM-005', 'qty' => 15, 'buyPrice' => '2200', 'salePriceExVat' => '3500',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '3850', 'profit' => '1300',
                'totalAmount' => '33000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '33000', 'paidAmount' => '33000', 'dueAmount' => '0',
                'specialNote' => 'TP-Link router batch', 'businessId' => 1,
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
        $base = Carbon::now()->subDays(38);

        $sales = [
            [
                'header' => [
                    'date' => $base->copy()->addDays(2)->toDateString(), 'invoice' => 'INV-COM-001',
                    'customerId' => $custId('cyberzone@computer.com'), 'reference' => '',
                    'note' => 'Full PC build – CPU + RAM + Storage', 'totalSale' => '37100', 'discountAmount' => '0',
                    'grandTotal' => '37100', 'paidAmount' => '37100', 'invoiceDue' => '0',
                    'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => 1,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-COM-001'), 'qty' => 1, 'salePrice' => '25000', 'buyPrice' => '20000', 'totalSale' => '25000', 'totalPurchase' => '20000', 'profitTotal' => '5000', 'profitMargin' => '20.00', 'warranty_days' => '365'],
                    ['purchaseId' => $purId('PUR-COM-002'), 'qty' => 2, 'salePrice' => '4800',  'buyPrice' => '3500',  'totalSale' => '9600',  'totalPurchase' => '7000',  'profitTotal' => '2600', 'profitMargin' => '27.08', 'warranty_days' => '365'],
                    ['purchaseId' => $purId('PUR-COM-003'), 'qty' => 1, 'salePrice' => '6500',  'buyPrice' => '5000',  'totalSale' => '6500',  'totalPurchase' => '5000',  'profitTotal' => '1500', 'profitMargin' => '23.08', 'warranty_days' => '365'],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(7)->toDateString(), 'invoice' => 'INV-COM-002',
                    'customerId' => $custId('pixeltech@shop.com'), 'reference' => '',
                    'note' => '2x Monitors + Router', 'totalSale' => '40500', 'discountAmount' => '500',
                    'grandTotal' => '40000', 'paidAmount' => '40000', 'invoiceDue' => '0',
                    'prevDue' => '7500', 'curDue' => '7500', 'status' => 'complete', 'businessId' => 1,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-COM-004'), 'qty' => 2, 'salePrice' => '18500', 'buyPrice' => '14000', 'totalSale' => '37000', 'totalPurchase' => '28000', 'profitTotal' => '9000', 'profitMargin' => '24.32', 'warranty_days' => '365'],
                    ['purchaseId' => $purId('PUR-COM-005'), 'qty' => 1, 'salePrice' => '3500',  'buyPrice' => '2200',  'totalSale' => '3500',  'totalPurchase' => '2200',  'profitTotal' => '1300', 'profitMargin' => '37.14', 'warranty_days' => '365'],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(13)->toDateString(), 'invoice' => 'INV-COM-003',
                    'customerId' => $custId('datasoft@solutions.com'), 'reference' => '',
                    'note' => 'Office setup – RAM upgrade x10', 'totalSale' => '48000', 'discountAmount' => '3000',
                    'grandTotal' => '45000', 'paidAmount' => '45000', 'invoiceDue' => '0',
                    'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => 1,
                ],
                'items' => [[
                    'purchaseId' => $purId('PUR-COM-002'), 'qty' => 10, 'salePrice' => '4800', 'buyPrice' => '3500',
                    'totalSale' => '48000', 'totalPurchase' => '35000', 'profitTotal' => '13000', 'profitMargin' => '27.08', 'warranty_days' => '365',
                ]],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(20)->toDateString(), 'invoice' => 'INV-COM-004',
                    'customerId' => $custId('ahmedit@center.com'), 'reference' => '',
                    'note' => 'HDD + SSD + Peripherals', 'totalSale' => '18300', 'discountAmount' => '0',
                    'grandTotal' => '18300', 'paidAmount' => '10000', 'invoiceDue' => '8300',
                    'prevDue' => '4000', 'curDue' => '12300', 'status' => 'partial', 'businessId' => 1,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-COM-003'), 'qty' => 1, 'salePrice' => '6500', 'buyPrice' => '5000', 'totalSale' => '6500',  'totalPurchase' => '5000',  'profitTotal' => '1500', 'profitMargin' => '23.08', 'warranty_days' => '365'],
                    ['purchaseId' => null,                  'qty' => 1, 'salePrice' => '5800', 'buyPrice' => '4200', 'totalSale' => '5800',  'totalPurchase' => '4200',  'profitTotal' => '1600', 'profitMargin' => '27.59', 'warranty_days' => '365'],
                    ['purchaseId' => null,                  'qty' => 3, 'salePrice' => '2000', 'buyPrice' => '1400', 'totalSale' => '6000',  'totalPurchase' => '4200',  'profitTotal' => '1800', 'profitMargin' => '30.00', 'warranty_days' => '90'],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(28)->toDateString(), 'invoice' => 'INV-COM-005',
                    'customerId' => $custId('smartoffice@systems.com'), 'reference' => '',
                    'note' => 'Office routers bulk – credit', 'totalSale' => '17500', 'discountAmount' => '0',
                    'grandTotal' => '17500', 'paidAmount' => '0', 'invoiceDue' => '17500',
                    'prevDue' => '0', 'curDue' => '17500', 'status' => 'due', 'businessId' => 1,
                ],
                'items' => [[
                    'purchaseId' => $purId('PUR-COM-005'), 'qty' => 5, 'salePrice' => '3500', 'buyPrice' => '2200',
                    'totalSale' => '17500', 'totalPurchase' => '11000', 'profitTotal' => '6500', 'profitMargin' => '37.14', 'warranty_days' => '365',
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
