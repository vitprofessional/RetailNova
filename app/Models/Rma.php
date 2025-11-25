<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rma extends Model
{
    protected $table = 'rmas';

    protected $fillable = [
        'customer_id',
        'product_serial_id',
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
}
