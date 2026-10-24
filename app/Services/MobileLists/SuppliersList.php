<?php

namespace App\Services\MobileLists;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

/** Suppliers with what is still owed to them (desktop: coustomerSupplier@addSupplier). */
class SuppliersList extends ContactsList
{
    public function key(): string
    {
        return 'suppliers';
    }

    public function route(): string
    {
        return 'addSupplier';
    }

    public function searchPlaceholder(): string
    {
        return __('mobile.search_contacts');
    }

    /** Unlike the desktop table, deleted suppliers stay out of the list (they keep their own restore list there). */
    protected function model(): Builder
    {
        return Supplier::withoutGlobalScope('business');
    }

    /** Due of purchase invoices plus older purchase lines that have no invoice header. */
    protected function dueSql(): string
    {
        return '((SELECT COALESCE(SUM(purchase_invoices.due_amount), 0) FROM purchase_invoices WHERE purchase_invoices.supplier_id = suppliers.id)'
            .' + (SELECT COALESCE(SUM(purchase_products.dueAmount), 0) FROM purchase_products'
            .' WHERE purchase_products.supplier = suppliers.id AND purchase_products.purchase_invoice_id IS NULL))';
    }

    protected function lang(): string
    {
        return 'suppliers';
    }

    protected function editRoute(): string
    {
        return 'editSupplier';
    }

    protected function deleteRoute(): string
    {
        return 'delSupplier';
    }
}
