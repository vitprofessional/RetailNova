<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSerial extends Model
{
    protected $fillable = [
        'productId',
        'purchaseId',
        'serialNumber',
    ];

    // keep default table name 'product_serials'
}
