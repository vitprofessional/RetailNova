<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Brands
        $brands = [
            'Samsung', 'Apple', 'Sony', 'LG', 'HP',
            'Dell', 'Lenovo', 'Canon', 'Panasonic', 'Generic',
        ];
        foreach ($brands as $name) {
            DB::table('brands')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Categories
        $categories = [
            'Smartphones',
            'Laptops & Computers',
            'Televisions',
            'Audio & Speakers',
            'Cameras',
            'Accessories',
            'Printers & Scanners',
            'Tablets',
            'Home Appliances',
            'Networking',
        ];
        foreach ($categories as $name) {
            DB::table('categories')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Product Units
        $units = ['Piece', 'Box', 'Pair', 'Set', 'Dozen', 'Kg', 'Litre'];
        foreach ($units as $name) {
            DB::table('product_units')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
