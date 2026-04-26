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
 * Demo data for a Vehicle / Auto Parts Shop
 */
class VehicleShopSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $bid = $this->businessId;

        // ── Brands ──────────────────────────────────────────────────────────
        foreach (['Toyota', 'Honda', 'Yamaha', 'Bajaj', 'TVS', 'Bosch', 'NGK', 'Castrol'] as $b) {
            DB::table('brands')->insertOrIgnore(['name' => $b, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ── Categories ──────────────────────────────────────────────────────
        $cats = ['Engine Parts', 'Suspension & Brakes', 'Electrical Parts',
                 'Body & Exterior', 'Lubricants & Fluids', 'Tyres & Tubes', 'Batteries', 'Filters'];
        foreach ($cats as $c) {
            DB::table('categories')->insertOrIgnore(['name' => $c, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ── Helpers ──────────────────────────────────────────────────────────
        $brand   = fn(string $n) => (string)(DB::table('brands')->where('name', $n)->value('id') ?? 1);
        $cat     = fn(string $n) => (string)(DB::table('categories')->where('name', $n)->value('id') ?? 1);
        $unit    = fn(string $n) => (string)(DB::table('product_units')->where('name', $n)->value('id') ?? 1);
        $supId   = fn(string $mail) => DB::table('suppliers')->where('businessId', $bid)->where('mail', $mail)->value('id');
        $custId  = fn(string $mail) => DB::table('customers')->where('businessId', $bid)->where('mail', $mail)->value('id');
        $purId   = fn(string $inv)  => DB::table('purchase_products')->where('businessId', $bid)->where('invoice', $inv)->value('id');

        // ── Suppliers ────────────────────────────────────────────────────────
        $suppliers = [
            ['name' => 'AutoParts Depot',    'mail' => 'orders@autopartsdepot.com', 'mobile' => '0312-4001001', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Gabtoli',    'openingBalance' => 0],
            ['name' => 'Moto Supply House',  'mail' => 'supply@motosupply.com',     'mobile' => '0312-4002002', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Kalurghat',  'openingBalance' => 12000],
            ['name' => 'VehiclePro Traders', 'mail' => 'sales@vehiclepro.com',      'mobile' => '0312-4003003', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Tejgaon',    'openingBalance' => 0],
        ];
        foreach ($suppliers as $s) {
            $s['businessId'] = $bid;
            Supplier::updateOrCreate(['mail' => $s['mail'], 'businessId' => $bid], $s);
        }

        // ── Customers ────────────────────────────────────────────────────────
        $customers = [
            ['name' => 'Karim Auto Workshop', 'mail' => 'karim.auto@email.com',      'mobile' => '0322-5001001', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Badda',       'openingBalance' => 0,    'businessId' => $bid],
            ['name' => 'Ali Motors Garage',   'mail' => 'ali.motors@email.com',      'mobile' => '0322-5002002', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Demra',       'openingBalance' => 5000, 'businessId' => $bid],
            ['name' => 'Bike Care Center',    'mail' => 'bikecare.center@email.com', 'mobile' => '0322-5003003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Fatikchhari', 'openingBalance' => 0,    'businessId' => $bid],
            ['name' => 'Malik Transport Co',  'mail' => 'malik.transport@email.com', 'mobile' => '0322-5004004', 'country' => 'Bangladesh', 'state' => 'Rajshahi',   'city' => 'Rajshahi',   'area' => 'Rajpara',     'openingBalance' => 2500, 'businessId' => $bid],
            ['name' => 'Noor Vehicles BD',    'mail' => 'noor.vehicles@email.com',   'mobile' => '0322-5005005', 'country' => 'Bangladesh', 'state' => 'Khulna',     'city' => 'Khulna',     'area' => 'Batiaghata',  'openingBalance' => 0,    'businessId' => $bid],
        ];
        foreach ($customers as $c) {
            $c['businessId'] = $bid;
            Customer::updateOrCreate(['mail' => $c['mail'], 'businessId' => $bid], $c);
        }

        // ── Products ─────────────────────────────────────────────────────────
        $products = [
            ['name' => 'NGK Spark Plug BPR6ES',          'brand' => $brand('NGK'),     'category' => $cat('Engine Parts'),        'unitName' => $unit('Piece'), 'quantity' => '20', 'details' => 'Standard Resistor Spark Plug for most 4-stroke engines',     'barCode' => 'NGK-BPR6ES-001',    'businessId' => $bid, 'stock' => 60, 'buy' =>  350,  'sell' =>  600],
            ['name' => 'Bosch Wiper Blade A26F',          'brand' => $brand('Bosch'),   'category' => $cat('Body & Exterior'),     'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => '26" Aerotwin Front Wiper Blade',                              'barCode' => 'BSH-WPR-A26F-001',  'businessId' => $bid, 'stock' => 40, 'buy' =>  600,  'sell' => 1000],
            ['name' => 'Honda CD70 Chain Sprocket Kit',   'brand' => $brand('Honda'),   'category' => $cat('Engine Parts'),        'unitName' => $unit('Set'),   'quantity' => '10', 'details' => 'Chain + Front & Rear Sprocket for Honda CD70',               'barCode' => 'HON-CD70-CSK-001',  'businessId' => $bid, 'stock' => 35, 'buy' =>  800,  'sell' => 1400],
            ['name' => 'Yamaha FZS Clutch Cable',         'brand' => $brand('Yamaha'),  'category' => $cat('Engine Parts'),        'unitName' => $unit('Piece'), 'quantity' => '15', 'details' => 'Original Clutch Cable for Yamaha FZS Series',                'barCode' => 'YAM-FZS-CC-001',    'businessId' => $bid, 'stock' => 50, 'buy' =>  250,  'sell' =>  450],
            ['name' => 'Bajaj Pulsar 150 Brake Pad Set',  'brand' => $brand('Bajaj'),   'category' => $cat('Suspension & Brakes'), 'unitName' => $unit('Set'),   'quantity' => '10', 'details' => 'Front & Rear Brake Pad Set for Bajaj Pulsar 150',            'barCode' => 'BAJ-P150-BPS-001',  'businessId' => $bid, 'stock' => 40, 'buy' =>  500,  'sell' =>  850],
            ['name' => 'Castrol Engine Oil 5W-30 4L',     'brand' => $brand('Castrol'), 'category' => $cat('Lubricants & Fluids'), 'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => 'Fully Synthetic Engine Oil 5W-30, 4 Litre Can',              'barCode' => 'CST-5W30-4L-001',   'businessId' => $bid, 'stock' => 30, 'buy' =>  900,  'sell' => 1400],
            ['name' => 'Toyota Corolla Air Filter',       'brand' => $brand('Toyota'),  'category' => $cat('Filters'),             'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => 'OEM Air Filter for Toyota Corolla 2014-2020',                'barCode' => 'TOY-COR-AF-001',    'businessId' => $bid, 'stock' => 45, 'buy' =>  450,  'sell' =>  750],
            ['name' => '12V 65Ah Dry Cell Battery',       'brand' => $brand('Generic'), 'category' => $cat('Batteries'),           'unitName' => $unit('Piece'), 'quantity' => '3',  'details' => 'Maintenance-Free Dry Battery for Cars 12V 65Ah',             'barCode' => 'BAT-12V-65AH-001',  'businessId' => $bid, 'stock' => 15, 'buy' => 6000,  'sell' => 8500],
            ['name' => 'Car Tyre 185/65R15',              'brand' => $brand('Generic'), 'category' => $cat('Tyres & Tubes'),       'unitName' => $unit('Piece'), 'quantity' => '4',  'details' => '185/65R15 88H All-Season Passenger Car Tyre',                'barCode' => 'TYR-185-65R15-001', 'businessId' => $bid, 'stock' => 20, 'buy' => 4500,  'sell' => 6500],
            ['name' => 'Radiator Coolant 1L (Universal)', 'brand' => $brand('Generic'), 'category' => $cat('Lubricants & Fluids'), 'unitName' => $unit('Piece'), 'quantity' => '15', 'details' => 'Long-Life Radiator Coolant & Antifreeze 1 Litre',            'barCode' => 'RAD-COOL-1L-001',   'businessId' => $bid, 'stock' => 50, 'buy' =>  200,  'sell' =>  380],
            ['name' => 'Headlight Bulb H4 60/55W Pair',   'brand' => $brand('Bosch'),   'category' => $cat('Electrical Parts'),    'unitName' => $unit('Pair'),  'quantity' => '20', 'details' => 'H4 Halogen Headlight Bulb 60/55W Twin Pack',                 'barCode' => 'HLB-H4-PAIR-001',   'businessId' => $bid, 'stock' => 60, 'buy' =>  280,  'sell' =>  500],
            ['name' => 'Motorcycle Helmet Half Face',      'brand' => $brand('Generic'), 'category' => $cat('Body & Exterior'),     'unitName' => $unit('Piece'), 'quantity' => '5',  'details' => 'DOT-Certified Half Face Motorcycle Helmet',                   'barCode' => 'HLM-HF-001',        'businessId' => $bid, 'stock' => 25, 'buy' => 1200,  'sell' => 2000],
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
        $base = Carbon::now()->subDays(55);

        $purchases = [
            [
                'productName' => $pId('NGK-BPR6ES-001'), 'supplier' => $supId('orders@autopartsdepot.com'),
                'purchase_date' => $base->copy()->addDays(0)->toDateString(), 'invoice' => 'PUR-VEH-001',
                'reference' => 'REF-VEH-001', 'qty' => 60, 'buyPrice' => '350', 'salePriceExVat' => '600',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '660', 'profit' => '250',
                'totalAmount' => '21000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '21000', 'paidAmount' => '21000', 'dueAmount' => '0',
                'specialNote' => 'NGK Spark Plug initial lot', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('BAT-12V-65AH-001'), 'supplier' => $supId('supply@motosupply.com'),
                'purchase_date' => $base->copy()->addDays(5)->toDateString(), 'invoice' => 'PUR-VEH-002',
                'reference' => 'REF-VEH-002', 'qty' => 15, 'buyPrice' => '6000', 'salePriceExVat' => '8500',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '9350', 'profit' => '2500',
                'totalAmount' => '90000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '90000', 'paidAmount' => '50000', 'dueAmount' => '40000',
                'specialNote' => 'Car batteries – partial payment', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('TYR-185-65R15-001'), 'supplier' => $supId('sales@vehiclepro.com'),
                'purchase_date' => $base->copy()->addDays(10)->toDateString(), 'invoice' => 'PUR-VEH-003',
                'reference' => 'REF-VEH-003', 'qty' => 20, 'buyPrice' => '4500', 'salePriceExVat' => '6500',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '7150', 'profit' => '2000',
                'totalAmount' => '90000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '90000', 'paidAmount' => '90000', 'dueAmount' => '0',
                'specialNote' => 'Car tyre stock', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('CST-5W30-4L-001'), 'supplier' => $supId('orders@autopartsdepot.com'),
                'purchase_date' => $base->copy()->addDays(15)->toDateString(), 'invoice' => 'PUR-VEH-004',
                'reference' => 'REF-VEH-004', 'qty' => 30, 'buyPrice' => '900', 'salePriceExVat' => '1400',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '1540', 'profit' => '500',
                'totalAmount' => '27000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '27000', 'paidAmount' => '27000', 'dueAmount' => '0',
                'specialNote' => 'Castrol engine oil batch', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('HON-CD70-CSK-001'), 'supplier' => $supId('supply@motosupply.com'),
                'purchase_date' => $base->copy()->addDays(20)->toDateString(), 'invoice' => 'PUR-VEH-005',
                'reference' => 'REF-VEH-005', 'qty' => 35, 'buyPrice' => '800', 'salePriceExVat' => '1400',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '1540', 'profit' => '600',
                'totalAmount' => '28000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '28000', 'paidAmount' => '28000', 'dueAmount' => '0',
                'specialNote' => 'Honda CD70 chain sprocket kits', 'businessId' => $bid,
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
        $base = Carbon::now()->subDays(45);

        $sales = [
            [
                'header' => [
                    'date' => $base->copy()->addDays(3)->toDateString(), 'invoice' => 'INV-VEH-001',
                    'customerId' => $custId('karim.auto@email.com'), 'reference' => '',
                    'note' => 'Spark plugs + air filter + oil', 'totalSale' => '2750', 'discountAmount' => '0',
                    'grandTotal' => '2750', 'paidAmount' => '2750', 'invoiceDue' => '0',
                    'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => $bid,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-VEH-001'), 'qty' => 2, 'salePrice' => '600',  'buyPrice' => '350',  'totalSale' => '1200', 'totalPurchase' => '700',  'profitTotal' => '500',  'profitMargin' => '41.67', 'warranty_days' => null],
                    ['purchaseId' => $purId('PUR-VEH-004'), 'qty' => 1, 'salePrice' => '1400', 'buyPrice' => '900',  'totalSale' => '1400', 'totalPurchase' => '900',  'profitTotal' => '500',  'profitMargin' => '35.71', 'warranty_days' => null],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(8)->toDateString(), 'invoice' => 'INV-VEH-002',
                    'customerId' => $custId('ali.motors@email.com'), 'reference' => '',
                    'note' => 'Car battery + 2 tyres', 'totalSale' => '22500', 'discountAmount' => '500',
                    'grandTotal' => '22000', 'paidAmount' => '22000', 'invoiceDue' => '0',
                    'prevDue' => '5000', 'curDue' => '5000', 'status' => 'complete', 'businessId' => $bid,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-VEH-002'), 'qty' => 1, 'salePrice' => '8500', 'buyPrice' => '6000', 'totalSale' => '8500',  'totalPurchase' => '6000',  'profitTotal' => '2500',  'profitMargin' => '29.41', 'warranty_days' => '365'],
                    ['purchaseId' => $purId('PUR-VEH-003'), 'qty' => 2, 'salePrice' => '6500', 'buyPrice' => '4500', 'totalSale' => '13000', 'totalPurchase' => '9000',  'profitTotal' => '4000',  'profitMargin' => '30.77', 'warranty_days' => null],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(14)->toDateString(), 'invoice' => 'INV-VEH-003',
                    'customerId' => $custId('bikecare.center@email.com'), 'reference' => '',
                    'note' => 'Honda CD70 chain kits x5', 'totalSale' => '7000', 'discountAmount' => '0',
                    'grandTotal' => '7000', 'paidAmount' => '4000', 'invoiceDue' => '3000',
                    'prevDue' => '0', 'curDue' => '3000', 'status' => 'partial', 'businessId' => $bid,
                ],
                'items' => [[
                    'purchaseId' => $purId('PUR-VEH-005'), 'qty' => 5, 'salePrice' => '1400', 'buyPrice' => '800',
                    'totalSale' => '7000', 'totalPurchase' => '4000', 'profitTotal' => '3000', 'profitMargin' => '42.86', 'warranty_days' => null,
                ]],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(20)->toDateString(), 'invoice' => 'INV-VEH-004',
                    'customerId' => $custId('malik.transport@email.com'), 'reference' => '',
                    'note' => 'Engine oil bulk order', 'totalSale' => '7000', 'discountAmount' => '700',
                    'grandTotal' => '6300', 'paidAmount' => '6300', 'invoiceDue' => '0',
                    'prevDue' => '2500', 'curDue' => '2500', 'status' => 'complete', 'businessId' => $bid,
                ],
                'items' => [[
                    'purchaseId' => $purId('PUR-VEH-004'), 'qty' => 5, 'salePrice' => '1400', 'buyPrice' => '900',
                    'totalSale' => '7000', 'totalPurchase' => '4500', 'profitTotal' => '2500', 'profitMargin' => '35.71', 'warranty_days' => null,
                ]],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(28)->toDateString(), 'invoice' => 'INV-VEH-005',
                    'customerId' => $custId('noor.vehicles@email.com'), 'reference' => '',
                    'note' => 'Vehicle accessories – bulbs & wiper', 'totalSale' => '3500', 'discountAmount' => '0',
                    'grandTotal' => '3500', 'paidAmount' => '0', 'invoiceDue' => '3500',
                    'prevDue' => '0', 'curDue' => '3500', 'status' => 'due', 'businessId' => $bid,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-VEH-001'), 'qty' => 3, 'salePrice' => '600',  'buyPrice' => '350',  'totalSale' => '1800',  'totalPurchase' => '1050', 'profitTotal' => '750',  'profitMargin' => '41.67', 'warranty_days' => null],
                    ['purchaseId' => null,                  'qty' => 1, 'salePrice' => '1000', 'buyPrice' => '600',  'totalSale' => '1000',  'totalPurchase' => '600',  'profitTotal' => '400',  'profitMargin' => '40.00', 'warranty_days' => null],
                ],
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




