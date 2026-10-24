<?php

namespace App\Models;

use App\Models\Concerns\ScopesByBusiness;

use Illuminate\Database\Eloquent\Model;

class ProvideService extends Model
{
    use ScopesByBusiness;
    protected $fillable = ['businessId', 
        'customerName',
        'serviceName',
        'amount',
        'note',
        'qty',
        'rate'
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'qty' => 'integer',
    ];

    /**
     * Customer shown for this row. Reads customer_name from the usual
     * "customers.name AS customer_name" join; walk-in services store no customer.
     */
    public function customerLabel(): string
    {
        if ($this->customer_name !== null && $this->customer_name !== '') {
            return Customer::displayName($this->customer_name);
        }

        return Customer::displayLabel(null, $this->customerName, $this->businessId);
    }
}
