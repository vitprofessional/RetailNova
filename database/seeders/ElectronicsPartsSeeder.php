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
 * Demo data for an Electronics Parts / Components Shop
 */
class ElectronicsPartsSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $bid = $this->businessId;

        // ── Brands ──────────────────────────────────────────────────────────
        foreach (['Arduino', 'Raspberry Pi', 'Texas Instruments', 'STMicroelectronics', 'Philips', 'JBC Tools'] as $b) {
            DB::table('brands')->insertOrIgnore(['name' => $b, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ── Categories ──────────────────────────────────────────────────────
        $cats = ['Microcontrollers & Dev Boards', 'Passive Components', 'Active Components',
                 'Power Electronics', 'Tools & Equipment', 'Wires & Connectors', 'Sensors & Modules', 'PCB & Prototyping'];
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
            ['name' => 'ElectroSpark Components', 'mail' => 'orders@electrospark.com',  'mobile' => '0314-8001001', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Science Lab', 'openingBalance' => 0],
            ['name' => 'Circuit House Ltd',        'mail' => 'supply@circuithouse.com',  'mobile' => '0314-8002002', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Nawabpur',    'openingBalance' => 3000],
            ['name' => 'Tech Components BD',       'mail' => 'sales@techcomponents.com', 'mobile' => '0314-8003003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Chawkbazar',  'openingBalance' => 0],
        ];
        foreach ($suppliers as $s) {
            Supplier::updateOrCreate(['mail' => $s['mail']], $s);
        }

        // ── Customers ────────────────────────────────────────────────────────
        $customers = [
            ['name' => 'Robotics Lab BD',        'mail' => 'robotics@labbd.com',        'mobile' => '0324-9001001', 'country' => 'Bangladesh', 'state' => 'Dhaka',    'city' => 'Dhaka',    'area' => 'BUET Campus',  'openingBalance' => 0,    'businessId' => $bid],
            ['name' => 'Engineering Projects Co', 'mail' => 'eng@projectsco.com',        'mobile' => '0324-9002002', 'country' => 'Bangladesh', 'state' => 'Dhaka',    'city' => 'Dhaka',    'area' => 'Mohakhali',   'openingBalance' => 1500, 'businessId' => $bid],
            ['name' => 'Makers Workshop',         'mail' => 'makers@workshop.com',       'mobile' => '0324-9003003', 'country' => 'Bangladesh', 'state' => 'Dhaka',    'city' => 'Dhaka',    'area' => 'Lalmatia',    'openingBalance' => 0,    'businessId' => $bid],
            ['name' => 'PCB Design Studio',       'mail' => 'pcbdesign@studio.com',      'mobile' => '0324-9004004', 'country' => 'Bangladesh', 'state' => 'Sylhet',   'city' => 'Sylhet',   'area' => 'Tilagarh',    'openingBalance' => 800,  'businessId' => $bid],
            ['name' => 'Automation Tech BD',      'mail' => 'automation@techbd.com',     'mobile' => '0324-9005005', 'country' => 'Bangladesh', 'state' => 'Rajshahi', 'city' => 'Rajshahi', 'area' => 'Laxmipur',    'openingBalance' => 0,    'businessId' => $bid],
        ];
        foreach ($customers as $c) {
            Customer::updateOrCreate(['mail' => $c['mail']], $c);
        }

        // ── Products ─────────────────────────────────────────────────────────
        $products = [
            ['name' => 'Arduino Uno R3',                'brand' => $brand('Arduino'),            'category' => $cat('Microcontrollers & Dev Boards'), 'unitName' => $unit('Piece'), 'quantity' => '5',  'details' => 'ATmega328P, 14 Digital I/O, 6 Analog Inputs, USB-A',       'barCode' => 'ARD-UNO-R3-001',     'businessId' => $bid, 'stock' => 40,  'buy' =>  750, 'sell' => 1200],
            ['name' => 'Raspberry Pi 4 Model B 4GB',    'brand' => $brand('Raspberry Pi'),       'category' => $cat('Microcontrollers & Dev Boards'), 'unitName' => $unit('Piece'), 'quantity' => '3',  'details' => 'Quad-core Cortex-A72, 4GB RAM, Wi-Fi, Bluetooth, USB 3.0', 'barCode' => 'RPI-4B-4G-001',      'businessId' => $bid, 'stock' => 15,  'buy' => 5000, 'sell' => 7000],
            ['name' => '555 Timer IC NE555P (Pack 10)', 'brand' => $brand('Texas Instruments'),  'category' => $cat('Active Components'),             'unitName' => $unit('Box'),   'quantity' => '50', 'details' => 'NE555P Timer IC Pack of 10 – General Purpose',             'barCode' => 'IC-NE555-P10-001',   'businessId' => $bid, 'stock' => 200, 'buy' =>   40, 'sell' =>  100],
            ['name' => '10KΩ Resistor 1/4W (Pack 100)', 'brand' => $brand('Generic'),            'category' => $cat('Passive Components'),            'unitName' => $unit('Box'),   'quantity' => '50', 'details' => '10K Ohm Through-hole Resistor 1/4W 5% Tolerance Pack 100', 'barCode' => 'RES-10K-P100-001',   'businessId' => $bid, 'stock' => 300, 'buy' =>   30, 'sell' =>   80],
            ['name' => '100µF 25V Capacitor (Pack 20)', 'brand' => $brand('Generic'),            'category' => $cat('Passive Components'),            'unitName' => $unit('Box'),   'quantity' => '30', 'details' => '100 µF 25V Electrolytic Capacitor Pack of 20',             'barCode' => 'CAP-100UF-P20-001',  'businessId' => $bid, 'stock' => 200, 'buy' =>   40, 'sell' =>  100],
            ['name' => 'BC547 NPN Transistor (Pack 50)','brand' => $brand('STMicroelectronics'), 'category' => $cat('Active Components'),             'unitName' => $unit('Box'),   'quantity' => '30', 'details' => 'BC547B NPN General Purpose Transistor Pack of 50',          'barCode' => 'TRN-BC547-P50-001',  'businessId' => $bid, 'stock' => 200, 'buy' =>   50, 'sell' =>  120],
            ['name' => 'LED 5mm Red (Pack 100)',         'brand' => $brand('Philips'),            'category' => $cat('Active Components'),             'unitName' => $unit('Box'),   'quantity' => '50', 'details' => '5mm Through-Hole Red LED 30mA Pack of 100',                'barCode' => 'LED-5MM-RED-001',    'businessId' => $bid, 'stock' => 300, 'buy' =>   80, 'sell' =>  200],
            ['name' => '60W Soldering Iron Station',    'brand' => $brand('JBC Tools'),          'category' => $cat('Tools & Equipment'),             'unitName' => $unit('Piece'), 'quantity' => '2',  'details' => '60W Digital Soldering Station with Lead-Free Tips',         'barCode' => 'SOL-60W-STN-001',    'businessId' => $bid, 'stock' => 10,  'buy' => 1500, 'sell' => 2500],
            ['name' => 'Digital Multimeter Auto-Range', 'brand' => $brand('Generic'),            'category' => $cat('Tools & Equipment'),             'unitName' => $unit('Piece'), 'quantity' => '3',  'details' => 'Auto-Ranging DMM, DC/AC Voltage, Current, Resistance, Diode','barCode' => 'DMM-AUTO-001',        'businessId' => $bid, 'stock' => 20,  'buy' =>  800, 'sell' => 1400],
            ['name' => 'Breadboard 830 Points',         'brand' => $brand('Generic'),            'category' => $cat('PCB & Prototyping'),             'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => '830 Tie-Point Solderless Breadboard with Power Rails',       'barCode' => 'BBD-830-001',        'businessId' => $bid, 'stock' => 80,  'buy' =>  200, 'sell' =>  420],
            ['name' => '40-Pin Jumper Wires M-M 20cm',  'brand' => $brand('Generic'),            'category' => $cat('Wires & Connectors'),            'unitName' => $unit('Box'),   'quantity' => '20', 'details' => '40-Pin Male-to-Male Breadboard Jumper Wires 20cm',          'barCode' => 'JMP-40PIN-001',      'businessId' => $bid, 'stock' => 150, 'buy' =>   80, 'sell' =>  180],
            ['name' => '5V 2A DC Power Supply Module',  'brand' => $brand('Generic'),            'category' => $cat('Power Electronics'),             'unitName' => $unit('Piece'), 'quantity' => '10', 'details' => 'AC-DC 5V 2A Regulated Switch Mode Power Supply Module',      'barCode' => 'PSM-5V2A-001',       'businessId' => $bid, 'stock' => 50,  'buy' =>  120, 'sell' =>  280],
        ];

        foreach ($products as $data) {
            $stock = $data['stock']; unset($data['stock'], $data['buy'], $data['sell']);
            $p = Product::updateOrCreate(['barCode' => $data['barCode']], $data);
            if ($p->stocks()->sum('currentStock') == 0) {
                ProductStock::create(['productId' => $p->id, 'purchaseId' => null, 'currentStock' => $stock, 'businessId' => $bid]);
            }
        }

        // ── Purchases ────────────────────────────────────────────────────────
        $pId  = fn($bc) => DB::table('products')->where('barCode', $bc)->value('id');
        $base = Carbon::now()->subDays(52);

        $purchases = [
            [
                'productName' => $pId('ARD-UNO-R3-001'), 'supplier' => $supId('orders@electrospark.com'),
                'purchase_date' => $base->copy()->addDays(0)->toDateString(), 'invoice' => 'PUR-ELC-001',
                'reference' => 'REF-ELC-001', 'qty' => 40, 'buyPrice' => '750', 'salePriceExVat' => '1200',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '1320', 'profit' => '450',
                'totalAmount' => '30000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '30000', 'paidAmount' => '30000', 'dueAmount' => '0',
                'specialNote' => 'Arduino Uno R3 initial stock', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('RPI-4B-4G-001'), 'supplier' => $supId('supply@circuithouse.com'),
                'purchase_date' => $base->copy()->addDays(4)->toDateString(), 'invoice' => 'PUR-ELC-002',
                'reference' => 'REF-ELC-002', 'qty' => 15, 'buyPrice' => '5000', 'salePriceExVat' => '7000',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '7700', 'profit' => '2000',
                'totalAmount' => '75000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '75000', 'paidAmount' => '75000', 'dueAmount' => '0',
                'specialNote' => 'Raspberry Pi 4 batch', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('SOL-60W-STN-001'), 'supplier' => $supId('sales@techcomponents.com'),
                'purchase_date' => $base->copy()->addDays(10)->toDateString(), 'invoice' => 'PUR-ELC-003',
                'reference' => 'REF-ELC-003', 'qty' => 10, 'buyPrice' => '1500', 'salePriceExVat' => '2500',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '2750', 'profit' => '1000',
                'totalAmount' => '15000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '15000', 'paidAmount' => '10000', 'dueAmount' => '5000',
                'specialNote' => 'Soldering stations – partial payment', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('RES-10K-P100-001'), 'supplier' => $supId('orders@electrospark.com'),
                'purchase_date' => $base->copy()->addDays(16)->toDateString(), 'invoice' => 'PUR-ELC-004',
                'reference' => 'REF-ELC-004', 'qty' => 300, 'buyPrice' => '30', 'salePriceExVat' => '80',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '88', 'profit' => '50',
                'totalAmount' => '9000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '9000', 'paidAmount' => '9000', 'dueAmount' => '0',
                'specialNote' => 'Resistor bulk lot', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('DMM-AUTO-001'), 'supplier' => $supId('supply@circuithouse.com'),
                'purchase_date' => $base->copy()->addDays(22)->toDateString(), 'invoice' => 'PUR-ELC-005',
                'reference' => 'REF-ELC-005', 'qty' => 20, 'buyPrice' => '800', 'salePriceExVat' => '1400',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '1540', 'profit' => '600',
                'totalAmount' => '16000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '16000', 'paidAmount' => '16000', 'dueAmount' => '0',
                'specialNote' => 'Digital multimeters batch', 'businessId' => $bid,
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
        $base = Carbon::now()->subDays(42);

        $sales = [
            [
                'header' => [
                    'date' => $base->copy()->addDays(2)->toDateString(), 'invoice' => 'INV-ELC-001',
                    'customerId' => $custId('robotics@labbd.com'), 'reference' => '',
                    'note' => 'Arduino + Pi + Resistors starter kit', 'totalSale' => '9480', 'discountAmount' => '480',
                    'grandTotal' => '9000', 'paidAmount' => '9000', 'invoiceDue' => '0',
                    'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => $bid,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-ELC-001'), 'qty' => 5, 'salePrice' => '1200', 'buyPrice' => '750',  'totalSale' => '6000', 'totalPurchase' => '3750', 'profitTotal' => '2250', 'profitMargin' => '37.50', 'warranty_days' => null],
                    ['purchaseId' => $purId('PUR-ELC-004'), 'qty' => 10,'salePrice' => '80',   'buyPrice' => '30',   'totalSale' => '800',  'totalPurchase' => '300',  'profitTotal' => '500',  'profitMargin' => '62.50', 'warranty_days' => null],
                    ['purchaseId' => null,                  'qty' => 2, 'salePrice' => '420',  'buyPrice' => '200',  'totalSale' => '840',  'totalPurchase' => '400',  'profitTotal' => '440',  'profitMargin' => '52.38', 'warranty_days' => null],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(7)->toDateString(), 'invoice' => 'INV-ELC-002',
                    'customerId' => $custId('eng@projectsco.com'), 'reference' => '',
                    'note' => 'Raspberry Pi x3 + soldering iron', 'totalSale' => '23500', 'discountAmount' => '0',
                    'grandTotal' => '23500', 'paidAmount' => '20000', 'invoiceDue' => '3500',
                    'prevDue' => '1500', 'curDue' => '5000', 'status' => 'partial', 'businessId' => $bid,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-ELC-002'), 'qty' => 3, 'salePrice' => '7000', 'buyPrice' => '5000', 'totalSale' => '21000', 'totalPurchase' => '15000', 'profitTotal' => '6000', 'profitMargin' => '28.57', 'warranty_days' => '365'],
                    ['purchaseId' => $purId('PUR-ELC-003'), 'qty' => 1, 'salePrice' => '2500', 'buyPrice' => '1500', 'totalSale' => '2500',  'totalPurchase' => '1500',  'profitTotal' => '1000', 'profitMargin' => '40.00', 'warranty_days' => '365'],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(14)->toDateString(), 'invoice' => 'INV-ELC-003',
                    'customerId' => $custId('makers@workshop.com'), 'reference' => '',
                    'note' => 'Electronic components lot', 'totalSale' => '7800', 'discountAmount' => '300',
                    'grandTotal' => '7500', 'paidAmount' => '7500', 'invoiceDue' => '0',
                    'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => $bid,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-ELC-001'), 'qty' => 3, 'salePrice' => '1200', 'buyPrice' => '750',  'totalSale' => '3600', 'totalPurchase' => '2250', 'profitTotal' => '1350', 'profitMargin' => '37.50', 'warranty_days' => null],
                    ['purchaseId' => $purId('PUR-ELC-005'), 'qty' => 2, 'salePrice' => '1400', 'buyPrice' => '800',  'totalSale' => '2800', 'totalPurchase' => '1600', 'profitTotal' => '1200', 'profitMargin' => '42.86', 'warranty_days' => '365'],
                    ['purchaseId' => null,                  'qty' => 10,'salePrice' => '100',  'buyPrice' => '40',   'totalSale' => '1000', 'totalPurchase' => '400',  'profitTotal' => '600',  'profitMargin' => '60.00', 'warranty_days' => null],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(22)->toDateString(), 'invoice' => 'INV-ELC-004',
                    'customerId' => $custId('pcbdesign@studio.com'), 'reference' => '',
                    'note' => 'Soldering station + multimeters', 'totalSale' => '6400', 'discountAmount' => '400',
                    'grandTotal' => '6000', 'paidAmount' => '6000', 'invoiceDue' => '0',
                    'prevDue' => '800', 'curDue' => '800', 'status' => 'complete', 'businessId' => $bid,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-ELC-003'), 'qty' => 1, 'salePrice' => '2500', 'buyPrice' => '1500', 'totalSale' => '2500', 'totalPurchase' => '1500', 'profitTotal' => '1000', 'profitMargin' => '40.00', 'warranty_days' => '365'],
                    ['purchaseId' => $purId('PUR-ELC-005'), 'qty' => 2, 'salePrice' => '1400', 'buyPrice' => '800',  'totalSale' => '2800', 'totalPurchase' => '1600', 'profitTotal' => '1200', 'profitMargin' => '42.86', 'warranty_days' => '365'],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(30)->toDateString(), 'invoice' => 'INV-ELC-005',
                    'customerId' => $custId('automation@techbd.com'), 'reference' => '',
                    'note' => 'Arduino Uno x10 credit order', 'totalSale' => '12000', 'discountAmount' => '0',
                    'grandTotal' => '12000', 'paidAmount' => '0', 'invoiceDue' => '12000',
                    'prevDue' => '0', 'curDue' => '12000', 'status' => 'due', 'businessId' => $bid,
                ],
                'items' => [[
                    'purchaseId' => $purId('PUR-ELC-001'), 'qty' => 10, 'salePrice' => '1200', 'buyPrice' => '750',
                    'totalSale' => '12000', 'totalPurchase' => '7500', 'profitTotal' => '4500', 'profitMargin' => '37.50', 'warranty_days' => null,
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
