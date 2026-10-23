<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    use Concerns\BusinessTypeSeedable;

    public function run(): void
    {
        // Brands
        $brands = [
            'Samsung', 'Apple', 'Sony', 'LG', 'HP',
            'Dell', 'Lenovo', 'Canon', 'Panasonic', 'Generic',
        ];
        foreach ($brands as $name) {
            $this->masterId('brands', $name);
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
            $this->masterId('categories', $name);
        }

        // Product Units
        $units = ['Piece', 'Box', 'Pair', 'Set', 'Dozen', 'Kg', 'Litre'];
        foreach ($units as $name) {
            $this->masterId('product_units', $name);
        }
    }
}
