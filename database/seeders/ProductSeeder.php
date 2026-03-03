<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductStock;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch lookup IDs dynamically so the seeder is order-independent
        $brand  = fn(string $n) => (string) (DB::table('brands')->where('name', $n)->value('id') ?? 1);
        $cat    = fn(string $n) => (string) (DB::table('categories')->where('name', $n)->value('id') ?? 1);
        $unit   = fn(string $n) => (string) (DB::table('product_units')->where('name', $n)->value('id') ?? 1);

        $products = [
            [
                'name'     => 'Samsung Galaxy A54',
                'brand'    => $brand('Samsung'),
                'category' => $cat('Smartphones'),
                'unitName' => $unit('Piece'),
                'quantity' => '5',   // low-stock alert threshold
                'details'  => '6.4" Super AMOLED, 128GB, 5000mAh',
                'barCode'  => 'SMG-A54-001',
                'businessId' => 1,
                'stock'    => 30,
            ],
            [
                'name'     => 'iPhone 14',
                'brand'    => $brand('Apple'),
                'category' => $cat('Smartphones'),
                'unitName' => $unit('Piece'),
                'quantity' => '3',
                'details'  => '6.1" Super Retina XDR, 128GB',
                'barCode'  => 'APL-IP14-001',
                'businessId' => 1,
                'stock'    => 15,
            ],
            [
                'name'     => 'Dell Inspiron 15',
                'brand'    => $brand('Dell'),
                'category' => $cat('Laptops & Computers'),
                'unitName' => $unit('Piece'),
                'quantity' => '2',
                'details'  => 'Intel Core i5, 8GB RAM, 512GB SSD',
                'barCode'  => 'DEL-INS15-001',
                'businessId' => 1,
                'stock'    => 12,
            ],
            [
                'name'     => 'Lenovo ThinkPad E14',
                'brand'    => $brand('Lenovo'),
                'category' => $cat('Laptops & Computers'),
                'unitName' => $unit('Piece'),
                'quantity' => '2',
                'details'  => 'AMD Ryzen 5, 16GB RAM, 256GB SSD',
                'barCode'  => 'LEN-TP-E14-001',
                'businessId' => 1,
                'stock'    => 8,
            ],
            [
                'name'     => 'LG 43" Smart TV',
                'brand'    => $brand('LG'),
                'category' => $cat('Televisions'),
                'unitName' => $unit('Piece'),
                'quantity' => '2',
                'details'  => '43" 4K UHD, WebOS, HDR10',
                'barCode'  => 'LG-TV43-001',
                'businessId' => 1,
                'stock'    => 10,
            ],
            [
                'name'     => 'Sony WH-1000XM5 Headphones',
                'brand'    => $brand('Sony'),
                'category' => $cat('Audio & Speakers'),
                'unitName' => $unit('Piece'),
                'quantity' => '3',
                'details'  => 'Noise Cancelling Wireless Headphones',
                'barCode'  => 'SNY-WH1000XM5-001',
                'businessId' => 1,
                'stock'    => 20,
            ],
            [
                'name'     => 'Canon EOS 2000D Camera',
                'brand'    => $brand('Canon'),
                'category' => $cat('Cameras'),
                'unitName' => $unit('Piece'),
                'quantity' => '2',
                'details'  => '24.1MP DSLR with 18-55mm Kit Lens',
                'barCode'  => 'CAN-EOS2000D-001',
                'businessId' => 1,
                'stock'    => 7,
            ],
            [
                'name'     => 'HP LaserJet Pro M404n',
                'brand'    => $brand('HP'),
                'category' => $cat('Printers & Scanners'),
                'unitName' => $unit('Piece'),
                'quantity' => '2',
                'details'  => 'Monochrome Laser Printer, 40 ppm',
                'barCode'  => 'HP-LJM404-001',
                'businessId' => 1,
                'stock'    => 6,
            ],
            [
                'name'     => 'Samsung Galaxy Tab A8',
                'brand'    => $brand('Samsung'),
                'category' => $cat('Tablets'),
                'unitName' => $unit('Piece'),
                'quantity' => '3',
                'details'  => '10.5" TFT LCD, 32GB, Wi-Fi',
                'barCode'  => 'SMG-TABA8-001',
                'businessId' => 1,
                'stock'    => 18,
            ],
            [
                'name'     => 'USB-C to HDMI Cable 2m',
                'brand'    => $brand('Generic'),
                'category' => $cat('Accessories'),
                'unitName' => $unit('Piece'),
                'quantity' => '10',
                'details'  => '4K 60Hz USB-C to HDMI Adapter Cable',
                'barCode'  => 'GEN-USBCHDMI-2M',
                'businessId' => 1,
                'stock'    => 60,
            ],
        ];

        foreach ($products as $data) {
            $stock = $data['stock'];
            unset($data['stock']);

            $product = Product::updateOrCreate(['barCode' => $data['barCode']], $data);

            // Create a stock record if the product has no stock yet
            if ($product->stocks()->sum('currentStock') == 0) {
                ProductStock::create([
                    'productId'    => $product->id,
                    'purchaseId'   => null,
                    'currentStock' => $stock,
                    'businessId'   => 1,
                ]);
            }
        }
    }
}
