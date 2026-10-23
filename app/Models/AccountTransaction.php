<?php

namespace App\Models;

use App\Models\Concerns\ScopesByBusiness;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class AccountTransaction extends Model implements Auditable
{
    use AuditableTrait, ScopesByBusiness;

    protected $fillable = ['businessId', 
        'transaction_date',
        'transaction_type',
        'reference_no',
        'debit_account_id',
        'credit_account_id',
        'amount',
        'description',
        'created_by',
        'created_by_type',
        'business_location_id'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Transaction types
    const TYPE_JOURNAL = 'journal';
    const TYPE_PAYMENT = 'payment';
    const TYPE_RECEIPT = 'receipt';
    const TYPE_EXPENSE = 'expense';
    const TYPE_SALE = 'sale';
    const TYPE_PURCHASE = 'purchase';
    const TYPE_TRANSFER = 'transfer';

    public static function getTransactionTypes()
    {
        return [
            self::TYPE_JOURNAL => __('common.journal_entry'),
            self::TYPE_PAYMENT => __('common.payment'),
            self::TYPE_RECEIPT => __('common.receipt'),
            self::TYPE_EXPENSE => __('common.expense'),
            self::TYPE_SALE => __('common.sale'),
            self::TYPE_PURCHASE => __('common.purchase'),
            self::TYPE_TRANSFER => __('common.transfer'),
        ];
    }

    /**
     * Get the debit account
     */
    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    /**
     * Get the credit account
     */
    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the business location
     */
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope for specific transaction type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }
}
