<?php

namespace App\Services\MobileLists;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

/** Customers with their due (desktop: coustomerSupplier@addCustomer). */
class CustomersList extends ContactsList
{
    public function key(): string
    {
        return 'customers';
    }

    public function route(): string
    {
        return 'addCustomer';
    }

    public function searchPlaceholder(): string
    {
        return __('mobile.search_contacts');
    }

    protected function model(): Builder
    {
        return Customer::query();
    }

    /** What the customer still owes on live sales, as the sale screen's "previous due" counts it. */
    protected function dueSql(): string
    {
        return '(SELECT COALESCE(SUM(COALESCE(sale_products.grandTotal, 0) - COALESCE(sale_products.paidAmount, 0)), 0) '
            .'FROM sale_products WHERE sale_products.customerId = customers.id AND sale_products.deleted_at IS NULL)';
    }

    protected function lastTransactionSql(): string
    {
        return '(SELECT MAX(sale_products.created_at) FROM sale_products WHERE sale_products.customerId = customers.id AND sale_products.deleted_at IS NULL)';
    }

    protected function lang(): string
    {
        return 'customers';
    }

    protected function editRoute(): string
    {
        return 'editCustomer';
    }

    protected function deleteRoute(): string
    {
        return 'delCustomer';
    }
}
