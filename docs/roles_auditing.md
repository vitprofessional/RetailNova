# Roles & Auditing Overview

## Roles
User accounts now include a `role` column (default `staff`). Suggested values:
- `staff`: Limited CRUD, cannot delete core entities.
- `admin`: Full CRUD, can delete (non-force) Products/Brands/Categories/ProductUnits.
- `superadmin`: All admin powers plus force delete reserved in policies.

### Assigning Roles
Use artisan tinker or a seeder:
```php
\App\Models\User::where('email','owner@example.com')->update(['role'=>'superadmin']);
```

### Policy Enforcement
Delete operations invoke `$this->authorize('delete', $model)`; allowed roles:
- delete: `admin`,`superadmin`
- forceDelete: `superadmin`

## Session ↔ Auth Synchronization
Middleware `SyncSessionUser` auto-logins a user using session keys:
- Primary key: `pos_user_id`
- Legacy fallback: `pos`
Set these on login:
```php
session(['pos_user_id'=>$adminUser->id,'pos'=>$adminUser->id]);
```

## Auditing
Installed `owen-it/laravel-auditing`.
Models audited: Product, PurchaseProduct, SaleProduct, ProductStock, InvoiceItem, ReturnSaleItem, SaleReturn.
Excluded: `updated_at` per model via `$auditExclude`.

### Custom User Resolver
`SessionUserResolver` returns `Auth::user()` or finds a user from session keys. Configure in `config/audit.php` under `user.resolver`.

### Viewing Audits
Query the `audits` table:
```sql
SELECT * FROM audits ORDER BY id DESC LIMIT 50;
```
Each row contains:
- `user_type` / `user_id`
- `event` (created, updated, deleted, restored)
- `old_values`, `new_values`

### Adding More Models
To audit another model:
```php
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
class YourModel extends Model implements AuditableContract { use Auditable; }
```

### Fine Tuning
Edit `config/audit.php`:
- Add global excludes in `'exclude' => ['remember_token']`
- Enable timestamps auditing: set `'timestamps' => true`
- Limit records per model: set `'threshold' => 1000`

## Logout Flow
`userInfo::logout()` now:
1. `Auth::logout()`
2. Clears session keys
3. Invalidates session & regenerates CSRF token

## Next Suggestions
- Add UI to manage user roles
- Build audit viewer page (filter by model, user, date range)
- Add alerts for high-risk events (e.g., bulk deletions)
- Queue audit writing if performance becomes concern (enable queue in config)
