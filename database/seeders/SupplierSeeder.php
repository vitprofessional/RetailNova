<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name'           => 'TechSource Distributors',
                'mail'           => 'orders@techsource.com',
                'mobile'         => '0300-1111111',
                'country'        => 'Bangladesh',
                'state'          => 'Dhaka',
                'city'           => 'Dhaka',
                'area'           => 'Gulshan',
                'openingBalance' => 0,
            ],
            [
                'name'           => 'Global Gadgets Ltd',
                'mail'           => 'supply@globalgadgets.com',
                'mobile'         => '0300-2222222',
                'country'        => 'Bangladesh',
                'state'          => 'Chattogram',
                'city'           => 'Chattogram',
                'area'           => 'Agrabad',
                'openingBalance' => 5000,
            ],
            [
                'name'           => 'Prime Electronics',
                'mail'           => 'info@primeelectronics.pk',
                'mobile'         => '0300-3333333',
                'country'        => 'Bangladesh',
                'state'          => 'Sylhet',
                'city'           => 'Sylhet',
                'area'           => 'Zindabazar',
                'openingBalance' => 0,
            ],
            [
                'name'           => 'Swift Imports',
                'mail'           => 'sales@swiftimports.pk',
                'mobile'         => '0300-4444444',
                'country'        => 'Bangladesh',
                'state'          => 'Khulna',
                'city'           => 'Khulna',
                'area'           => 'Sonadanga',
                'openingBalance' => 12000,
            ],
            [
                'name'           => 'Horizon Trading Co',
                'mail'           => 'contact@horizontrading.pk',
                'mobile'         => '0300-5555555',
                'country'        => 'Bangladesh',
                'state'          => 'Rajshahi',
                'city'           => 'Rajshahi',
                'area'           => 'Shaheb Bazar',
                'openingBalance' => 0,
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::updateOrCreate(['mail' => $data['mail']], $data);
        }
    }
}
