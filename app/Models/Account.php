<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Account extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_account_id',
        'opening_balance',
        'current_balance',
        'description',
        'is_active',
        'business_location_id'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Account types
    const TYPE_ASSET = 'asset';
    const TYPE_LIABILITY = 'liability';
    const TYPE_EQUITY = 'equity';
    const TYPE_REVENUE = 'revenue';
    const TYPE_EXPENSE = 'expense';

    public static function getAccountTypes()
    {
        return [
            self::TYPE_ASSET => 'Asset',
            self::TYPE_LIABILITY => 'Liability',
            self::TYPE_EQUITY => 'Equity',
            self::TYPE_REVENUE => 'Revenue',
            self::TYPE_EXPENSE => 'Expense',
        ];
    }

    /**
     * Get the parent account
     */
    public function parentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    /**
     * Get the child accounts
     */
    public function childAccounts(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_account_id');
    }

    /**
     * Get the business location
     */
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    /**
     * Get all transactions for this account
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class, 'account_id');
    }

    /**
     * Get debit transactions
     */
    public function debitTransactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class, 'debit_account_id');
    }

    /**
     * Get credit transactions
     */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class, 'credit_account_id');
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific account type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('account_type', $type);
    }
}
