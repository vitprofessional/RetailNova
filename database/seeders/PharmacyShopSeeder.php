<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Supplier;
use Carbon\Carbon;
use Database\Seeders\Concerns\BusinessTypeSeedable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for a Pharmacy / Medicine Shop.
 */
class PharmacyShopSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $bid = $this->businessId;

        foreach (['Square', 'Beximco', 'Incepta', 'Renata', 'ACME', 'Healthcare', 'Generic'] as $brandName) {
            DB::table('brands')->insertOrIgnore([
                'name' => $brandName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $categories = [
            'Tablets',
            'Capsules',
            'Syrups',
            'Drops',
            'Antibiotics',
            'Vitamins & Supplements',
            'Medical Devices',
            'Personal Care',
        ];

        foreach ($categories as $categoryName) {
            DB::table('categories')->insertOrIgnore([
                'name' => $categoryName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $brand = fn(string $name) => (string) (DB::table('brands')->where('name', $name)->value('id') ?? 1);
        $category = fn(string $name) => (string) (DB::table('categories')->where('name', $name)->value('id') ?? 1);
        $unit = fn(string $name) => (string) (DB::table('product_units')->where('name', $name)->value('id') ?? 1);
        $supplierId = fn(string $mail) => DB::table('suppliers')->where('mail', $mail)->value('id');
        $customerId = fn(string $mail) => DB::table('customers')->where('mail', $mail)->value('id');
        $purchaseId = fn(string $invoice) => DB::table('purchase_products')->where('invoice', $invoice)->value('id');
        $productId = fn(string $barcode) => DB::table('products')->where('barCode', $barcode)->value('id');

        $suppliers = [
            ['name' => 'Square Pharma Distribution', 'mail' => 'supply@squarepharma.com', 'mobile' => '0311-7001001', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Tejgaon', 'openingBalance' => 0],
            ['name' => 'HealthCare Medicine Depot', 'mail' => 'orders@healthcaredistribution.com', 'mobile' => '0311-7002002', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Pahartali', 'openingBalance' => 12000],
            ['name' => 'Renata Pharma Link', 'mail' => 'trade@renatalink.com', 'mobile' => '0311-7003003', 'country' => 'Bangladesh', 'state' => 'Khulna', 'city' => 'Khulna', 'area' => 'Khalishpur', 'openingBalance' => 0],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(['mail' => $supplier['mail']], $supplier);
        }

        $customers = [
            ['name' => 'Rahman Clinic Pharmacy', 'mail' => 'rahman.clinic@email.com', 'mobile' => '0321-7101001', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Dhanmondi', 'openingBalance' => 0, 'businessId' => $bid],
            ['name' => 'Sadia Begum', 'mail' => 'sadia.begum@email.com', 'mobile' => '0321-7102002', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Mohammadpur', 'openingBalance' => 850, 'businessId' => $bid],
            ['name' => 'MediCare Center', 'mail' => 'medicare.center@email.com', 'mobile' => '0321-7103003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Nasirabad', 'openingBalance' => 0, 'businessId' => $bid],
            ['name' => 'Dr. Anika Sultana', 'mail' => 'dr.anika@email.com', 'mobile' => '0321-7104004', 'country' => 'Bangladesh', 'state' => 'Rajshahi', 'city' => 'Rajshahi', 'area' => 'Laxmipur', 'openingBalance' => 0, 'businessId' => $bid],
            ['name' => 'Zaman Diagnostics', 'mail' => 'zaman.diagnostics@email.com', 'mobile' => '0321-7105005', 'country' => 'Bangladesh', 'state' => 'Sylhet', 'city' => 'Sylhet', 'area' => 'Amberkhana', 'openingBalance' => 2500, 'businessId' => $bid],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(['mail' => $customer['mail']], $customer);
        }

        $products = [
            ['name' => 'Napa 500mg', 'brand' => $brand('Beximco'), 'category' => $category('Tablets'), 'unitName' => $unit('Box'), 'quantity' => '20', 'details' => 'Paracetamol 500mg, 10 strips x 10 tablets', 'barCode' => 'PHA-NAPA-001', 'businessId' => $bid, 'stock' => 120, 'buy' => 180, 'sell' => 220],
            ['name' => 'Seclo 20mg', 'brand' => $brand('Square'), 'category' => $category('Capsules'), 'unitName' => $unit('Box'), 'quantity' => '10', 'details' => 'Omeprazole capsule, 10 strips x 10 capsules', 'barCode' => 'PHA-SECLO-001', 'businessId' => $bid, 'stock' => 80, 'buy' => 520, 'sell' => 620],
            ['name' => 'Ace 500mg', 'brand' => $brand('Square'), 'category' => $category('Tablets'), 'unitName' => $unit('Box'), 'quantity' => '20', 'details' => 'Paracetamol tablet, 10 strips x 10 tablets', 'barCode' => 'PHA-ACE-001', 'businessId' => $bid, 'stock' => 110, 'buy' => 190, 'sell' => 230],
            ['name' => 'Monas 10mg', 'brand' => $brand('Incepta'), 'category' => $category('Tablets'), 'unitName' => $unit('Box'), 'quantity' => '10', 'details' => 'Montelukast 10mg tablet', 'barCode' => 'PHA-MONAS-001', 'businessId' => $bid, 'stock' => 70, 'buy' => 300, 'sell' => 380],
            ['name' => 'Ceevit 250mg', 'brand' => $brand('Healthcare'), 'category' => $category('Vitamins & Supplements'), 'unitName' => $unit('Box'), 'quantity' => '10', 'details' => 'Vitamin C chewable tablet', 'barCode' => 'PHA-CEEVIT-001', 'businessId' => $bid, 'stock' => 90, 'buy' => 210, 'sell' => 280],
            ['name' => 'Losectil Syrup', 'brand' => $brand('Renata'), 'category' => $category('Syrups'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Pediatric syrup bottle 100ml', 'barCode' => 'PHA-LOSY-001', 'businessId' => $bid, 'stock' => 45, 'buy' => 85, 'sell' => 120],
            ['name' => 'Azithro 500mg', 'brand' => $brand('ACME'), 'category' => $category('Antibiotics'), 'unitName' => $unit('Box'), 'quantity' => '5', 'details' => 'Azithromycin 500mg', 'barCode' => 'PHA-AZI-001', 'businessId' => $bid, 'stock' => 60, 'buy' => 320, 'sell' => 410],
            ['name' => 'ORS Powder Pack', 'brand' => $brand('Generic'), 'category' => $category('Personal Care'), 'unitName' => $unit('Box'), 'quantity' => '20', 'details' => 'Oral rehydration salts pack', 'barCode' => 'PHA-ORS-001', 'businessId' => $bid, 'stock' => 150, 'buy' => 90, 'sell' => 130],
            ['name' => 'MaxPro Junior Syrup', 'brand' => $brand('Renata'), 'category' => $category('Syrups'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Kids vitamin syrup 100ml', 'barCode' => 'PHA-MAXPRO-001', 'businessId' => $bid, 'stock' => 35, 'buy' => 140, 'sell' => 185],
            ['name' => 'Digital Thermometer', 'brand' => $brand('Generic'), 'category' => $category('Medical Devices'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Flexible tip digital thermometer', 'barCode' => 'PHA-THERMO-001', 'businessId' => $bid, 'stock' => 25, 'buy' => 160, 'sell' => 240],
            ['name' => 'Blood Pressure Monitor', 'brand' => $brand('Generic'), 'category' => $category('Medical Devices'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Automatic digital BP machine', 'barCode' => 'PHA-BP-001', 'businessId' => $bid, 'stock' => 12, 'buy' => 1650, 'sell' => 2200],
            ['name' => 'Tobrex Eye Drops', 'brand' => $brand('Healthcare'), 'category' => $category('Drops'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Eye drops 5ml bottle', 'barCode' => 'PHA-TOBREX-001', 'businessId' => $bid, 'stock' => 30, 'buy' => 95, 'sell' => 145],
        ];

        foreach ($products as $data) {
            $stock = $data['stock'];
            unset($data['stock'], $data['buy'], $data['sell']);

            $product = Product::updateOrCreate(['barCode' => $data['barCode']], $data);

            if ($product->stocks()->sum('currentStock') == 0) {
                ProductStock::create([
                    'productId' => $product->id,
                    'purchaseId' => null,
                    'currentStock' => $stock,
                    'businessId' => $bid,
                ]);
            }
        }

        $baseDate = Carbon::now()->subDays(36);
        $purchases = [
            ['productName' => $productId('PHA-NAPA-001'), 'supplier' => $supplierId('supply@squarepharma.com'), 'purchase_date' => $baseDate->copy()->addDays(0)->toDateString(), 'invoice' => 'PUR-PHA-001', 'reference' => 'REF-PHA-001', 'qty' => 120, 'buyPrice' => '180', 'salePriceExVat' => '220', 'vatStatus' => 'exclusive', 'salePriceInVat' => '242', 'profit' => '40', 'totalAmount' => '21600', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0', 'grandTotal' => '21600', 'paidAmount' => '21600', 'dueAmount' => '0', 'specialNote' => 'Napa opening stock', 'businessId' => $bid],
            ['productName' => $productId('PHA-SECLO-001'), 'supplier' => $supplierId('supply@squarepharma.com'), 'purchase_date' => $baseDate->copy()->addDays(2)->toDateString(), 'invoice' => 'PUR-PHA-002', 'reference' => 'REF-PHA-002', 'qty' => 80, 'buyPrice' => '520', 'salePriceExVat' => '620', 'vatStatus' => 'exclusive', 'salePriceInVat' => '682', 'profit' => '100', 'totalAmount' => '41600', 'disType' => 'flat', 'disAmount' => '1600', 'disParcent' => '0', 'grandTotal' => '40000', 'paidAmount' => '25000', 'dueAmount' => '15000', 'specialNote' => 'Seclo mixed payment purchase', 'businessId' => $bid],
            ['productName' => $productId('PHA-AZI-001'), 'supplier' => $supplierId('orders@healthcaredistribution.com'), 'purchase_date' => $baseDate->copy()->addDays(5)->toDateString(), 'invoice' => 'PUR-PHA-003', 'reference' => 'REF-PHA-003', 'qty' => 60, 'buyPrice' => '320', 'salePriceExVat' => '410', 'vatStatus' => 'exclusive', 'salePriceInVat' => '451', 'profit' => '90', 'totalAmount' => '19200', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0', 'grandTotal' => '19200', 'paidAmount' => '19200', 'dueAmount' => '0', 'specialNote' => 'Antibiotics shelf refill', 'businessId' => $bid],
            ['productName' => $productId('PHA-MAXPRO-001'), 'supplier' => $supplierId('trade@renatalink.com'), 'purchase_date' => $baseDate->copy()->addDays(9)->toDateString(), 'invoice' => 'PUR-PHA-004', 'reference' => 'REF-PHA-004', 'qty' => 35, 'buyPrice' => '140', 'salePriceExVat' => '185', 'vatStatus' => 'exclusive', 'salePriceInVat' => '203.5', 'profit' => '45', 'totalAmount' => '4900', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0', 'grandTotal' => '4900', 'paidAmount' => '4900', 'dueAmount' => '0', 'specialNote' => 'Kids syrup stock', 'businessId' => $bid],
            ['productName' => $productId('PHA-BP-001'), 'supplier' => $supplierId('orders@healthcaredistribution.com'), 'purchase_date' => $baseDate->copy()->addDays(12)->toDateString(), 'invoice' => 'PUR-PHA-005', 'reference' => 'REF-PHA-005', 'qty' => 12, 'buyPrice' => '1650', 'salePriceExVat' => '2200', 'vatStatus' => 'exclusive', 'salePriceInVat' => '2420', 'profit' => '550', 'totalAmount' => '19800', 'disType' => 'flat', 'disAmount' => '1800', 'disParcent' => '0', 'grandTotal' => '18000', 'paidAmount' => '10000', 'dueAmount' => '8000', 'specialNote' => 'Medical devices batch', 'businessId' => $bid],
            ['productName' => $productId('PHA-ACE-001'), 'supplier' => $supplierId('supply@squarepharma.com'), 'purchase_date' => $baseDate->copy()->addDays(14)->toDateString(), 'invoice' => 'PUR-PHA-006', 'reference' => 'REF-PHA-006', 'qty' => 110, 'buyPrice' => '190', 'salePriceExVat' => '230', 'vatStatus' => 'exclusive', 'salePriceInVat' => '253', 'profit' => '40', 'totalAmount' => '20900', 'disType' => 'flat', 'disAmount' => '900', 'disParcent' => '0', 'grandTotal' => '20000', 'paidAmount' => '20000', 'dueAmount' => '0', 'specialNote' => 'Ace tablet replenishment', 'businessId' => $bid],
            ['productName' => $productId('PHA-LOSY-001'), 'supplier' => $supplierId('trade@renatalink.com'), 'purchase_date' => $baseDate->copy()->addDays(17)->toDateString(), 'invoice' => 'PUR-PHA-007', 'reference' => 'REF-PHA-007', 'qty' => 45, 'buyPrice' => '85', 'salePriceExVat' => '120', 'vatStatus' => 'exclusive', 'salePriceInVat' => '132', 'profit' => '35', 'totalAmount' => '3825', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0', 'grandTotal' => '3825', 'paidAmount' => '3825', 'dueAmount' => '0', 'specialNote' => 'Losectil syrup batch', 'businessId' => $bid],
            ['productName' => $productId('PHA-THERMO-001'), 'supplier' => $supplierId('orders@healthcaredistribution.com'), 'purchase_date' => $baseDate->copy()->addDays(20)->toDateString(), 'invoice' => 'PUR-PHA-008', 'reference' => 'REF-PHA-008', 'qty' => 25, 'buyPrice' => '160', 'salePriceExVat' => '240', 'vatStatus' => 'exclusive', 'salePriceInVat' => '264', 'profit' => '80', 'totalAmount' => '4000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0', 'grandTotal' => '4000', 'paidAmount' => '4000', 'dueAmount' => '0', 'specialNote' => 'Thermometer stock arrival', 'businessId' => $bid],
            ['productName' => $productId('PHA-TOBREX-001'), 'supplier' => $supplierId('orders@healthcaredistribution.com'), 'purchase_date' => $baseDate->copy()->addDays(22)->toDateString(), 'invoice' => 'PUR-PHA-009', 'reference' => 'REF-PHA-009', 'qty' => 30, 'buyPrice' => '95', 'salePriceExVat' => '145', 'vatStatus' => 'exclusive', 'salePriceInVat' => '159.5', 'profit' => '50', 'totalAmount' => '2850', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0', 'grandTotal' => '2850', 'paidAmount' => '2850', 'dueAmount' => '0', 'specialNote' => 'Eye drops restock', 'businessId' => $bid],
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

        $salesBaseDate = Carbon::now()->subDays(28);
        $sales = [
            [
                'header' => ['date' => $salesBaseDate->copy()->addDays(1)->toDateString(), 'invoice' => 'INV-PHA-001', 'customerId' => $customerId('rahman.clinic@email.com'), 'reference' => '', 'note' => 'Napa and Ace clinic purchase', 'totalSale' => '4500', 'discountAmount' => '200', 'grandTotal' => '4300', 'paidAmount' => '4300', 'invoiceDue' => '0', 'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => $bid],
                'items' => [
                    ['purchaseId' => $purchaseId('PUR-PHA-001'), 'qty' => 10, 'salePrice' => '220', 'buyPrice' => '180', 'totalSale' => '2200', 'totalPurchase' => '1800', 'profitTotal' => '400', 'profitMargin' => '18.18', 'warranty_days' => null],
                    ['purchaseId' => $purchaseId('PUR-PHA-006'), 'qty' => 10, 'salePrice' => '230', 'buyPrice' => '190', 'totalSale' => '2300', 'totalPurchase' => '1900', 'profitTotal' => '400', 'profitMargin' => '17.39', 'warranty_days' => null],
                ],
            ],
            [
                'header' => ['date' => $salesBaseDate->copy()->addDays(4)->toDateString(), 'invoice' => 'INV-PHA-002', 'customerId' => $customerId('sadia.begum@email.com'), 'reference' => '', 'note' => 'Seclo and MaxPro family purchase', 'totalSale' => '805', 'discountAmount' => '20', 'grandTotal' => '785', 'paidAmount' => '500', 'invoiceDue' => '285', 'prevDue' => '850', 'curDue' => '1135', 'status' => 'partial', 'businessId' => $bid],
                'items' => [
                    ['purchaseId' => $purchaseId('PUR-PHA-002'), 'qty' => 1, 'salePrice' => '620', 'buyPrice' => '520', 'totalSale' => '620', 'totalPurchase' => '520', 'profitTotal' => '100', 'profitMargin' => '16.13', 'warranty_days' => null],
                    ['purchaseId' => $purchaseId('PUR-PHA-004'), 'qty' => 1, 'salePrice' => '185', 'buyPrice' => '140', 'totalSale' => '185', 'totalPurchase' => '140', 'profitTotal' => '45', 'profitMargin' => '24.32', 'warranty_days' => null],
                ],
            ],
            [
                'header' => ['date' => $salesBaseDate->copy()->addDays(8)->toDateString(), 'invoice' => 'INV-PHA-003', 'customerId' => $customerId('medicare.center@email.com'), 'reference' => '', 'note' => 'Antibiotics and syrup order', 'totalSale' => '1760', 'discountAmount' => '60', 'grandTotal' => '1700', 'paidAmount' => '1700', 'invoiceDue' => '0', 'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => $bid],
                'items' => [
                    ['purchaseId' => $purchaseId('PUR-PHA-003'), 'qty' => 4, 'salePrice' => '410', 'buyPrice' => '320', 'totalSale' => '1640', 'totalPurchase' => '1280', 'profitTotal' => '360', 'profitMargin' => '21.95', 'warranty_days' => null],
                    ['purchaseId' => $purchaseId('PUR-PHA-007'), 'qty' => 1, 'salePrice' => '120', 'buyPrice' => '85', 'totalSale' => '120', 'totalPurchase' => '85', 'profitTotal' => '35', 'profitMargin' => '29.17', 'warranty_days' => null],
                ],
            ],
            [
                'header' => ['date' => $salesBaseDate->copy()->addDays(13)->toDateString(), 'invoice' => 'INV-PHA-004', 'customerId' => $customerId('dr.anika@email.com'), 'reference' => '', 'note' => 'Digital thermometer and eye drops', 'totalSale' => '385', 'discountAmount' => '0', 'grandTotal' => '385', 'paidAmount' => '385', 'invoiceDue' => '0', 'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => $bid],
                'items' => [
                    ['purchaseId' => $purchaseId('PUR-PHA-008'), 'qty' => 1, 'salePrice' => '240', 'buyPrice' => '160', 'totalSale' => '240', 'totalPurchase' => '160', 'profitTotal' => '80', 'profitMargin' => '33.33', 'warranty_days' => '180'],
                    ['purchaseId' => $purchaseId('PUR-PHA-009'), 'qty' => 1, 'salePrice' => '145', 'buyPrice' => '95', 'totalSale' => '145', 'totalPurchase' => '95', 'profitTotal' => '50', 'profitMargin' => '34.48', 'warranty_days' => null],
                ],
            ],
            [
                'header' => ['date' => $salesBaseDate->copy()->addDays(18)->toDateString(), 'invoice' => 'INV-PHA-005', 'customerId' => $customerId('zaman.diagnostics@email.com'), 'reference' => '', 'note' => 'Blood pressure monitor on credit', 'totalSale' => '2200', 'discountAmount' => '0', 'grandTotal' => '2200', 'paidAmount' => '0', 'invoiceDue' => '2200', 'prevDue' => '2500', 'curDue' => '4700', 'status' => 'due', 'businessId' => $bid],
                'items' => [
                    ['purchaseId' => $purchaseId('PUR-PHA-005'), 'qty' => 1, 'salePrice' => '2200', 'buyPrice' => '1650', 'totalSale' => '2200', 'totalPurchase' => '1650', 'profitTotal' => '550', 'profitMargin' => '25.00', 'warranty_days' => '365'],
                ],
            ],
        ];

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
                DB::table('invoice_items')->insert(array_merge($item, [
                    'saleId' => $saleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }
}