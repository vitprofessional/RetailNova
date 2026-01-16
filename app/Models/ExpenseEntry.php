<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class ExpenseEntry extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'expense_date',
        'category_id',
        'amount',
        'payment_method',
        'reference_no',
        'description',
        'receipt_file',
        'created_by',
        'created_by_type',
        'business_location_id',
        'account_transaction_id'
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Payment methods
    const PAYMENT_CASH = 'cash';
    const PAYMENT_BANK = 'bank';
    const PAYMENT_CARD = 'card';
    const PAYMENT_CHEQUE = 'cheque';
    const PAYMENT_MOBILE = 'mobile';

    public static function getPaymentMethods()
    {
        return [
            self::PAYMENT_CASH => 'Cash',
            self::PAYMENT_BANK => 'Bank Transfer',
            self::PAYMENT_CARD => 'Card',
            self::PAYMENT_CHEQUE => 'Cheque',
            self::PAYMENT_MOBILE => 'Mobile Payment',
        ];
    }

    /**
     * Get the expense category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
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
     * Get the related account transaction
     */
    public function accountTransaction(): BelongsTo
    {
        return $this->belongsTo(AccountTransaction::class);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    /**
     * Scope for specific category
     */
    public function scopeOfCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
