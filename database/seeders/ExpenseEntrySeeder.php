<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExpenseEntrySeeder extends Seeder
{
    public function run(): void
    {
        // Fetch category IDs by name
        $cat = fn(string $n) => DB::table('expense_categories')->where('name', $n)->value('id');

        // Use admin user ID 1 (created_by is now nullable with no FK)
        $adminId = DB::table('admin_users')->value('id');

        $base = Carbon::now()->subDays(50);

        $entries = [
            [
                'expense_date'    => $base->copy()->addDays(1)->toDateString(),
                'category_id'     => $cat('Rent'),
                'amount'          => 35000.00,
                'payment_method'  => 'bank',
                'reference_no'    => 'EXP-2026-001',
                'description'     => 'Monthly shop rent – January 2026',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
            [
                'expense_date'    => $base->copy()->addDays(3)->toDateString(),
                'category_id'     => $cat('Utilities'),
                'amount'          => 4500.00,
                'payment_method'  => 'cash',
                'reference_no'    => 'EXP-2026-002',
                'description'     => 'Electricity bill',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
            [
                'expense_date'    => $base->copy()->addDays(5)->toDateString(),
                'category_id'     => $cat('Salaries'),
                'amount'          => 60000.00,
                'payment_method'  => 'bank',
                'reference_no'    => 'EXP-2026-003',
                'description'     => 'Staff salaries – January 2026',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
            [
                'expense_date'    => $base->copy()->addDays(8)->toDateString(),
                'category_id'     => $cat('Telephone & Internet'),
                'amount'          => 3200.00,
                'payment_method'  => 'cash',
                'reference_no'    => 'EXP-2026-004',
                'description'     => 'Internet & phone bill',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
            [
                'expense_date'    => $base->copy()->addDays(12)->toDateString(),
                'category_id'     => $cat('Marketing'),
                'amount'          => 8000.00,
                'payment_method'  => 'card',
                'reference_no'    => 'EXP-2026-005',
                'description'     => 'Facebook/Instagram ad campaign',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
            [
                'expense_date'    => $base->copy()->addDays(15)->toDateString(),
                'category_id'     => $cat('Office Supplies'),
                'amount'          => 2100.00,
                'payment_method'  => 'cash',
                'reference_no'    => 'EXP-2026-006',
                'description'     => 'Printer paper, pens, folders',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
            [
                'expense_date'    => $base->copy()->addDays(20)->toDateString(),
                'category_id'     => $cat('Transportation'),
                'amount'          => 5500.00,
                'payment_method'  => 'cash',
                'reference_no'    => 'EXP-2026-007',
                'description'     => 'Delivery fuel expenses',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
            [
                'expense_date'    => $base->copy()->addDays(25)->toDateString(),
                'category_id'     => $cat('Maintenance'),
                'amount'          => 12000.00,
                'payment_method'  => 'bank',
                'reference_no'    => 'EXP-2026-008',
                'description'     => 'AC servicing & repair',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
            [
                'expense_date'    => $base->copy()->addDays(28)->toDateString(),
                'category_id'     => $cat('Bank Charges'),
                'amount'          => 850.00,
                'payment_method'  => 'bank',
                'reference_no'    => 'EXP-2026-009',
                'description'     => 'Bank transaction fees',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
            [
                'expense_date'    => $base->copy()->addDays(32)->toDateString(),
                'category_id'     => $cat('Rent'),
                'amount'          => 35000.00,
                'payment_method'  => 'bank',
                'reference_no'    => 'EXP-2026-010',
                'description'     => 'Monthly shop rent – February 2026',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
            [
                'expense_date'    => $base->copy()->addDays(35)->toDateString(),
                'category_id'     => $cat('Salaries'),
                'amount'          => 60000.00,
                'payment_method'  => 'bank',
                'reference_no'    => 'EXP-2026-011',
                'description'     => 'Staff salaries – February 2026',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
            [
                'expense_date'    => $base->copy()->addDays(40)->toDateString(),
                'category_id'     => $cat('Utilities'),
                'amount'          => 5100.00,
                'payment_method'  => 'cash',
                'reference_no'    => 'EXP-2026-012',
                'description'     => 'Electricity bill – February 2026',
                'created_by'      => $adminId,
                'created_by_type' => 'admin',
            ],
        ];

        foreach ($entries as $entry) {
            $payload = array_merge($entry, [
                'receipt_file'             => null,
                'business_location_id'     => null,
                'account_transaction_id'   => null,
            ]);

            $referenceNo = $entry['reference_no'];
            $existingEntry = DB::table('expense_entries')
                ->where('reference_no', $referenceNo)
                ->select('id')
                ->first();

            if ($existingEntry) {
                DB::table('expense_entries')
                    ->where('id', $existingEntry->id)
                    ->update(array_merge($payload, [
                        'updated_at' => now(),
                    ]));
            } else {
                DB::table('expense_entries')->insert(array_merge($payload, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
}
