<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessLocation extends Model
{
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'manager_name',
        'is_main_location',
        'status',
        'description',
    ];

    protected $casts = [
        'is_main_location' => 'boolean',
        'status' => 'boolean',
    ];

    public function getFullAddressAttribute()
    {
        return "{$this->address}, {$this->city}, {$this->state} {$this->postal_code}, {$this->country}";
    }
}
