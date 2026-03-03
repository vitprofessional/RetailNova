<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

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
