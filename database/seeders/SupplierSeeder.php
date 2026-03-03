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
                'country'        => 'Pakistan',
                'state'          => 'Punjab',
                'city'           => 'Lahore',
                'area'           => 'Gulberg',
                'openingBalance' => 0,
            ],
            [
                'name'           => 'Global Gadgets Ltd',
                'mail'           => 'supply@globalgadgets.com',
                'mobile'         => '0300-2222222',
                'country'        => 'Pakistan',
                'state'          => 'Sindh',
                'city'           => 'Karachi',
                'area'           => 'SITE Area',
                'openingBalance' => 5000,
            ],
            [
                'name'           => 'Prime Electronics',
                'mail'           => 'info@primeelectronics.pk',
                'mobile'         => '0300-3333333',
                'country'        => 'Pakistan',
                'state'          => 'KPK',
                'city'           => 'Peshawar',
                'area'           => 'Hayatabad',
                'openingBalance' => 0,
            ],
            [
                'name'           => 'Swift Imports',
                'mail'           => 'sales@swiftimports.pk',
                'mobile'         => '0300-4444444',
                'country'        => 'Pakistan',
                'state'          => 'Punjab',
                'city'           => 'Faisalabad',
                'area'           => 'D-Ground',
                'openingBalance' => 12000,
            ],
            [
                'name'           => 'Horizon Trading Co',
                'mail'           => 'contact@horizontrading.pk',
                'mobile'         => '0300-5555555',
                'country'        => 'Pakistan',
                'state'          => 'Punjab',
                'city'           => 'Islamabad',
                'area'           => 'Blue Area',
                'openingBalance' => 0,
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::updateOrCreate(['mail' => $data['mail']], $data);
        }
    }
}
