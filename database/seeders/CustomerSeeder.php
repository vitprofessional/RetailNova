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
                'country'        => 'Bangladesh',
                'state'          => 'Dhaka',
                'city'           => 'Dhaka',
                'area'           => 'Dhanmondi',
                'openingBalance' => 0,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Sara Khan',
                'mail'           => 'sara.khan@email.com',
                'mobile'         => '0321-1002002',
                'country'        => 'Bangladesh',
                'state'          => 'Dhaka',
                'city'           => 'Dhaka',
                'area'           => 'Uttara',
                'openingBalance' => 2500,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Usman Malik',
                'mail'           => 'usman.malik@email.com',
                'mobile'         => '0321-1003003',
                'country'        => 'Bangladesh',
                'state'          => 'Chattogram',
                'city'           => 'Chattogram',
                'area'           => 'Panchlaish',
                'openingBalance' => 0,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Fatima Riaz',
                'mail'           => 'fatima.riaz@email.com',
                'mobile'         => '0321-1004004',
                'country'        => 'Bangladesh',
                'state'          => 'Chattogram',
                'city'           => 'Chattogram',
                'area'           => 'Halishahar',
                'openingBalance' => 1000,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Bilal Hassan',
                'mail'           => 'bilal.hassan@email.com',
                'mobile'         => '0321-1005005',
                'country'        => 'Bangladesh',
                'state'          => 'Rajshahi',
                'city'           => 'Rajshahi',
                'area'           => 'Boalia',
                'openingBalance' => 0,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Nadia Hussain',
                'mail'           => 'nadia.hussain@email.com',
                'mobile'         => '0321-1006006',
                'country'        => 'Bangladesh',
                'state'          => 'Khulna',
                'city'           => 'Khulna',
                'area'           => 'Khalishpur',
                'openingBalance' => 3000,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Tariq Mehmood',
                'mail'           => 'tariq.mehmood@email.com',
                'mobile'         => '0321-1007007',
                'country'        => 'Bangladesh',
                'state'          => 'Sylhet',
                'city'           => 'Sylhet',
                'area'           => 'Ambarkhana',
                'openingBalance' => 0,
                'businessId'     => 1,
            ],
            [
                'name'           => 'Zainab Qureshi',
                'mail'           => 'zainab.qureshi@email.com',
                'mobile'         => '0321-1008008',
                'country'        => 'Bangladesh',
                'state'          => 'Barishal',
                'city'           => 'Barishal',
                'area'           => 'Nathullabad',
                'openingBalance' => 500,
                'businessId'     => 1,
            ],
        ];

        foreach ($customers as $data) {
            Customer::updateOrCreate(['mail' => $data['mail']], $data);
        }
    }
}
