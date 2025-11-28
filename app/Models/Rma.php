<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rma extends Model
{
    protected $table = 'rmas';

    protected $fillable = [
        'customer_id',
        'product_serial_id',
        'rma_no',
        'reason',
        'notes',
        'status',
        'created_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function productSerial()
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id');
    }

    protected static function booted()
    {
        // After an RMA is created, populate a human-readable RMA number if missing.
        static::created(function (Rma $rma) {
            try {
                if (empty($rma->rma_no)) {
                    $prefix = 'RMA' . now()->format('Ymd');
                    $seq = str_pad($rma->id, 6, '0', STR_PAD_LEFT);
                    $rma->rma_no = $prefix . $seq;
                    // save quietly to avoid recursion into created event
                    $rma->saveQuietly();
                }
            } catch (\Throwable $e) {
                // don't let RMA numbering failures block creation; log when possible
                try { \Log::warning('Failed to generate rma_no: ' . $e->getMessage()); } catch (\Throwable $_) {}
            }
        });
    }
}
