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
 * Demo data for a Toy & Play Items Shop
 */
class ToyShopSeeder extends Seeder
{
    use BusinessTypeSeedable;

    public function run(): void
    {
        $bid = $this->businessId;

        // ── Brands ──────────────────────────────────────────────────────────
        foreach (['LEGO', 'Mattel', 'Hasbro', 'Sylvanian Families', 'Melissa & Doug', 'Nerf', 'Hot Wheels', 'Barbie', 'Fisher-Price', 'PlayMobil'] as $b) {
            DB::table('brands')->insertOrIgnore(['name' => $b, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ── Categories ──────────────────────────────────────────────────────
        $cats = ['Action Figures', 'Building Blocks', 'Dolls & Accessories', 'Vehicles & Tracks', 
                 'Outdoor & Sports Toys', 'Educational Toys', 'Stuffed Animals', 'Board Games & Puzzles', 'Play Sets', 'Remote Control Toys'];
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
            ['name' => 'Global Toys Distributors', 'mail' => 'orders@globaltoysdist.com',  'mobile' => '0311-5001001', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Bashundhara',   'openingBalance' => 0],
            ['name' => 'Playworld Supplies',       'mail' => 'supply@playworld.com',       'mobile' => '0311-5002002', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Narayanganj', 'area' => 'Siddeshwari',   'openingBalance' => 18000],
            ['name' => 'Fun Factory Imports',      'mail' => 'sales@funfactoryimports.com','mobile' => '0311-5003003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Ramna',         'openingBalance' => 0],
        ];
        foreach ($suppliers as $s) {
            $s['businessId'] = $bid;
            Supplier::updateOrCreate(['mail' => $s['mail'], 'businessId' => $bid], $s);
        }

        // ── Customers ────────────────────────────────────────────────────────
        $customers = [
            ['name' => 'Joy Kids Store',        'mail' => 'joystore@kids.com',         'mobile' => '0321-6001001', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Motijheel',    'openingBalance' => 0,    'businessId' => $bid],
            ['name' => 'Playtime Retailer',     'mail' => 'playtime@retail.com',       'mobile' => '0321-6002002', 'country' => 'Bangladesh', 'state' => 'Dhaka',      'city' => 'Dhaka',      'area' => 'Banani',       'openingBalance' => 5000, 'businessId' => $bid],
            ['name' => 'Toy Kingdom Shop',      'mail' => 'toykingdom@shop.com',       'mobile' => '0321-6003003', 'country' => 'Bangladesh', 'state' => 'Chattogram', 'city' => 'Chattogram', 'area' => 'Halishahar',   'openingBalance' => 0,    'businessId' => $bid],
            ['name' => 'Fun & Games Store',     'mail' => 'funandgames@store.com',     'mobile' => '0321-6004004', 'country' => 'Bangladesh', 'state' => 'Sylhet',     'city' => 'Sylhet',     'area' => 'Zindabazar',   'openingBalance' => 3000, 'businessId' => $bid],
            ['name' => 'Smart Kids Paradise',   'mail' => 'smartkids@paradise.com',    'mobile' => '0321-6005005', 'country' => 'Bangladesh', 'state' => 'Rajshahi',   'city' => 'Rajshahi',   'area' => 'Laxmipur',     'openingBalance' => 0,    'businessId' => $bid],
        ];
        foreach ($customers as $c) {
            $c['businessId'] = $bid;
            Customer::updateOrCreate(['mail' => $c['mail'], 'businessId' => $bid], $c);
        }

        // ── Products ─────────────────────────────────────────────────────────
        $products = [
            ['name' => 'LEGO Classic Medium Creative Box', 'brand' => $brand('LEGO'), 'category' => $cat('Building Blocks'), 'unitName' => $unit('Piece'), 'quantity' => '5', 'details' => 'LEGO Classic 484 pieces, 33 colors, inc. 12 wheels', 'barCode' => 'LGO-CLASSIC-MED-001', 'businessId' => $bid, 'stock' => 25, 'buy' => 2500, 'sell' => 4200],
            ['name' => 'Barbie Fashion Doll Blonde', 'brand' => $brand('Barbie'), 'category' => $cat('Dolls & Accessories'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Barbie Fashion Doll with Blonde Hair, 28cm Tall', 'barCode' => 'BAR-FASHION-BLN-001', 'businessId' => $bid, 'stock' => 40, 'buy' => 850, 'sell' => 1500],
            ['name' => 'Hot Wheels 20-Car Gift Pack', 'brand' => $brand('Hot Wheels'), 'category' => $cat('Vehicles & Tracks'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Hot Wheels 20-Car Collection Assorted Styles', 'barCode' => 'HW-20PACK-001', 'businessId' => $bid, 'stock' => 30, 'buy' => 1200, 'sell' => 2000],
            ['name' => 'Nerf N-Strike Elite Blaster', 'brand' => $brand('Nerf'), 'category' => $cat('Outdoor & Sports Toys'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Nerf N-Strike Elite Jolt Blaster with Foam Darts', 'barCode' => 'NRF-JOLT-001', 'businessId' => $bid, 'stock' => 20, 'buy' => 1500, 'sell' => 2500],
            ['name' => 'Sylvanian Families Starter Set', 'brand' => $brand('Sylvanian Families'), 'category' => $cat('Play Sets'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Sylvanian Families House with 3 Characters & Furniture', 'barCode' => 'SYL-STARTER-001', 'businessId' => $bid, 'stock' => 18, 'buy' => 3000, 'sell' => 5500],
            ['name' => 'Melissa & Doug Wooden Puzzle', 'brand' => $brand('Melissa & Doug'), 'category' => $cat('Board Games & Puzzles'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'M&D 100-Piece Wood Jigsaw Puzzle – Animals', 'barCode' => 'MAD-PUZZLE-001', 'businessId' => $bid, 'stock' => 35, 'buy' => 600, 'sell' => 1200],
            ['name' => 'Fisher-Price Baby Mobile', 'brand' => $brand('Fisher-Price'), 'category' => $cat('Educational Toys'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Fisher-Price Rainforest Mobile with Lights & Music', 'barCode' => 'FP-MOBILE-001', 'businessId' => $bid, 'stock' => 15, 'buy' => 2000, 'sell' => 3800],
            ['name' => 'Hasbro Monopoly Board Game', 'brand' => $brand('Hasbro'), 'category' => $cat('Board Games & Puzzles'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Monopoly Classic Edition for 2-8 Players', 'barCode' => 'HAS-MONOPOLY-001', 'businessId' => $bid, 'stock' => 22, 'buy' => 1100, 'sell' => 2000],
            ['name' => 'PlayMobil City Life School Set', 'brand' => $brand('PlayMobil'), 'category' => $cat('Play Sets'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'PlayMobil School Playset with 3 Figures & Accessories', 'barCode' => 'PM-SCHOOL-001', 'businessId' => $bid, 'stock' => 16, 'buy' => 2200, 'sell' => 4000],
            ['name' => 'Remote Control Car 360-Degree', 'brand' => $brand('Generic'), 'category' => $cat('Remote Control Toys'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => '360° RC Stunt Car with Low-Battery Indicator', 'barCode' => 'RC-STUNT-001', 'businessId' => $bid, 'stock' => 20, 'buy' => 1800, 'sell' => 3200],
            ['name' => 'Stuffed Plush Pink Flamingo', 'brand' => $brand('Generic'), 'category' => $cat('Stuffed Animals'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Soft Plush Pink Flamingo Toy 25cm', 'barCode' => 'PLUSH-FLAMINGO-001', 'businessId' => $bid, 'stock' => 50, 'buy' => 250, 'sell' => 600],
            ['name' => 'Action Figure Super Hero Set', 'brand' => $brand('Hasbro'), 'category' => $cat('Action Figures'), 'unitName' => $unit('Piece'), 'quantity' => '1', 'details' => 'Super Hero Action Figures 10-Pack Assorted', 'barCode' => 'AF-SUPERHERO-001', 'businessId' => $bid, 'stock' => 28, 'buy' => 1400, 'sell' => 2400],
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
        $base = Carbon::now()->subDays(50);

        $purchases = [
            [
                'productName' => $pId('LGO-CLASSIC-MED-001'), 'supplier' => $supId('orders@globaltoysdist.com'),
                'purchase_date' => $base->copy()->addDays(0)->toDateString(), 'invoice' => 'PUR-TOY-001',
                'reference' => 'REF-TOY-001', 'qty' => 25, 'buyPrice' => '2500', 'salePriceExVat' => '4200',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '4620', 'profit' => '1700',
                'totalAmount' => '62500', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '62500', 'paidAmount' => '62500', 'dueAmount' => '0',
                'specialNote' => 'LEGO Classic sets initial stock', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('BAR-FASHION-BLN-001'), 'supplier' => $supId('supply@playworld.com'),
                'purchase_date' => $base->copy()->addDays(5)->toDateString(), 'invoice' => 'PUR-TOY-002',
                'reference' => 'REF-TOY-002', 'qty' => 40, 'buyPrice' => '850', 'salePriceExVat' => '1500',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '1650', 'profit' => '650',
                'totalAmount' => '34000', 'disType' => 'flat', 'disAmount' => '2000', 'disParcent' => '0',
                'grandTotal' => '32000', 'paidAmount' => '20000', 'dueAmount' => '12000',
                'specialNote' => 'Barbie dolls – partial payment', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('HW-20PACK-001'), 'supplier' => $supId('sales@funfactoryimports.com'),
                'purchase_date' => $base->copy()->addDays(10)->toDateString(), 'invoice' => 'PUR-TOY-003',
                'reference' => 'REF-TOY-003', 'qty' => 30, 'buyPrice' => '1200', 'salePriceExVat' => '2000',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '2200', 'profit' => '800',
                'totalAmount' => '36000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '36000', 'paidAmount' => '36000', 'dueAmount' => '0',
                'specialNote' => 'Hot Wheels 20-car pack collections', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('NRF-JOLT-001'), 'supplier' => $supId('orders@globaltoysdist.com'),
                'purchase_date' => $base->copy()->addDays(15)->toDateString(), 'invoice' => 'PUR-TOY-004',
                'reference' => 'REF-TOY-004', 'qty' => 20, 'buyPrice' => '1500', 'salePriceExVat' => '2500',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '2750', 'profit' => '1000',
                'totalAmount' => '30000', 'disType' => 'flat', 'disAmount' => '0', 'disParcent' => '0',
                'grandTotal' => '30000', 'paidAmount' => '30000', 'dueAmount' => '0',
                'specialNote' => 'Nerf blasters stock for outdoor play', 'businessId' => $bid,
            ],
            [
                'productName' => $pId('SYL-STARTER-001'), 'supplier' => $supId('supply@playworld.com'),
                'purchase_date' => $base->copy()->addDays(20)->toDateString(), 'invoice' => 'PUR-TOY-005',
                'reference' => 'REF-TOY-005', 'qty' => 18, 'buyPrice' => '3000', 'salePriceExVat' => '5500',
                'vatStatus' => 'exclusive', 'salePriceInVat' => '6050', 'profit' => '2500',
                'totalAmount' => '54000', 'disType' => 'flat', 'disAmount' => '1500', 'disParcent' => '0',
                'grandTotal' => '52500', 'paidAmount' => '52500', 'dueAmount' => '0',
                'specialNote' => 'Sylvanian Families playsets', 'businessId' => $bid,
            ],
        ];

        $now = now();
        foreach ($purchases as $row) {
            $existing = DB::table('purchase_products')->where('businessId', $bid)->where('invoice', $row['invoice'])->first();
            if ($existing) {
                DB::table('purchase_products')->where('id', $existing->id)->update(array_merge($row, ['updated_at' => $now]));
            } else {
                DB::table('purchase_products')->insert(array_merge($row, ['created_at' => $now, 'updated_at' => $now]));
            }
        }
    }
}




