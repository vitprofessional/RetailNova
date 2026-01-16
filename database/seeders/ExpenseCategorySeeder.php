<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Rent',
                'description' => 'Shop/Office rent payments',
                'is_active' => true,
            ],
            [
                'name' => 'Utilities',
                'description' => 'Electricity, water, gas bills',
                'is_active' => true,
            ],
            [
                'name' => 'Salaries',
                'description' => 'Employee salaries and wages',
                'is_active' => true,
            ],
            [
                'name' => 'Marketing',
                'description' => 'Marketing and advertising expenses',
                'is_active' => true,
            ],
            [
                'name' => 'Transportation',
                'description' => 'Vehicle fuel, maintenance, transportation',
                'is_active' => true,
            ],
            [
                'name' => 'Office Supplies',
                'description' => 'Stationery, printing, office materials',
                'is_active' => true,
            ],
            [
                'name' => 'Telephone & Internet',
                'description' => 'Communication expenses',
                'is_active' => true,
            ],
            [
                'name' => 'Maintenance',
                'description' => 'Equipment and facility maintenance',
                'is_active' => true,
            ],
            [
                'name' => 'Insurance',
                'description' => 'Insurance premiums',
                'is_active' => true,
            ],
            [
                'name' => 'Bank Charges',
                'description' => 'Bank fees and transaction charges',
                'is_active' => true,
            ],
            [
                'name' => 'Legal & Professional',
                'description' => 'Legal, accounting, consultancy fees',
                'is_active' => true,
            ],
            [
                'name' => 'Inventory Purchases',
                'description' => 'Stock purchases for resale',
                'is_active' => true,
            ],
            [
                'name' => 'Employee Benefits',
                'description' => 'Health insurance, bonuses, benefits',
                'is_active' => true,
            ],
            [
                'name' => 'Taxes',
                'description' => 'Business taxes and duties',
                'is_active' => true,
            ],
            [
                'name' => 'Miscellaneous',
                'description' => 'Other expenses',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::create($category);
        }
    }
}
