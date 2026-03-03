<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the default Walking Customer exists
        Customer::ensureWalkingCustomer();

        $customers = [
            [
                'name'           => 'Ahmed Ali',
                'mail'           => 'ahmed.ali@email.com',
                'mobile'         => '0321-1001001',
                'country'        => 'Pakistan',
                'state'          => 'Punjab',
                'city'           => 'Lahore',
                'area'           => 'Model Town',
                'openingBalance' => 0,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Sara Khan',
                'mail'           => 'sara.khan@email.com',
                'mobile'         => '0321-1002002',
                'country'        => 'Pakistan',
                'state'          => 'Punjab',
                'city'           => 'Lahore',
                'area'           => 'DHA Phase 5',
                'openingBalance' => 2500,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Usman Malik',
                'mail'           => 'usman.malik@email.com',
                'mobile'         => '0321-1003003',
                'country'        => 'Pakistan',
                'state'          => 'Sindh',
                'city'           => 'Karachi',
                'area'           => 'Clifton',
                'openingBalance' => 0,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Fatima Riaz',
                'mail'           => 'fatima.riaz@email.com',
                'mobile'         => '0321-1004004',
                'country'        => 'Pakistan',
                'state'          => 'Sindh',
                'city'           => 'Karachi',
                'area'           => 'North Nazimabad',
                'openingBalance' => 1000,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Bilal Hassan',
                'mail'           => 'bilal.hassan@email.com',
                'mobile'         => '0321-1005005',
                'country'        => 'Pakistan',
                'state'          => 'Punjab',
                'city'           => 'Rawalpindi',
                'area'           => 'Satellite Town',
                'openingBalance' => 0,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Nadia Hussain',
                'mail'           => 'nadia.hussain@email.com',
                'mobile'         => '0321-1006006',
                'country'        => 'Pakistan',
                'state'          => 'Punjab',
                'city'           => 'Multan',
                'area'           => 'Cantt',
                'openingBalance' => 3000,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Tariq Mehmood',
                'mail'           => 'tariq.mehmood@email.com',
                'mobile'         => '0321-1007007',
                'country'        => 'Pakistan',
                'state'          => 'KPK',
                'city'           => 'Peshawar',
                'area'           => 'University Road',
                'openingBalance' => 0,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Zainab Qureshi',
                'mail'           => 'zainab.qureshi@email.com',
                'mobile'         => '0321-1008008',
                'country'        => 'Pakistan',
                'state'          => 'Punjab',
                'city'           => 'Islamabad',
                'area'           => 'F-7',
                'openingBalance' => 500,
                'businessId'     => 1,
            ],
        ];

        foreach ($customers as $data) {
            Customer::updateOrCreate(['mail' => $data['mail']], $data);
        }
    }
}
