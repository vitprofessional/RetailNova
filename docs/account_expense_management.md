# Account Management & Expense Management System

## Overview

This package adds comprehensive **Account Management** and **Expense Management** systems to your RetailNova POS application.

## Features

### Account Management System
- **Chart of Accounts**: Manage accounts across 5 types (Assets, Liabilities, Equity, Revenue, Expense)
- **Double-Entry Accounting**: All transactions follow double-entry bookkeeping principles
- **Account Transactions**: Record journal entries, payments, receipts, and more
- **Account Ledger**: View detailed transaction history for each account
- **Financial Reports**: 
  - Balance Sheet
  - Income Statement (Profit & Loss)
  - Trial Balance
- **Parent-Child Accounts**: Create sub-accounts under main accounts
- **Multi-Location Support**: Track accounts per business location

### Expense Management System
- **Expense Categories**: Organize expenses by categories
- **Daily Expense Recording**: Record expenses with:
  - Date, amount, payment method
  - Reference numbers
  - Receipt file uploads
  - Detailed descriptions
- **Multiple Payment Methods**: Cash, Bank, Card, Cheque, Mobile
- **Accounting Integration**: Expenses automatically create accounting transactions
- **Expense Reports**: Analyze expenses by:
  - Category
  - Payment method
  - Date range
  - Business location
- **Dashboard Statistics**: Quick expense overview

## Installation & Setup

### Step 1: Run Migrations

Run the migrations to create the required database tables:

```bash
php artisan migrate
```

This will create the following tables:
- `accounts` - Chart of accounts
- `account_transactions` - Accounting transactions
- `expense_categories` - Expense categories
- `expense_entries` - Daily expense records

### Step 2: Seed Default Data (Optional)

Seed default chart of accounts and expense categories:

```bash
php artisan db:seed --class=AccountSeeder
php artisan db:seed --class=ExpenseCategorySeeder
```

This will create:
- **27 default accounts** across all account types
- **15 default expense categories**

### Step 3: Create Storage Link

If you haven't already, create the storage link for expense receipt uploads:

```bash
php artisan storage:link
```

## Database Schema

### Accounts Table
- `account_code` - Unique code (e.g., 1000, 2000)
- `account_name` - Account name
- `account_type` - asset, liability, equity, revenue, expense
- `parent_account_id` - For sub-accounts
- `opening_balance` - Starting balance
- `current_balance` - Current balance
- `business_location_id` - Multi-location support

### Account Transactions Table
- `transaction_date` - Date of transaction
- `transaction_type` - journal, payment, receipt, expense, sale, purchase, transfer
- `reference_no` - Unique reference
- `debit_account_id` - Debit account
- `credit_account_id` - Credit account
- `amount` - Transaction amount
- `created_by` - User who created the transaction

### Expense Categories Table
- `name` - Category name
- `description` - Category description
- `is_active` - Active status

### Expense Entries Table
- `expense_date` - Date of expense
- `category_id` - Expense category
- `amount` - Expense amount
- `payment_method` - cash, bank, card, cheque, mobile
- `reference_no` - Reference/receipt number
- `receipt_file` - Uploaded receipt file
- `account_transaction_id` - Link to accounting transaction
- `business_location_id` - Business location

## Routes

### Account Management Routes

```php
// Chart of Accounts
GET  /accounts/chart                    - View chart of accounts
GET  /accounts/create                   - Create new account form
POST /accounts/store                    - Save new account
GET  /accounts/edit/{id}                - Edit account form
POST /accounts/update/{id}              - Update account
GET  /accounts/delete/{id}              - Delete account

// Transactions
GET  /accounts/transactions             - List all transactions
GET  /accounts/transactions/create      - Create transaction form
POST /accounts/transactions/store       - Save transaction

// Reports
GET  /accounts/ledger/{id}              - Account ledger
GET  /accounts/reports                  - Financial reports
```

### Expense Management Routes

```php
// Categories
GET  /expenses/categories               - List expense categories
GET  /expenses/categories/create        - Create category form
POST /expenses/categories/store         - Save category
GET  /expenses/categories/edit/{id}     - Edit category form
POST /expenses/categories/update/{id}   - Update category
GET  /expenses/categories/delete/{id}   - Delete category

// Expense Entries
GET  /expenses/list                     - List all expenses
GET  /expenses/create                   - Create expense form
POST /expenses/store                    - Save expense
GET  /expenses/edit/{id}                - Edit expense form
POST /expenses/update/{id}              - Update expense
GET  /expenses/delete/{id}              - Delete expense

// Reports
GET  /expenses/reports                  - Expense reports
GET  /expenses/statistics               - Expense statistics (AJAX)
```

## Models

### Account Model
- **Relationships**: parentAccount, childAccounts, transactions, debitTransactions, creditTransactions, businessLocation
- **Scopes**: active(), ofType()
- **Constants**: Account types (ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE)

