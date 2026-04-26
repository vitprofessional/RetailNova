<?php

namespace Database\Seeders;

use App\Models\Customer;
use Database\Seeders\Concerns\BusinessTypeSeedable;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * General store demo seeder.
 */
class GeneralShopSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $bid = $this->businessId;

        // Brands
        foreach (['FreshHome', 'DailyCare', 'PrimeChoice', 'UrbanSelect', 'HouseMate', 'KitchenPro'] as $b) {
            DB::table('brands')->insertOrIgnore(['name' => $b, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Categories
        $cats = ['Rice, Flour & Staples', 'Snacks & Biscuits', 'Beverages', 'Personal Care', 'Cleaning Supplies', 'Kitchen Essentials', 'Baby Care'];
        foreach ($cats as $c) {
            DB::table('categories')->insertOrIgnore(['name' => $c, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Helpers
        $brand = fn(string $n) => (string) (DB::table('brands')->where('name', $n)->value('id') ?? 1);
        $cat = fn(string $n) => (string) (DB::table('categories')->where('name', $n)->value('id') ?? 1);
        $unit = fn(string $n) => (string) (DB::table('product_units')->where('name', $n)->value('id') ?? 1);
        $supId = fn(string $mail) => DB::table('suppliers')->where('businessId', $bid)->where('mail', $mail)->value('id');
        $custId = fn(string $mail) => DB::table('customers')->where('businessId', $bid)->where('mail', $mail)->value('id');
        $purId = fn(string $inv) => DB::table('purchase_products')->where('businessId', $bid)->where('invoice', $inv)->value('id');

        // Suppliers
        $suppliers = [
            ['name' => 'Metro Grocery Supply', 'mail' => 'orders@metrogrocery.com', 'mobile' => '0351-1001001', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Karwan Bazar', 'openingBalance' => 0],
            ['name' => 'HomeNeeds Wholesale', 'mail' => 'supply@homeneeds.com', 'mobile' => '0351-1002002', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Mohammadpur', 'openingBalance' => 9000],
            ['name' => 'Prime FMCG Traders', 'mail' => 'sales@primefmcg.com', 'mobile' => '0351-1003003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Agrabad', 'openingBalance' => 0],
        ];
        foreach ($suppliers as $s) {
            $s['businessId'] = $bid;
            Supplier::updateOrCreate(['mail' => $s['mail'], 'businessId' => $bid], $s);
        }

        // Customers
        $customers = [
            ['name' => 'Rahman Family Store', 'mail' => 'rahman.family@genstore.com', 'mobile' => '0352-1101001', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Mirpur', 'openingBalance' => 0, 'businessId' => $bid],
            ['name' => 'Nabila Super Corner', 'mail' => 'nabila.corner@genstore.com', 'mobile' => '0352-1102002', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Uttara', 'openingBalance' => 2000, 'businessId' => $bid],
            ['name' => 'Sadiq Mini Mart', 'mail' => 'sadiq.minimart@genstore.com', 'mobile' => '0352-1103003', 'country' => 'Bangladesh', 'state' => 'Khulna', 'city' => 'Khulna', 'area' => 'Sonadanga', 'openingBalance' => 0, 'businessId' => $bid],
            ['name' => 'Arif Daily Needs', 'mail' => 'arif.daily@genstore.com', 'mobile' => '0352-1104004', 'country' => 'Bangladesh', 'state' => 'Sylhet', 'city' => 'Sylhet', 'area' => 'Ambarkhana', 'openingBalance' => 1200, 'businessId' => $bid],
        ];
        foreach ($customers as $c) {
            $c['businessId'] = $bid;
            Customer::updateOrCreate(['mail' => $c['mail'], 'businessId' => $bid], $c);
        }

        // Products
        $products = [
            ['name' => 'Premium Miniket Rice 25kg', 'brand' => $brand('FreshHome'), 'category' => $cat('Rice, Flour & Staples'), 'unitName' => $unit('Bag'), 'quantity' => '1', 'details' => '25kg premium miniket rice bag', 'barCode' => 'GEN-RICE-25KG-001', 'businessId' => $bid, 'stock' => 40],
            ['name' => 'All Purpose Flour 2kg', 'brand' => $brand('PrimeChoice'), 'category' => $cat('Rice, Flour & Staples'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Fine all-purpose wheat flour', 'barCode' => 'GEN-FLOUR-2KG-001', 'businessId' => $bid, 'stock' => 80],
            ['name' => 'Cream Biscuits 300g', 'brand' => $brand('UrbanSelect'), 'category' => $cat('Snacks & Biscuits'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Vanilla cream biscuits pack', 'barCode' => 'GEN-BISC-300G-001', 'businessId' => $bid, 'stock' => 150],
            ['name' => 'Cola Soft Drink 1L', 'brand' => $brand('PrimeChoice'), 'category' => $cat('Beverages'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => '1 liter PET bottle beverage', 'barCode' => 'GEN-COLA-1L-001', 'businessId' => $bid, 'stock' => 120],
            ['name' => 'Bath Soap 125g', 'brand' => $brand('DailyCare'), 'category' => $cat('Personal Care'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Moisturizing bathing soap', 'barCode' => 'GEN-SOAP-125G-001', 'businessId' => $bid, 'stock' => 180],
            ['name' => 'Dishwashing Liquid 500ml', 'brand' => $brand('HouseMate'), 'category' => $cat('Cleaning Supplies'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Lemon dishwashing liquid bottle', 'barCode' => 'GEN-DISH-500ML-001', 'businessId' => $bid, 'stock' => 90],
            ['name' => 'Sunflower Oil 5L', 'brand' => $brand('KitchenPro'), 'category' => $cat('Kitchen Essentials'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Refined edible sunflower oil', 'barCode' => 'GEN-OIL-5L-001', 'businessId' => $bid, 'stock' => 55],
            ['name' => 'Baby Diaper Pack M (44 pcs)', 'brand' => $brand('DailyCare'), 'category' => $cat('Baby Care'), 'unitName' => $unit('Packet'), 'quantity' => '1', 'details' => 'Medium size diaper pack', 'barCode' => 'GEN-DIAPER-M44-001', 'businessId' => $bid, 'stock' => 65],
        ];

        foreach ($products as $data) {
            $stock = $data['stock'];
            unset($data['stock']);
            $p = Product::updateOrCreate(['barCode' => $data['barCode'], 'businessId' => $bid], $data);
            if ($p->stocks()->sum('currentStock') == 0) {
                ProductStock::create(['productId' => $p->id, 'purchaseId' => null, 'currentStock' => $stock, 'businessId' => $bid]);
            }
        }

        // Purchases
        $pId = fn(string $bc) => DB::table('products')->where('businessId', $bid)->where('barCode', $bc)->value('id');
        $base = Carbon::now()->subDays(28);

        $purchases = [
            ['productName' => $pId('GEN-RICE-25KG-001'), 'supplier' => $supId('orders@metrogrocery.com'), 'purchase_date' => $base->copy()->addDays(0)->toDateString(), 'invoice' => 'PUR-GEN-001', 'reference' => 'REF-GEN-001', 'qty' => 40, 'buyPrice' => '2400', 'salePriceExVat' => '2900', 'vatStatus' => 'exclusive', 'salePriceInVat' => '3190', 'profit' => '500', 'totalAmount' => '96000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0', 'grandTotal' => '96000', 'paidAmount' => '96000', 'dueAmount' => '0', 'specialNote' => 'Rice monthly stock', 'businessId' => $bid],
            ['productName' => $pId('GEN-COLA-1L-001'), 'supplier' => $supId('supply@homeneeds.com'), 'purchase_date' => $base->copy()->addDays(4)->toDateString(), 'invoice' => 'PUR-GEN-002', 'reference' => 'REF-GEN-002', 'qty' => 120, 'buyPrice' => '60', 'salePriceExVat' => '85', 'vatStatus' => 'exclusive', 'salePriceInVat' => '94', 'profit' => '25', 'totalAmount' => '7200', 'disType' => 'flat', 'disAmount' => '200', 'disParcent' => '0', 'grandTotal' => '7000', 'paidAmount' => '7000', 'dueAmount' => '0', 'specialNote' => 'Beverage restock', 'businessId' => $bid],
            ['productName' => $pId('GEN-SOAP-125G-001'), 'supplier' => $supId('sales@primefmcg.com'), 'purchase_date' => $base->copy()->addDays(9)->toDateString(), 'invoice' => 'PUR-GEN-003', 'reference' => 'REF-GEN-003', 'qty' => 180, 'buyPrice' => '38', 'salePriceExVat' => '55', 'vatStatus' => 'exclusive', 'salePriceInVat' => '61', 'profit' => '17', 'totalAmount' => '6840', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0', 'grandTotal' => '6840', 'paidAmount' => '3000', 'dueAmount' => '3840', 'specialNote' => 'Personal care lot partial payment', 'businessId' => $bid],
            ['productName' => $pId('GEN-OIL-5L-001'), 'supplier' => $supId('orders@metrogrocery.com'), 'purchase_date' => $base->copy()->addDays(14)->toDateString(), 'invoice' => 'PUR-GEN-004', 'reference' => 'REF-GEN-004', 'qty' => 55, 'buyPrice' => '780', 'salePriceExVat' => '980', 'vatStatus' => 'exclusive', 'salePriceInVat' => '1078', 'profit' => '200', 'totalAmount' => '42900', 'disType' => 'flat', 'disAmount' => '900', 'disParcent' => '0', 'grandTotal' => '42000', 'paidAmount' => '42000', 'dueAmount' => '0', 'specialNote' => 'Edible oil batch', 'businessId' => $bid],
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
            if ($stock) {
                $stock->update(['purchaseId' => $id]);
            }
        }

        // Sales
        $base = Carbon::now()->subDays(20);
        $sales = [
            [
                'header' => ['date' => $base->copy()->addDays(1)->toDateString(), 'invoice' => 'INV-GEN-001', 'customerId' => $custId('rahman.family@genstore.com'), 'reference' => '', 'note' => 'Rice + flour + biscuits', 'totalSale' => '6340', 'discountAmount' => '140', 'grandTotal' => '6200', 'paidAmount' => '6200', 'invoiceDue' => '0', 'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => $bid],
                'items' => [
                    ['purchaseId' => $purId('PUR-GEN-001'), 'qty' => 1, 'salePrice' => '2900', 'buyPrice' => '2400', 'totalSale' => '2900', 'totalPurchase' => '2400', 'profitTotal' => '500', 'profitMargin' => '17.24', 'warranty_days' => null],
                    ['purchaseId' => null, 'qty' => 2, 'salePrice' => '140', 'buyPrice' => '110', 'totalSale' => '280', 'totalPurchase' => '220', 'profitTotal' => '60', 'profitMargin' => '21.43', 'warranty_days' => null],
                    ['purchaseId' => null, 'qty' => 4, 'salePrice' => '85', 'buyPrice' => '60', 'totalSale' => '340', 'totalPurchase' => '240', 'profitTotal' => '100', 'profitMargin' => '29.41', 'warranty_days' => null],
                ],
            ],
            [
                'header' => ['date' => $base->copy()->addDays(5)->toDateString(), 'invoice' => 'INV-GEN-002', 'customerId' => $custId('nabila.corner@genstore.com'), 'reference' => '', 'note' => 'Soap and dishwashing liquid', 'totalSale' => '3530', 'discountAmount' => '130', 'grandTotal' => '3400', 'paidAmount' => '2000', 'invoiceDue' => '1400', 'prevDue' => '2000', 'curDue' => '3400', 'status' => 'partial', 'businessId' => $bid],
                'items' => [
                    ['purchaseId' => $purId('PUR-GEN-003'), 'qty' => 40, 'salePrice' => '55', 'buyPrice' => '38', 'totalSale' => '2200', 'totalPurchase' => '1520', 'profitTotal' => '680', 'profitMargin' => '30.91', 'warranty_days' => null],
                    ['purchaseId' => null, 'qty' => 10, 'salePrice' => '120', 'buyPrice' => '80', 'totalSale' => '1200', 'totalPurchase' => '800', 'profitTotal' => '400', 'profitMargin' => '33.33', 'warranty_days' => null],
                ],
            ],
            [
                'header' => ['date' => $base->copy()->addDays(10)->toDateString(), 'invoice' => 'INV-GEN-003', 'customerId' => $custId('sadiq.minimart@genstore.com'), 'reference' => '', 'note' => 'Sunflower oil x6', 'totalSale' => '5880', 'discountAmount' => '280', 'grandTotal' => '5600', 'paidAmount' => '5600', 'invoiceDue' => '0', 'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => $bid],
                'items' => [[
                    'purchaseId' => $purId('PUR-GEN-004'), 'qty' => 6, 'salePrice' => '980', 'buyPrice' => '780', 'totalSale' => '5880', 'totalPurchase' => '4680', 'profitTotal' => '1200', 'profitMargin' => '20.41', 'warranty_days' => null,
                ]],
            ],
            [
                'header' => ['date' => $base->copy()->addDays(15)->toDateString(), 'invoice' => 'INV-GEN-004', 'customerId' => $custId('arif.daily@genstore.com'), 'reference' => '', 'note' => 'Mixed monthly essentials', 'totalSale' => '4995', 'discountAmount' => '0', 'grandTotal' => '4995', 'paidAmount' => '0', 'invoiceDue' => '4995', 'prevDue' => '1200', 'curDue' => '6195', 'status' => 'due', 'businessId' => $bid],
                'items' => [
                    ['purchaseId' => $purId('PUR-GEN-002'), 'qty' => 15, 'salePrice' => '85', 'buyPrice' => '60', 'totalSale' => '1275', 'totalPurchase' => '900', 'profitTotal' => '375', 'profitMargin' => '29.41', 'warranty_days' => null],
                    ['purchaseId' => $purId('PUR-GEN-003'), 'qty' => 30, 'salePrice' => '55', 'buyPrice' => '38', 'totalSale' => '1650', 'totalPurchase' => '1140', 'profitTotal' => '510', 'profitMargin' => '30.91', 'warranty_days' => null],
                    ['purchaseId' => null, 'qty' => 3, 'salePrice' => '690', 'buyPrice' => '540', 'totalSale' => '2070', 'totalPurchase' => '1620', 'profitTotal' => '450', 'profitMargin' => '21.74', 'warranty_days' => null],
                ],
            ],
        ];

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




