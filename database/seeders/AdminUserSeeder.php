<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Credentials provided by workspace owner
        $email = 'virtualitprofessional@gmail.com';
        $password = '11223344';

        $data = [
            'fullName' => 'Admin User',
            'sureName' => 'Admin',
            'storeName' => 'RetailNova',
            'mail' => $email,
            'contactNumber' => '0000000000',
            'password' => Hash::make($password),
            'businessId' => 1,
        ];

        // Only include the role field if the column exists (some installations may not have it)
        if (\Illuminate\Support\Facades\Schema::hasColumn('admin_users', 'role')) {
            $data['role'] = 'superadmin';
        }

        AdminUser::updateOrCreate(['mail' => $email], $data);
    }
}
