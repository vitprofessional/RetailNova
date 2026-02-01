<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = ['name','provider','mode','api_key','api_secret','is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
