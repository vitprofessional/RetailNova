# Quick Setup Guide - Account & Expense Management

## 🚀 Quick Start (5 minutes)

### Step 1: Run Migrations
```bash
php artisan migrate
```

### Step 2: Seed Default Data
```bash
php artisan db:seed --class=AccountSeeder
php artisan db:seed --class=ExpenseCategorySeeder
```

### Step 3: Create Storage Link (if not already done)
```bash
php artisan storage:link
```

### Step 4: Access the System

**Account Management:**
- Chart of Accounts: `http://your-domain/accounts/chart`
- Create Account: `http://your-domain/accounts/create`
- Transactions: `http://your-domain/accounts/transactions`
- Financial Reports: `http://your-domain/accounts/reports`

**Expense Management:**
- Expense Categories: `http://your-domain/expenses/categories`
- Add Expense: `http://your-domain/expenses/create`
- Expense List: `http://your-domain/expenses/list`
- Expense Reports: `http://your-domain/expenses/reports`

## 📋 What's Included

### ✅ Models (4)
1. `Account.php` - Chart of accounts
2. `AccountTransaction.php` - Double-entry transactions
3. `ExpenseCategory.php` - Expense categories
4. `ExpenseEntry.php` - Daily expenses

### ✅ Controllers (2)
1. `AccountManagementController.php` - Full accounting system
2. `ExpenseManagementController.php` - Expense management

### ✅ Migrations (4)
1. `create_accounts_table.php`
2. `create_account_transactions_table.php`
3. `create_expense_categories_table.php`
4. `create_expense_entries_table.php`

### ✅ Seeders (2)
1. `AccountSeeder.php` - 27 default accounts
2. `ExpenseCategorySeeder.php` - 15 expense categories

### ✅ Routes
- All routes added to `routes/web.php`
- Protected by authentication middleware
- RESTful design

## 🎯 Key Features

### Account Management
- ✅ Chart of Accounts (Asset, Liability, Equity, Revenue, Expense)
- ✅ Double-Entry Accounting
- ✅ Transaction Recording
- ✅ Account Ledgers
- ✅ Balance Sheet
- ✅ Income Statement
- ✅ Trial Balance

### Expense Management
- ✅ Expense Categories
- ✅ Daily Expense Recording
- ✅ Receipt Upload
- ✅ Multiple Payment Methods
- ✅ Accounting Integration
- ✅ Expense Reports
- ✅ Statistics Dashboard

## 📊 Default Chart of Accounts

### Assets (1000-1599)
- 1000: Cash
- 1100: Bank Account
- 1200: Accounts Receivable
- 1300: Inventory
- 1400: Prepaid Expenses
- 1500: Fixed Assets

### Liabilities (2000-2399)
- 2000: Accounts Payable
- 2100: Bank Loans
- 2200: Credit Cards
- 2300: Sales Tax Payable

### Equity (3000-3299)
- 3000: Owner's Equity
- 3100: Retained Earnings
- 3200: Owner's Drawings

### Revenue (4000-4399)
- 4000: Sales Revenue
- 4100: Service Revenue
- 4200: Interest Income
- 4300: Other Income

### Expenses (5000-6199)
- 5000: Cost of Goods Sold
- 5100: Rent Expense
- 5200: Salaries & Wages
- 5300: Utilities Expense
- 5400: Marketing & Advertising
- 5500: Office Supplies
- 5600: Telephone & Internet
- 5700: Maintenance & Repairs
- 5800: Insurance
- 5900: Bank Charges
- 6000: Depreciation
- 6100: Miscellaneous Expense

## 📁 Default Expense Categories

1. Rent
2. Utilities
3. Salaries
4. Marketing
5. Transportation
6. Office Supplies
7. Telephone & Internet
8. Maintenance
9. Insurance
10. Bank Charges
11. Legal & Professional
12. Inventory Purchases
13. Employee Benefits
14. Taxes
15. Miscellaneous

## 🔐 Security

- ✅ Authentication required
- ✅ SuperAdmin middleware
- ✅ Audit trail (all changes tracked)
- ✅ Foreign key constraints
- ✅ File upload validation

## 💡 Next Steps

1. **Customize Accounts**: Add/edit accounts based on your business needs
2. **Setup Categories**: Modify expense categories if needed
3. **Configure Locations**: Assign accounts to business locations
4. **Train Users**: Show staff how to record expenses
5. **Set Budgets**: (Future feature)

## 🆘 Need Help?

Check the full documentation: `docs/account_expense_management.md`

## ✨ You're Ready!

Your account and expense management system is now ready to use. Start by:
1. Reviewing the chart of accounts
2. Recording your first expense
3. Creating an accounting transaction
4. Generating your first report

Happy Accounting! 📊💰
