# RetailNova Account and Expense Management

RetailNova extends the POS stack with double-entry accounting and expense management built on Laravel 12 (PHP 8.2). The module delivers a chart of accounts, transaction workflows, expense capture with receipts, reports, and auditing.

## Features

- Accounting: chart of accounts (five types), parent-child structure, double-entry transactions (journal, payment, receipt, expense, sale, purchase, transfer), ledgers, balance sheet, income statement, trial balance, multi-location support, audit trail.
- Expenses: category management, expense entry with reference numbers and receipt uploads, payment methods (cash, bank, card, cheque, mobile), reports by category/payment/date/location, statistics dashboard, automatic accounting integration.
- UI: Blade views powered by DataTables for filtering, pagination, and exports; SweetAlert confirmations.
- Security: auth plus SuperAdmin middleware, CSRF protection, validation, file upload limits, auditing of all changes.

## Tech Stack

- Laravel 12, PHP ^8.2
- MySQL (or any Laravel-supported database)
- Vite + Tailwind CSS 4 + jQuery/DataTables
- DomPDF for print/export, owen-it/laravel-auditing for change tracking, realrashid/sweet-alert for dialogs
- Playwright end-to-end test scaffold (see playwright/README.md)

## Prerequisites

- PHP 8.2+, Composer
- Node 20+ and npm
- Database credentials configured in .env (APP_URL, DB_*, FILESYSTEM_DRIVER if customized)

## Quick Start

1) Install dependencies

```bash
composer install
npm install
```

2) Configure environment

```bash
cp .env.example .env
php artisan key:generate
# update .env with DB connection and APP_URL
```

3) Provision database and storage

```bash
php artisan migrate
php artisan db:seed --class=AccountSeeder
php artisan db:seed --class=ExpenseCategorySeeder
php artisan storage:link
```

4) Run the app

```bash
# option A: run everything together
composer run dev

# option B: separate terminals
php artisan serve
npm run dev
```

## Key URLs

- Accounts: /accounts/chart, /accounts/transactions, /accounts/reports
- Expenses: /expenses/categories, /expenses/create, /expenses/list, /expenses/reports
- Ledger: /accounts/ledger/{id}
- Expense statistics (AJAX): /expenses/statistics

## Testing

- Application tests: `php artisan test`
- Playwright (optional): see playwright/README.md for installing playwright dependencies and running browser tests.

## Documentation

- Detailed guide: docs/account_expense_management.md
- Quick setup checklist: docs/SETUP_GUIDE.md
- File inventory: docs/FILE_SUMMARY.md

## Troubleshooting

- Migration errors about references: ensure business locations and users exist before accounting tables; re-run `php artisan migrate` after fixing.
- Receipts not visible: re-run `php artisan storage:link` and check permissions on storage/app/public.
- Balances look off: confirm account types align with debit/credit rules and that seeds were applied once.

---

Last updated: January 25, 2026
