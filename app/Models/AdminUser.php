<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'admin_users';

    protected $fillable = [
        'fullName', 'sureName', 'storeName', 'mail', 'contactNumber', 'password', 'businessId', 'role', 'avatar', 'preferred_locale'
    ];


    protected $hidden = [
        'password',
    ];

    public function normalizedRole(): string
    {
        $role = (string)($this->role ?? '');
        $role = strtolower(trim($role));
        return preg_replace('/[^a-z]/', '', $role) ?: '';
    }

    public function isSuperAdminRole(): bool
    {
        return $this->normalizedRole() === 'superadmin';
    }

    /** Cancelling a sale reverses stock and serials, so it is limited to admins. */
    public function canCancelSales(): bool
    {
        return in_array($this->normalizedRole(), ['admin', 'superadmin'], true);
    }

    public function hasSuperAdminPrivileges(): bool
    {
        // Platform privileges are granted only by an explicit, normalized role.
        return $this->isSuperAdminRole();
    }

    public function canManageSalesSettings(): bool
    {
        return $this->normalizedRole() === 'admin' || $this->hasSuperAdminPrivileges();
    }

    public function canAccessAllSalesBusinesses(): bool
    {
        return $this->hasSuperAdminPrivileges();
    }

    public static function anyAdminExists(): bool
    {
        return static::query()->exists();
    }
}
