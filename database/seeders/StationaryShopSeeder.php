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
 * Stationary shop demo seeder.
 */
class StationaryShopSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $bid = $this->businessId;

        // Brands
        foreach (['Pilot', 'Faber-Castell', 'Deli', 'Maped', 'Classmate', 'PaperLine'] as $b) {
            DB::table('brands')->insertOrIgnore(['name' => $b, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Categories
        $cats = ['Writing Instruments', 'Notebooks & Paper', 'Office Files', 'Art & Craft', 'School Supplies', 'Desk Accessories'];
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
            ['name' => 'EduMart Wholesale', 'mail' => 'orders@edumart.com', 'mobile' => '0361-2001001', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'New Market', 'openingBalance' => 0],
            ['name' => 'OfficePlus Distribution', 'mail' => 'supply@officeplus.com', 'mobile' => '0361-2002002', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Motijheel', 'openingBalance' => 4500],
            ['name' => 'PaperHub Traders', 'mail' => 'sales@paperhub.com', 'mobile' => '0361-2003003', 'country' => 'Bangladesh', 'state' => 'Rajshahi', 'city' => 'Rajshahi', 'area' => 'Boalia', 'openingBalance' => 0],
        ];
        foreach ($suppliers as $s) {
            $s['businessId'] = $bid;
            Supplier::updateOrCreate(['mail' => $s['mail'], 'businessId' => $bid], $s);
        }

        // Customers
        $customers = [
            ['name' => 'Sunrise School Store', 'mail' => 'sunrise.school@stationary.com', 'mobile' => '0362-2101001', 'country' => 'Bangladesh', 'state' => 'Dhaka', 'city' => 'Dhaka', 'area' => 'Dhanmondi', 'openingBalance' => 0, 'businessId' => $bid],
            ['name' => 'Campus Point', 'mail' => 'campus.point@stationary.com', 'mobile' => '0362-2102002', 'country' => 'Bangladesh', 'state' => 'Sylhet', 'city' => 'Sylhet', 'area' => 'Zindabazar', 'openingBalance' => 1400, 'businessId' => $bid],
            ['name' => 'Office Desk BD', 'mail' => 'office.desk@stationary.com', 'mobile' => '0362-2103003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'GEC Circle', 'openingBalance' => 0, 'businessId' => $bid],
            ['name' => 'Creative Kids Hub', 'mail' => 'creative.kids@stationary.com', 'mobile' => '0362-2104004', 'country' => 'Bangladesh', 'state' => 'Khulna', 'city' => 'Khulna', 'area' => 'Khalishpur', 'openingBalance' => 800, 'businessId' => $bid],
        ];
        foreach ($customers as $c) {
            $c['businessId'] = $bid;
            Customer::updateOrCreate(['mail' => $c['mail'], 'businessId' => $bid], $c);
        }

        // Products
        $products = [
            ['name' => 'Pilot Ballpoint Pen (Blue) Pack 12', 'brand' => $brand('Pilot'), 'category' => $cat('Writing Instruments'), 'unitName' => $unit('Box'), 'quantity' => '1', 'details' => 'Smooth blue ballpoint pens, pack of 12', 'barCode' => 'STN-PEN-BLU-12-001', 'businessId' => $bid, 'stock' => 120],
            ['name' => 'A4 Notebook 200 Pages', 'brand' => $brand('Classmate'), 'category' => $cat('Notebooks & Paper'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Hard cover ruled notebook, 200 pages', 'barCode' => 'STN-NBK-A4-200-001', 'businessId' => $bid, 'stock' => 160],
            ['name' => 'Office File Folder Pack 10', 'brand' => $brand('Deli'), 'category' => $cat('Office Files'), 'unitName' => $unit('Packet'), 'quantity' => '1', 'details' => 'Plastic file folders assorted colors', 'barCode' => 'STN-FILE-P10-001', 'businessId' => $bid, 'stock' => 70],
            ['name' => 'Color Pencil Set 24 Colors', 'brand' => $brand('Faber-Castell'), 'category' => $cat('Art & Craft'), 'unitName' => $unit('Box'), 'quantity' => '1', 'details' => 'Wooden color pencils set with 24 shades', 'barCode' => 'STN-CLR-24-001', 'businessId' => $bid, 'stock' => 85],
            ['name' => 'Geometry Box Metal', 'brand' => $brand('Maped'), 'category' => $cat('School Supplies'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Compass, divider, ruler, protractor set', 'barCode' => 'STN-GEO-MTL-001', 'businessId' => $bid, 'stock' => 90],
            ['name' => 'Sticky Notes 3x3 Pack 5', 'brand' => $brand('PaperLine'), 'category' => $cat('Desk Accessories'), 'unitName' => $unit('Packet'), 'quantity' => '1', 'details' => '5 pads colored sticky notes', 'barCode' => 'STN-STKY-3X3-001', 'businessId' => $bid, 'stock' => 110],
            ['name' => 'A4 Copier Paper 80gsm Ream', 'brand' => $brand('PaperLine'), 'category' => $cat('Notebooks & Paper'), 'unitName' => $unit('Packet'), 'quantity' => '1', 'details' => '500 sheets multipurpose copier paper', 'barCode' => 'STN-A4-REAM-001', 'businessId' => $bid, 'stock' => 75],
            ['name' => 'Whiteboard Marker Set 4', 'brand' => $brand('Deli'), 'category' => $cat('Writing Instruments'), 'unitName' => $unit('Set'), 'quantity' => '1', 'details' => '4 color dry erase marker set', 'barCode' => 'STN-WBM-SET4-001', 'businessId' => $bid, 'stock' => 95],
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
        $base = Carbon::now()->subDays(30);

        $purchases = [
            ['productName' => $pId('STN-PEN-BLU-12-001'), 'supplier' => $supId('orders@edumart.com'), 'purchase_date' => $base->copy()->addDays(0)->toDateString(), 'invoice' => 'PUR-STN-001', 'reference' => 'REF-STN-001', 'qty' => 120, 'buyPrice' => '95', 'salePriceExVat' => '140', 'vatStatus' => 'exclusive', 'salePriceInVat' => '154', 'profit' => '45', 'totalAmount' => '11400', 'disType' => 'flat', 'disAmount' => '400', 'disParcent' => '0', 'grandTotal' => '11000', 'paidAmount' => '11000', 'dueAmount' => '0', 'specialNote' => 'Pens monthly stock', 'businessId' => $bid],
            ['productName' => $pId('STN-NBK-A4-200-001'), 'supplier' => $supId('sales@paperhub.com'), 'purchase_date' => $base->copy()->addDays(4)->toDateString(), 'invoice' => 'PUR-STN-002', 'reference' => 'REF-STN-002', 'qty' => 160, 'buyPrice' => '68', 'salePriceExVat' => '95', 'vatStatus' => 'exclusive', 'salePriceInVat' => '105', 'profit' => '27', 'totalAmount' => '10880', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0', 'grandTotal' => '10880', 'paidAmount' => '7000', 'dueAmount' => '3880', 'specialNote' => 'Notebooks partial payment', 'businessId' => $bid],
            ['productName' => $pId('STN-CLR-24-001'), 'supplier' => $supId('supply@officeplus.com'), 'purchase_date' => $base->copy()->addDays(9)->toDateString(), 'invoice' => 'PUR-STN-003', 'reference' => 'REF-STN-003', 'qty' => 85, 'buyPrice' => '150', 'salePriceExVat' => '220', 'vatStatus' => 'exclusive', 'salePriceInVat' => '242', 'profit' => '70', 'totalAmount' => '12750', 'disType' => 'flat', 'disAmount' => '750', 'disParcent' => '0', 'grandTotal' => '12000', 'paidAmount' => '12000', 'dueAmount' => '0', 'specialNote' => 'Color pencil set lot', 'businessId' => $bid],
            ['productName' => $pId('STN-A4-REAM-001'), 'supplier' => $supId('sales@paperhub.com'), 'purchase_date' => $base->copy()->addDays(15)->toDateString(), 'invoice' => 'PUR-STN-004', 'reference' => 'REF-STN-004', 'qty' => 75, 'buyPrice' => '320', 'salePriceExVat' => '420', 'vatStatus' => 'exclusive', 'salePriceInVat' => '462', 'profit' => '100', 'totalAmount' => '24000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0', 'grandTotal' => '24000', 'paidAmount' => '24000', 'dueAmount' => '0', 'specialNote' => 'Copier paper reams stock', 'businessId' => $bid],
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
        $base = Carbon::now()->subDays(22);
        $sales = [
            [
                'header' => ['date' => $base->copy()->addDays(2)->toDateString(), 'invoice' => 'INV-STN-001', 'customerId' => $custId('sunrise.school@stationary.com'), 'reference' => '', 'note' => 'School session starter order', 'totalSale' => '8100', 'discountAmount' => '600', 'grandTotal' => '7500', 'paidAmount' => '7500', 'invoiceDue' => '0', 'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => $bid],
                'items' => [
                    ['purchaseId' => $purId('PUR-STN-001'), 'qty' => 30, 'salePrice' => '140', 'buyPrice' => '95', 'totalSale' => '4200', 'totalPurchase' => '2850', 'profitTotal' => '1350', 'profitMargin' => '32.14', 'warranty_days' => null],
                    ['purchaseId' => $purId('PUR-STN-002'), 'qty' => 20, 'salePrice' => '95', 'buyPrice' => '68', 'totalSale' => '1900', 'totalPurchase' => '1360', 'profitTotal' => '540', 'profitMargin' => '28.42', 'warranty_days' => null],
                    ['purchaseId' => null, 'qty' => 5, 'salePrice' => '400', 'buyPrice' => '280', 'totalSale' => '2000', 'totalPurchase' => '1400', 'profitTotal' => '600', 'profitMargin' => '30.00', 'warranty_days' => null],
                ],
            ],
            [
                'header' => ['date' => $base->copy()->addDays(7)->toDateString(), 'invoice' => 'INV-STN-002', 'customerId' => $custId('campus.point@stationary.com'), 'reference' => '', 'note' => 'Notebook and file restock', 'totalSale' => '5770', 'discountAmount' => '170', 'grandTotal' => '5600', 'paidAmount' => '3000', 'invoiceDue' => '2600', 'prevDue' => '1400', 'curDue' => '4000', 'status' => 'partial', 'businessId' => $bid],
                'items' => [
                    ['purchaseId' => $purId('PUR-STN-002'), 'qty' => 40, 'salePrice' => '95', 'buyPrice' => '68', 'totalSale' => '3800', 'totalPurchase' => '2720', 'profitTotal' => '1080', 'profitMargin' => '28.42', 'warranty_days' => null],
                    ['purchaseId' => null, 'qty' => 12, 'salePrice' => '160', 'buyPrice' => '115', 'totalSale' => '1920', 'totalPurchase' => '1380', 'profitTotal' => '540', 'profitMargin' => '28.13', 'warranty_days' => null],
                ],
            ],
            [
                'header' => ['date' => $base->copy()->addDays(13)->toDateString(), 'invoice' => 'INV-STN-003', 'customerId' => $custId('office.desk@stationary.com'), 'reference' => '', 'note' => 'A4 paper reams bulk', 'totalSale' => '8400', 'discountAmount' => '400', 'grandTotal' => '8000', 'paidAmount' => '8000', 'invoiceDue' => '0', 'prevDue' => '0', 'curDue' => '0', 'status' => 'complete', 'businessId' => $bid],
                'items' => [[
                    'purchaseId' => $purId('PUR-STN-004'), 'qty' => 20, 'salePrice' => '420', 'buyPrice' => '320', 'totalSale' => '8400', 'totalPurchase' => '6400', 'profitTotal' => '2000', 'profitMargin' => '23.81', 'warranty_days' => null,
                ]],
            ],
            [
                'header' => ['date' => $base->copy()->addDays(18)->toDateString(), 'invoice' => 'INV-STN-004', 'customerId' => $custId('creative.kids@stationary.com'), 'reference' => '', 'note' => 'Art and school combo credit', 'totalSale' => '5460', 'discountAmount' => '160', 'grandTotal' => '5300', 'paidAmount' => '0', 'invoiceDue' => '5300', 'prevDue' => '800', 'curDue' => '6100', 'status' => 'due', 'businessId' => $bid],
                'items' => [
                    ['purchaseId' => $purId('PUR-STN-003'), 'qty' => 15, 'salePrice' => '220', 'buyPrice' => '150', 'totalSale' => '3300', 'totalPurchase' => '2250', 'profitTotal' => '1050', 'profitMargin' => '31.82', 'warranty_days' => null],
                    ['purchaseId' => null, 'qty' => 10, 'salePrice' => '95', 'buyPrice' => '68', 'totalSale' => '950', 'totalPurchase' => '680', 'profitTotal' => '270', 'profitMargin' => '28.42', 'warranty_days' => null],
                    ['purchaseId' => null, 'qty' => 5, 'salePrice' => '242', 'buyPrice' => '150', 'totalSale' => '1210', 'totalPurchase' => '750', 'profitTotal' => '460', 'profitMargin' => '38.02', 'warranty_days' => null],
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