### AccountTransaction Model
- **Relationships**: debitAccount, creditAccount, creator, businessLocation
- **Scopes**: dateRange(), ofType()
- **Constants**: Transaction types (JOURNAL, PAYMENT, RECEIPT, EXPENSE, SALE, PURCHASE, TRANSFER)

### ExpenseCategory Model
- **Relationships**: expenses
- **Scopes**: active()

### ExpenseEntry Model
- **Relationships**: category, creator, businessLocation, accountTransaction
- **Scopes**: dateRange(), ofCategory()
- **Constants**: Payment methods (CASH, BANK, CARD, CHEQUE, MOBILE)

## Usage Examples

### Creating a New Account

```php
use App\Models\Account;

Account::create([
    'account_code' => '1150',
    'account_name' => 'Petty Cash',
    'account_type' => 'asset',
    'parent_account_id' => 1, // Cash account
    'opening_balance' => 1000,
    'current_balance' => 1000,
    'description' => 'Petty cash for small expenses',
    'is_active' => true,
]);
```

### Recording an Expense

```php
use App\Models\ExpenseEntry;

ExpenseEntry::create([
    'expense_date' => now(),
    'category_id' => 1, // Rent
    'amount' => 5000,
    'payment_method' => 'bank',
    'reference_no' => 'RENT-2024-01',
    'description' => 'January rent payment',
    'created_by' => auth()->id(),
]);
```

### Creating an Accounting Transaction

```php
use App\Models\AccountTransaction;

AccountTransaction::create([
    'transaction_date' => now(),
    'transaction_type' => 'expense',
    'reference_no' => 'TRX-' . time(),
    'debit_account_id' => 18, // Rent Expense
    'credit_account_id' => 2,  // Bank Account
    'amount' => 5000,
    'description' => 'Monthly rent payment',
    'created_by' => auth()->id(),
]);
```

### Generating Financial Reports

```php
use App\Http\Controllers\AccountManagementController;

// Balance Sheet
$controller = new AccountManagementController();
$balanceSheet = $controller->generateBalanceSheet('2024-01-01', '2024-12-31');

// Income Statement
$incomeStatement = $controller->generateIncomeStatement('2024-01-01', '2024-12-31');
```

## Accounting Principles

### Double-Entry Bookkeeping
Every transaction has two sides:
- **Debit**: Entry on the left side
- **Credit**: Entry on the right side

### Account Types & Effects

| Account Type | Debit | Credit |
|-------------|-------|--------|
| Asset | Increase | Decrease |
| Liability | Decrease | Increase |
| Equity | Decrease | Increase |
| Revenue | Decrease | Increase |
| Expense | Increase | Decrease |

### Example Transactions

**Recording a Sale (Cash)**
- Debit: Cash (Asset) - Increases cash
- Credit: Sales Revenue (Revenue) - Increases revenue

**Recording an Expense (Cash)**
- Debit: Expense Account (Expense) - Increases expense
- Credit: Cash (Asset) - Decreases cash

**Recording a Purchase on Credit**
- Debit: Inventory (Asset) - Increases inventory
- Credit: Accounts Payable (Liability) - Increases liability

## Audit Trail

All models implement auditing using the `owen-it/laravel-auditing` package. This automatically tracks:
- Who created/updated/deleted records
- When changes were made
- What was changed (old vs new values)

Access audit logs via: `/audits`

## Security Features

- All routes protected by authentication middleware
- SuperAdmin middleware for sensitive operations
- Foreign key constraints ensure data integrity
- Soft deletes for important records
- File upload validation for receipts

## Best Practices

1. **Backup Before Migration**: Always backup your database before running migrations
2. **Reconcile Regularly**: Reconcile accounts monthly
3. **Receipt Management**: Always upload receipts for expenses
4. **Reference Numbers**: Use consistent reference numbering
5. **Account Structure**: Plan your chart of accounts carefully before adding many transactions
6. **Business Locations**: Assign locations to track per-location finances

## Troubleshooting

### Issue: Foreign Key Constraint Error
**Solution**: Ensure `business_locations` and `users` tables exist before running migrations

### Issue: Storage Link Not Working
**Solution**: Run `php artisan storage:link`

### Issue: File Upload Fails
**Solution**: Check `storage/app/public` permissions (755 or 777)

### Issue: Balance Not Updating
**Solution**: Verify account types match transaction logic (debit/credit rules)

## Future Enhancements

Potential features for future development:
- Bank reconciliation module
- Budget tracking and alerts
- Recurring expenses
- Multi-currency support
- Tax calculations
- Cash flow statements
- Profit center analysis
- Export to accounting software (QuickBooks, Xero)
- Mobile app for expense recording

## Support

For issues or questions:
1. Check the audit logs: `/audits`
2. Review Laravel logs: `storage/logs/laravel.log`
3. Check database integrity
4. Verify middleware and authentication

## License

This module is part of the RetailNova POS system and follows the same license.

---

**Created**: January 15, 2026
**Version**: 1.0.0
**Last Updated**: January 15, 2026
