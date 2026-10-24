<?php

namespace App\Services\MobileLists;

use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\SaleProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Sales history (desktop: saleController@saleList, sale/saleList.blade.php). */
class SalesList extends MobileList
{
    public function key(): string
    {
        return 'sales';
    }

    public function route(): string
    {
        return 'saleList';
    }

    public function dated(): bool
    {
        return true;
    }

    public function create(): ?array
    {
        return [route('newsale'), __('mobile.new_sale')];
    }

    public function searchPlaceholder(): string
    {
        return __('mobile.search_sales');
    }

    public function filterFields(): array
    {
        return [
            'customer' => ['label' => __('sales.customer'), 'type' => 'select',
                'options' => ['' => __('common.all_customers')] + Customer::query()->orderBy('name')->pluck('name', 'id')->all()],
            'status' => ['label' => __('sales.payment_status'), 'type' => 'select', 'options' => [
                '' => __('common.all_status'), 'paid' => __('sales.paid'), 'partial' => __('sales.partial'), 'due' => __('sales.due'),
            ]],
            'method' => ['label' => __('sales.payment_method'), 'type' => 'select',
                'options' => ['' => __('mobile.all_methods')] + collect(SaleProduct::PAYMENT_METHODS)->mapWithKeys(fn ($m) => [$m => __('sales.payment_method_'.$m)])->all()],
            'from' => ['label' => __('common.date_from'), 'type' => 'date'],
            'to' => ['label' => __('common.date_to'), 'type' => 'date'],
        ];
    }

    protected function query(array $filters): Builder
    {
        $actor = auth('admin')->user();
        $query = SaleProduct::query()->with('customer')->orderByDesc('id');
        // Like the desktop list: only the platform owner sees every salesperson's bills.
        if (!$actor instanceof AdminUser || !$actor->hasSuperAdminPrivileges()) {
            $query->where('salespersonId', $actor?->id ?? 0);
        }
        if (isset($filters['q'])) {
            $term = $this->like($filters['q']);
            $query->where(fn (Builder $q) => $q->where('invoice', 'like', $term)
                ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', $term)));
        }
        if (isset($filters['customer'])) {
            $query->where('customerId', (int) $filters['customer']);
        }
        // Same rules as the desktop status column: paid when nothing is due, partial when something was paid.
        match ($filters['status'] ?? null) {
            'paid' => $query->where('curDue', '<=', 0),
            'partial' => $query->where(fn (Builder $q) => $q->where('curDue', '>', 0)->orWhereNull('curDue'))->where('paidAmount', '>', 0),
            'due' => $query->where(fn (Builder $q) => $q->where('curDue', '>', 0)->orWhereNull('curDue'))
                ->where(fn (Builder $q) => $q->where('paidAmount', '<=', 0)->orWhereNull('paidAmount')),
            default => null,
        };
        if (isset($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }
        if (isset($filters['from'])) {
            $query->whereDate('date', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->whereDate('date', '<=', $filters['to']);
        }

        return $query;
    }

    protected function summary(Builder $query, array $filters): array
    {
        $totals = $query->reorder()->toBase()
            ->select(DB::raw('COALESCE(SUM(grandTotal),0) AS sales, COALESCE(SUM(paidAmount),0) AS paid, COALESCE(SUM(curDue),0) AS due'))
            ->first();

        return [
            ['label' => __('mobile.summary_sales'), 'value' => $this->money($totals->sales ?? 0)],
            ['label' => __('mobile.summary_received'), 'value' => $this->money($totals->paid ?? 0), 'tone' => 'success'],
            ['label' => __('mobile.summary_due'), 'value' => $this->money($totals->due ?? 0), 'tone' => 'warning'],
        ];
    }

    public function card(Model $sale): array
    {
        /** @var SaleProduct $sale */
        $due = (float) ($sale->curDue ?? 0);
        $status = $sale->curDue !== null && $due <= 0 ? 'paid' : ((float) $sale->paidAmount > 0 ? 'partial' : 'due');
        $actor = auth('admin')->user();
        $name = $sale->customer?->name ?? '-';

        $actions = [
            $this->link(__('mobile.action_view'), route('invoiceGenerate', ['id' => $sale->id]), 'ri-file-text-line'),
            $this->link(__('common.return'), route('returnSale', ['id' => $sale->id]), 'ri-arrow-go-back-line'),
            // @canEdit(true): every signed-in role may edit a sale.
            $this->link(__('mobile.action_edit'), route('sale.edit', ['id' => $sale->id]), 'ri-pencil-line'),
        ];
        if ($actor instanceof AdminUser && $actor->canCancelSales()) {
            // The reason prompt (partials.sale-cancel-script) runs on submit; skip the generic delete confirmation.
            $actions[] = $this->form(__('sales.cancel_sale'), route('delSale', ['id' => $sale->id]), 'ri-forbid-line', 'DELETE', 'false',
                ['reason' => ''], ['data-cancel-sale-form' => '']);
        }

        return [
            'id' => $sale->id,
            'url' => route('invoiceGenerate', ['id' => $sale->id]),
            'avatar' => $this->initial($name),
            'title' => $name,
            'subtitle' => trim($sale->invoice.' · '.$this->date($sale->date), ' ·'),
            'amount' => $this->money($sale->grandTotal ?? 0),
            'note' => $due > 0 ? __('mobile.due').' '.$this->money($due) : null,
            'note_tone' => 'warning',
            'badge' => ['label' => __('sales.'.$status), 'tone' => ['paid' => 'success', 'partial' => 'warning', 'due' => 'danger'][$status]],
            'meta' => array_values(array_filter([$sale->paymentMethodLabel()])),
            'actions' => $actions,
        ];
    }
}
