<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // ASSETS
            [
                'account_code' => '1000',
                'account_name' => 'Cash',
                'account_type' => 'asset',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Cash on hand',
                'is_active' => true,
            ],
            [
                'account_code' => '1100',
                'account_name' => 'Bank Account',
                'account_type' => 'asset',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Bank account balance',
                'is_active' => true,
            ],
            [
                'account_code' => '1200',
                'account_name' => 'Accounts Receivable',
                'account_type' => 'asset',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Money owed by customers',
                'is_active' => true,
            ],
            [
                'account_code' => '1300',
                'account_name' => 'Inventory',
                'account_type' => 'asset',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Product inventory',
                'is_active' => true,
            ],
            [
                'account_code' => '1400',
                'account_name' => 'Prepaid Expenses',
                'account_type' => 'asset',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Expenses paid in advance',
                'is_active' => true,
            ],
            [
                'account_code' => '1500',
                'account_name' => 'Fixed Assets',
                'account_type' => 'asset',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Long-term assets like equipment, furniture',
                'is_active' => true,
            ],

            // LIABILITIES
            [
                'account_code' => '2000',
                'account_name' => 'Accounts Payable',
                'account_type' => 'liability',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Money owed to suppliers',
                'is_active' => true,
            ],
            [
                'account_code' => '2100',
                'account_name' => 'Bank Loans',
                'account_type' => 'liability',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Loans from banks',
                'is_active' => true,
            ],
            [
                'account_code' => '2200',
                'account_name' => 'Credit Cards',
                'account_type' => 'liability',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Credit card liabilities',
                'is_active' => true,
            ],
            [
                'account_code' => '2300',
                'account_name' => 'Sales Tax Payable',
                'account_type' => 'liability',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Tax collected from sales',
                'is_active' => true,
            ],

            // EQUITY
            [
                'account_code' => '3000',
                'account_name' => 'Owner\'s Equity',
                'account_type' => 'equity',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Owner\'s capital',
                'is_active' => true,
            ],
            [
                'account_code' => '3100',
                'account_name' => 'Retained Earnings',
                'account_type' => 'equity',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Accumulated profits',
                'is_active' => true,
            ],
            [
                'account_code' => '3200',
                'account_name' => 'Owner\'s Drawings',
                'account_type' => 'equity',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Money withdrawn by owner',
                'is_active' => true,
            ],

            // REVENUE
            [
                'account_code' => '4000',
                'account_name' => 'Sales Revenue',
                'account_type' => 'revenue',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Revenue from sales',
                'is_active' => true,
            ],
            [
                'account_code' => '4100',
                'account_name' => 'Service Revenue',
                'account_type' => 'revenue',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Revenue from services',
                'is_active' => true,
            ],
            [
                'account_code' => '4200',
                'account_name' => 'Interest Income',
                'account_type' => 'revenue',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Interest earned',
                'is_active' => true,
            ],
            [
                'account_code' => '4300',
                'account_name' => 'Other Income',
                'account_type' => 'revenue',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Miscellaneous income',
                'is_active' => true,
            ],

            // EXPENSES
            [
                'account_code' => '5000',
                'account_name' => 'Cost of Goods Sold',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Direct cost of products sold',
                'is_active' => true,
            ],
            [
                'account_code' => '5100',
                'account_name' => 'Rent Expense',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Shop/office rent',
                'is_active' => true,
            ],
            [
                'account_code' => '5200',
                'account_name' => 'Salaries & Wages',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Employee salaries and wages',
                'is_active' => true,
            ],
            [
                'account_code' => '5300',
                'account_name' => 'Utilities Expense',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Electricity, water, internet, etc.',
                'is_active' => true,
            ],
            [
                'account_code' => '5400',
                'account_name' => 'Marketing & Advertising',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Marketing and advertising costs',
                'is_active' => true,
            ],
            [
                'account_code' => '5500',
                'account_name' => 'Office Supplies',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Office supplies and stationery',
                'is_active' => true,
            ],
            [
                'account_code' => '5600',
                'account_name' => 'Telephone & Internet',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Communication expenses',
                'is_active' => true,
            ],
            [
                'account_code' => '5700',
                'account_name' => 'Maintenance & Repairs',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Maintenance and repair costs',
                'is_active' => true,
            ],
            [
                'account_code' => '5800',
                'account_name' => 'Insurance',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Insurance premiums',
                'is_active' => true,
            ],
            [
                'account_code' => '5900',
                'account_name' => 'Bank Charges',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Bank fees and charges',
                'is_active' => true,
            ],
            [
                'account_code' => '6000',
                'account_name' => 'Depreciation',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Asset depreciation',
                'is_active' => true,
            ],
            [
                'account_code' => '6100',
                'account_name' => 'Miscellaneous Expense',
                'account_type' => 'expense',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Other miscellaneous expenses',
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $account) {
            Account::create($account);
        }
    }
}
