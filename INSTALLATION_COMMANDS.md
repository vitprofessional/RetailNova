# Installation Commands

## Complete Setup (Run these commands in order)

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Default Accounts
```bash
php artisan db:seed --class=AccountSeeder
```

### 3. Seed Default Expense Categories
```bash
php artisan db:seed --class=ExpenseCategorySeeder
```

### 4. Create Storage Link (if not done already)
```bash
php artisan storage:link
```

### 5. Clear Cache (optional but recommended)
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

## Quick One-Liner (All in One)
```bash
php artisan migrate && php artisan db:seed --class=AccountSeeder && php artisan db:seed --class=ExpenseCategorySeeder && php artisan storage:link && php artisan config:clear
```

---

## Rollback (If needed)

### Rollback Last Migration Batch
```bash
php artisan migrate:rollback
```

### Rollback Specific Migrations
```bash
php artisan migrate:rollback --step=4
```

### Fresh Migration (WARNING: Deletes all data)
```bash
php artisan migrate:fresh
php artisan db:seed --class=AccountSeeder
php artisan db:seed --class=ExpenseCategorySeeder
```

---

## Verification Commands

### Check Migration Status
```bash
php artisan migrate:status
```

### Check Routes
```bash
php artisan route:list | grep -E "account|expense"
```

### Check Database Tables
```bash
php artisan tinker
>>> \DB::table('accounts')->count();
>>> \DB::table('expense_categories')->count();
>>> exit
```

---

## Troubleshooting

### Permission Issues
```bash
# Linux/Mac
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache
sudo chown -R www-data:www-data storage
sudo chown -R www-data:www-data bootstrap/cache

# Windows (Run as Administrator)
icacls "storage" /grant "Users:(OI)(CI)F" /T
icacls "bootstrap\cache" /grant "Users:(OI)(CI)F" /T
```

### Foreign Key Errors
Make sure these tables exist before running migrations:
- `users`
- `business_locations`

### Storage Link Already Exists
```bash
# Remove old link and recreate
rm public/storage
php artisan storage:link
```

---

## Post-Installation

### Access the System
1. **Chart of Accounts**: http://your-domain/accounts/chart
2. **Create Account**: http://your-domain/accounts/create
3. **Transactions**: http://your-domain/accounts/transactions
4. **Financial Reports**: http://your-domain/accounts/reports
5. **Expense Categories**: http://your-domain/expenses/categories
6. **Add Expense**: http://your-domain/expenses/create
7. **Expense List**: http://your-domain/expenses/list
8. **Expense Reports**: http://your-domain/expenses/reports

### Test the System
1. Create a test account
2. Record a test transaction
3. Add a test expense
4. Generate a report

### Configure
1. Review and customize chart of accounts
2. Adjust expense categories
3. Set up business locations
4. Train users

---

## Success!
If all commands run without errors, your system is ready! 🎉

Check the documentation for more details:
- Full Documentation: `docs/account_expense_management.md`
- Setup Guide: `docs/SETUP_GUIDE.md`
- File Summary: `docs/FILE_SUMMARY.md`
