<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'openingBalance',
        'mail',
        'mobile',
        'country',
        'state',
        'city',
        'area',
    ];

    protected $casts = [
        'openingBalance'=> 'integer',
    ];

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([$this->area, $this->city, $this->state, $this->country]);
        return implode(', ', $parts);
    }
}
