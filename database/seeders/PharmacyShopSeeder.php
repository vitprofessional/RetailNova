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
 * Demo data for a Pharmacy / Medicine Shop
 */
class PharmacyShopSeeder extends Seeder
{
    public function run(): void
    {
        // Brands
        foreach (['Square', 'Beximco', 'Incepta', 'Renata', 'ACI', 'Healthcare'] as $b) {
            DB::table('brands')->insertOrIgnore(['name' => $b, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Categories
        $cats = [
            'Prescription Medicines',
            'OTC Medicines',
            'Vitamins & Supplements',
            'Diabetes Care',
            'First Aid',
            'Baby Care',
            'Personal Care',
        ];
        foreach ($cats as $c) {
            DB::table('categories')->insertOrIgnore(['name' => $c, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Units commonly used in pharmacies
        foreach (['Strip', 'Bottle', 'Tube'] as $u) {
            DB::table('product_units')->insertOrIgnore(['name' => $u, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Helpers
        $brand  = fn(string $n) => (string)(DB::table('brands')->where('name', $n)->value('id') ?? 1);
        $cat    = fn(string $n) => (string)(DB::table('categories')->where('name', $n)->value('id') ?? 1);
        $unit   = fn(string $n) => (string)(DB::table('product_units')->where('name', $n)->value('id') ?? 1);
        $supId  = fn(string $mail) => DB::table('suppliers')->where('mail', $mail)->value('id');
        $custId = fn(string $mail) => DB::table('customers')->where('mail', $mail)->value('id');
        $purId  = fn(string $inv) => DB::table('purchase_products')->where('invoice', $inv)->value('id');

        // Suppliers
        $suppliers = [
            ['name' => 'MediSource Distribution', 'mail' => 'orders@medisource.com', 'mobile' => '0335-2101001', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Motijheel', 'openingBalance' => 0],
            ['name' => 'HealthLine Pharma', 'mail' => 'supply@healthline.com', 'mobile' => '0335-2102002', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Farmgate', 'openingBalance' => 12000],
            ['name' => 'CareMed Wholesale', 'mail' => 'sales@caremed.com', 'mobile' => '0335-2103003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Agrabad', 'openingBalance' => 0],
        ];
        foreach ($suppliers as $s) {
            Supplier::updateOrCreate(['mail' => $s['mail']], $s);
        }

        // Customers
        $customers = [
            ['name' => 'Rahman Medical Hall', 'mail' => 'rahman.medical@email.com', 'mobile' => '0345-2201001', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Shantinagar', 'openingBalance' => 0, 'businessId' => 1],
            ['name' => 'Nabila Pharmacy', 'mail' => 'nabila.pharmacy@email.com', 'mobile' => '0345-2202002', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Mohammadpur', 'openingBalance' => 1800, 'businessId' => 1],
            ['name' => 'Shuvo Healthcare Point', 'mail' => 'shuvo.health@email.com', 'mobile' => '0345-2203003', 'country' => 'Bangladesh', 'state' => 'Sylhet', 'city' => 'Sylhet', 'area' => 'Zindabazar', 'openingBalance' => 0, 'businessId' => 1],
            ['name' => 'Farhana Begum', 'mail' => 'farhana.begum@email.com', 'mobile' => '0345-2204004', 'country' => 'Bangladesh', 'state' => 'Rajshahi', 'city' => 'Rajshahi', 'area' => 'Laxmipur', 'openingBalance' => 900, 'businessId' => 1],
            ['name' => 'Mizanur Rahman', 'mail' => 'mizanur.rahman@email.com', 'mobile' => '0345-2205005', 'country' => 'Bangladesh', 'state' => 'Khulna', 'city' => 'Khulna', 'area' => 'Sonadanga', 'openingBalance' => 0, 'businessId' => 1],
        ];
        foreach ($customers as $c) {
            Customer::updateOrCreate(['mail' => $c['mail']], $c);
        }

        // Products
        $products = [
            ['name' => 'Napa 500mg (10 tablets)', 'brand' => $brand('Beximco'), 'category' => $cat('OTC Medicines'), 'unitName' => $unit('Strip'), 'quantity' => '30', 'details' => 'Paracetamol 500mg for fever and pain relief', 'barCode' => 'PHA-NAPA-500-001', 'businessId' => 1, 'stock' => 350, 'buy' => 12, 'sell' => 18],
            ['name' => 'Seclo 20mg (14 capsules)', 'brand' => $brand('Square'), 'category' => $cat('Prescription Medicines'), 'unitName' => $unit('Strip'), 'quantity' => '20', 'details' => 'Omeprazole for gastric acidity', 'barCode' => 'PHA-SECLO-20-001', 'businessId' => 1, 'stock' => 250, 'buy' => 38, 'sell' => 52],
            ['name' => 'Monas 10mg (10 tablets)', 'brand' => $brand('Incepta'), 'category' => $cat('Prescription Medicines'), 'unitName' => $unit('Strip'), 'quantity' => '20', 'details' => 'Montelukast for allergy/asthma', 'barCode' => 'PHA-MONAS-10-001', 'businessId' => 1, 'stock' => 180, 'buy' => 95, 'sell' => 125],
            ['name' => 'Vitamin C 500mg (30 tablets)', 'brand' => $brand('Healthcare'), 'category' => $cat('Vitamins & Supplements'), 'unitName' => $unit('Bottle'), 'quantity' => '12', 'details' => 'Vitamin C supplement for immunity support', 'barCode' => 'PHA-VITC-500-001', 'businessId' => 1, 'stock' => 90, 'buy' => 140, 'sell' => 210],
            ['name' => 'ORS Saline Powder (Pack)', 'brand' => $brand('ACI'), 'category' => $cat('OTC Medicines'), 'unitName' => $unit('Piece'), 'quantity' => '100', 'details' => 'Oral rehydration salts for dehydration', 'barCode' => 'PHA-ORS-001', 'businessId' => 1, 'stock' => 500, 'buy' => 4, 'sell' => 8],
            ['name' => 'Digital Glucometer Strips (50 pcs)', 'brand' => $brand('Renata'), 'category' => $cat('Diabetes Care'), 'unitName' => $unit('Box'), 'quantity' => '10', 'details' => 'Blood glucose test strips 50 count', 'barCode' => 'PHA-GLU-STRIP-001', 'businessId' => 1, 'stock' => 120, 'buy' => 620, 'sell' => 780],
            ['name' => 'Antiseptic Cream 30g', 'brand' => $brand('Square'), 'category' => $cat('First Aid'), 'unitName' => $unit('Tube'), 'quantity' => '24', 'details' => 'Topical antiseptic cream for minor cuts and burns', 'barCode' => 'PHA-ANTI-CRM-001', 'businessId' => 1, 'stock' => 150, 'buy' => 55, 'sell' => 85],
            ['name' => 'Baby Diaper Medium (30 pcs)', 'brand' => $brand('Healthcare'), 'category' => $cat('Baby Care'), 'unitName' => $unit('Pack'), 'quantity' => '12', 'details' => 'Medium size breathable baby diapers', 'barCode' => 'PHA-DIAPER-M-001', 'businessId' => 1, 'stock' => 85, 'buy' => 380, 'sell' => 490],
            ['name' => 'Hand Sanitizer 250ml', 'brand' => $brand('ACI'), 'category' => $cat('Personal Care'), 'unitName' => $unit('Bottle'), 'quantity' => '24', 'details' => '70% alcohol instant hand sanitizer', 'barCode' => 'PHA-SAN-250-001', 'businessId' => 1, 'stock' => 160, 'buy' => 70, 'sell' => 110],
            ['name' => 'Bandage Roll 5cm', 'brand' => $brand('Renata'), 'category' => $cat('First Aid'), 'unitName' => $unit('Piece'), 'quantity' => '50', 'details' => 'Sterile medical gauze bandage roll', 'barCode' => 'PHA-BAND-5-001', 'businessId' => 1, 'stock' => 220, 'buy' => 12, 'sell' => 20],
            ['name' => 'Aspirin 75mg (14 tablets)', 'brand' => $brand('Square'), 'category' => $cat('Prescription Medicines'), 'unitName' => $unit('Strip'), 'quantity' => '20', 'details' => 'Low-dose aspirin for cardiovascular support', 'barCode' => 'PHA-ASP-75-001', 'businessId' => 1, 'stock' => 170, 'buy' => 26, 'sell' => 40],
            ['name' => 'Calcium + D3 (30 tablets)', 'brand' => $brand('Beximco'), 'category' => $cat('Vitamins & Supplements'), 'unitName' => $unit('Bottle'), 'quantity' => '12', 'details' => 'Calcium and vitamin D3 bone health supplement', 'barCode' => 'PHA-CALD3-001', 'businessId' => 1, 'stock' => 100, 'buy' => 190, 'sell' => 260],
        ];

        foreach ($products as $data) {
            $stock = $data['stock'];
            unset($data['stock'], $data['buy'], $data['sell']);

            $p = Product::updateOrCreate(['barCode' => $data['barCode']], $data);
            if ($p->stocks()->sum('currentStock') == 0) {
                ProductStock::create(['productId' => $p->id, 'purchaseId' => null, 'currentStock' => $stock, 'businessId' => 1]);
            }
        }

        // Purchases
        $pId = fn($bc) => DB::table('products')->where('barCode', $bc)->value('id');
        $base = Carbon::now()->subDays(32);

        $purchases = [
            [
                'productName' => $pId('PHA-NAPA-500-001'), 'supplier' => $supId('orders@medisource.com'),
                'purchase_date' => $base->copy()->addDays(0)->toDateString(), 'invoice' => 'PUR-PHA-001',
                'reference' => 'REF-PHA-001', 'qty' => 350, 'buyPrice' => '12', 'salePriceExVat' => '18',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '20', 'profit' => '6',
                'totalAmount' => '4200', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '4200', 'paidAmount' => '4200', 'dueAmount' => '0',
                'specialNote' => 'OTC pain reliever restock', 'businessId' => 1,
            ],
            [
                'productName' => $pId('PHA-SECLO-20-001'), 'supplier' => $supId('supply@healthline.com'),
                'purchase_date' => $base->copy()->addDays(3)->toDateString(), 'invoice' => 'PUR-PHA-002',
                'reference' => 'REF-PHA-002', 'qty' => 250, 'buyPrice' => '38', 'salePriceExVat' => '52',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '57', 'profit' => '14',
                'totalAmount' => '9500', 'disType' => 'flat', 'disAmount' => '500', 'disParcent' => '0',
                'grandTotal' => '9000', 'paidAmount' => '6000', 'dueAmount' => '3000',
                'specialNote' => 'Gastric medicine batch, partial payment', 'businessId' => 1,
            ],
            [
                'productName' => $pId('PHA-GLU-STRIP-001'), 'supplier' => $supId('sales@caremed.com'),
                'purchase_date' => $base->copy()->addDays(8)->toDateString(), 'invoice' => 'PUR-PHA-003',
                'reference' => 'REF-PHA-003', 'qty' => 120, 'buyPrice' => '620', 'salePriceExVat' => '780',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '858', 'profit' => '160',
                'totalAmount' => '74400', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '74400', 'paidAmount' => '74400', 'dueAmount' => '0',
                'specialNote' => 'Diabetes strips monthly stock', 'businessId' => 1,
            ],
            [
                'productName' => $pId('PHA-DIAPER-M-001'), 'supplier' => $supId('orders@medisource.com'),
                'purchase_date' => $base->copy()->addDays(13)->toDateString(), 'invoice' => 'PUR-PHA-004',
                'reference' => 'REF-PHA-004', 'qty' => 85, 'buyPrice' => '380', 'salePriceExVat' => '490',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '539', 'profit' => '110',
                'totalAmount' => '32300', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '32300', 'paidAmount' => '32300', 'dueAmount' => '0',
                'specialNote' => 'Baby care items refill', 'businessId' => 1,
            ],
            [
                'productName' => $pId('PHA-CALD3-001'), 'supplier' => $supId('supply@healthline.com'),
                'purchase_date' => $base->copy()->addDays(19)->toDateString(), 'invoice' => 'PUR-PHA-005',
                'reference' => 'REF-PHA-005', 'qty' => 100, 'buyPrice' => '190', 'salePriceExVat' => '260',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '286', 'profit' => '70',
                'totalAmount' => '19000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '19000', 'paidAmount' => '19000', 'dueAmount' => '0',
                'specialNote' => 'Vitamin and calcium supplement lot', 'businessId' => 1,
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
            if ($stock) {
                $stock->update(['purchaseId' => $id]);
            }
        }

        // Sales
        $base = Carbon::now()->subDays(24);

        $sales = [
            [
                'header' => [
                    'date' => $base->copy()->addDays(2)->toDateString(), 'invoice' => 'INV-PHA-001',
                    'customerId' => $custId('rahman.medical@email.com'), 'reference' => '',
                    'note' => 'Napa strips x50 and ORS packs x80', 'totalSale' => '1540', 'discountAmount' => '40',
                    'grandTotal' => '1500', 'paidAmount' => '1500', 'invoiceDue' => '0',
                    'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => 1,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-PHA-001'), 'qty' => 50, 'salePrice' => '18', 'buyPrice' => '12', 'totalSale' => '900', 'totalPurchase' => '600', 'profitTotal' => '300', 'profitMargin' => '33.33', 'warranty_days' => null],
                    ['purchaseId' => null, 'qty' => 80, 'salePrice' => '8', 'buyPrice' => '4', 'totalSale' => '640', 'totalPurchase' => '320', 'profitTotal' => '320', 'profitMargin' => '50.00', 'warranty_days' => null],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(7)->toDateString(), 'invoice' => 'INV-PHA-002',
                    'customerId' => $custId('nabila.pharmacy@email.com'), 'reference' => '',
                    'note' => 'Seclo strips x40', 'totalSale' => '2080', 'discountAmount' => '80',
                    'grandTotal' => '2000', 'paidAmount' => '1200', 'invoiceDue' => '800',
                    'prevDue' => '1800', 'curDue' => '2600', 'status' => 'partial', 'businessId' => 1,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-PHA-002'), 'qty' => 40, 'salePrice' => '52', 'buyPrice' => '38', 'totalSale' => '2080', 'totalPurchase' => '1520', 'profitTotal' => '560', 'profitMargin' => '26.92', 'warranty_days' => null],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(12)->toDateString(), 'invoice' => 'INV-PHA-003',
                    'customerId' => $custId('shuvo.health@email.com'), 'reference' => '',
                    'note' => 'Glucometer strips x15 boxes', 'totalSale' => '11700', 'discountAmount' => '0',
                    'grandTotal' => '11700', 'paidAmount' => '11700', 'invoiceDue' => '0',
                    'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => 1,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-PHA-003'), 'qty' => 15, 'salePrice' => '780', 'buyPrice' => '620', 'totalSale' => '11700', 'totalPurchase' => '9300', 'profitTotal' => '2400', 'profitMargin' => '20.51', 'warranty_days' => null],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(16)->toDateString(), 'invoice' => 'INV-PHA-004',
                    'customerId' => $custId('farhana.begum@email.com'), 'reference' => '',
                    'note' => 'Baby diapers x10 packs + sanitizer x12', 'totalSale' => '6220', 'discountAmount' => '220',
                    'grandTotal' => '6000', 'paidAmount' => '6000', 'invoiceDue' => '0',
                    'prevDue' => '900', 'curDue' => '900', 'status' => 'complete', 'businessId' => 1,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-PHA-004'), 'qty' => 10, 'salePrice' => '490', 'buyPrice' => '380', 'totalSale' => '4900', 'totalPurchase' => '3800', 'profitTotal' => '1100', 'profitMargin' => '22.45', 'warranty_days' => null],
                    ['purchaseId' => null, 'qty' => 12, 'salePrice' => '110', 'buyPrice' => '70', 'totalSale' => '1320', 'totalPurchase' => '840', 'profitTotal' => '480', 'profitMargin' => '36.36', 'warranty_days' => null],
                ],
            ],
            [
                'header' => [
                    'date' => $base->copy()->addDays(21)->toDateString(), 'invoice' => 'INV-PHA-005',
                    'customerId' => $custId('mizanur.rahman@email.com'), 'reference' => '',
                    'note' => 'Calcium D3 bottles x20 on credit', 'totalSale' => '5200', 'discountAmount' => '0',
                    'grandTotal' => '5200', 'paidAmount' => '0', 'invoiceDue' => '5200',
                    'prevDue' => '0', 'curDue' => '5200', 'status' => 'due', 'businessId' => 1,
                ],
                'items' => [
                    ['purchaseId' => $purId('PUR-PHA-005'), 'qty' => 20, 'salePrice' => '260', 'buyPrice' => '190', 'totalSale' => '5200', 'totalPurchase' => '3800', 'profitTotal' => '1400', 'profitMargin' => '26.92', 'warranty_days' => null],
                ],
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
