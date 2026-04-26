<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'admin_users';

    protected $fillable = [
        'fullName', 'sureName', 'storeName', 'mail', 'contactNumber', 'password', 'businessId', 'role', 'avatar'
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

    public function isFirstCreatedAdmin(): bool
    {
        $firstId = (int) static::query()->min('id');
        return $firstId > 0 && (int)$this->id === $firstId;
    }

    public function hasSuperAdminPrivileges(): bool
    {
        // Backward-compatible: treat the first created admin as platform owner.
        return $this->isSuperAdminRole() || $this->isFirstCreatedAdmin();
    }

    public static function superAdminExists(): bool
    {
        return static::query()
            ->whereRaw("LOWER(REPLACE(role, ' ', '')) = ?", ['superadmin'])
            ->exists();
    }
}
