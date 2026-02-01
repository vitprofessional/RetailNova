<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = ['name','price','duration_days','features','is_active'];
    protected $casts = [
        'price' => 'float',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
    ];
}
