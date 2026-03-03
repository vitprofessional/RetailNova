<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $this->call([
            // Core setup
            AdminUserSeeder::class,

            // Lookup / catalogue tables (brands, categories, units)
            CatalogSeeder::class,

            // Chart of accounts
            AccountSeeder::class,

            // Expense categories
            ExpenseCategorySeeder::class,

            // People
            SupplierSeeder::class,
            CustomerSeeder::class,

            // Products + opening stock
            ProductSeeder::class,

            // Transactions
            PurchaseSeeder::class,
            SaleSeeder::class,
            ExpenseEntrySeeder::class,
        ]);
    }
}
