<?php

namespace App\Services\MobileLists;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseProduct;
use App\Models\Supplier;
use App\Services\PurchaseWorkflow;
use App\Support\LocalizedNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Purchase invoices (desktop: PurchaseInvoiceController@index, purchase/invoiceList.blade.php). */
class PurchasesList extends MobileList
{
    public function key(): string
    {
        return 'purchases';
    }

    public function route(): string
    {
        return 'purchaseList';
    }

    public function dated(): bool
    {
        return true;
    }

    public function create(): ?array
    {
        return [route('addPurchase'), __('mobile.new_purchase')];
    }

    public function searchPlaceholder(): string
    {
        return __('mobile.search_purchases');
    }

    public function filterFields(): array
    {
        $workflow = app(PurchaseWorkflow::class);

        return [
            'supplier' => ['label' => __('purchases.supplier'), 'type' => 'select',
                'options' => ['' => __('purchases.all_suppliers')] + $workflow->scoped(Supplier::query())->orderBy('name')->pluck('name', 'id')->all()],
            'status' => ['label' => __('purchases.payment_status'), 'type' => 'select', 'options' => [
                '' => __('purchases.all_payment_statuses'), 'paid' => __('invoices.status_paid'), 'due' => __('invoices.status_due'), 'partial' => __('invoices.status_partial'),
            ]],
            'from' => ['label' => __('common.from'), 'type' => 'date'],
            'to' => ['label' => __('common.to'), 'type' => 'date'],
        ];
    }

    protected function query(array $filters): Builder
    {
        $items = PurchaseProduct::withoutGlobalScopes()
            ->selectRaw('purchase_invoice_id, COUNT(*) AS item_count, SUM(qty) AS quantity_count')
            ->whereNotNull('purchase_invoice_id')->groupBy('purchase_invoice_id');
        $query = app(PurchaseWorkflow::class)->scoped(PurchaseInvoice::query())->with('supplier')
            ->leftJoinSub($items, 'item_totals', fn ($join) => $join->on('item_totals.purchase_invoice_id', '=', 'purchase_invoices.id'))
            ->select('purchase_invoices.*', 'item_totals.item_count', 'item_totals.quantity_count')
            ->orderByDesc('purchase_invoices.id');
        if (isset($filters['q'])) {
            $term = $this->like($filters['q']);
            $query->where(fn (Builder $q) => $q->where('purchase_invoices.invoice', 'like', $term)
                ->orWhereHas('supplier', fn (Builder $s) => $s->where('name', 'like', $term)));
        }
        if (isset($filters['supplier'])) {
            $query->where('purchase_invoices.supplier_id', (int) $filters['supplier']);
        }
        if (isset($filters['from'])) {
            $query->whereDate('purchase_invoices.purchase_date', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->whereDate('purchase_invoices.purchase_date', '<=', $filters['to']);
        }
        match ($filters['status'] ?? null) {
            'paid' => $query->where('purchase_invoices.due_amount', 0),
            'due' => $query->where('purchase_invoices.due_amount', '>', 0)->where('purchase_invoices.paid_amount', 0),
            'partial' => $query->where('purchase_invoices.due_amount', '>', 0)->where('purchase_invoices.paid_amount', '>', 0),
            default => null,
        };

        return $query;
    }

    protected function summary(Builder $query, array $filters): array
    {
        $totals = $query->reorder()->toBase()->select(DB::raw(
            'COALESCE(SUM(purchase_invoices.grand_total), 0) AS total, COALESCE(SUM(purchase_invoices.paid_amount), 0) AS paid, '
            .'COALESCE(SUM(purchase_invoices.due_amount), 0) AS due'
        ))->first();

        return [
            ['label' => __('mobile.summary_purchases'), 'value' => $this->money($totals->total ?? 0)],
            ['label' => __('mobile.summary_paid'), 'value' => $this->money($totals->paid ?? 0), 'tone' => 'success'],
            ['label' => __('mobile.summary_due'), 'value' => $this->money($totals->due ?? 0), 'tone' => 'warning'],
        ];
    }

    public function card(Model $invoice): array
    {
        /** @var PurchaseInvoice $invoice */
        $due = (float) $invoice->due_amount;
        $status = $due === 0.0 ? 'paid' : ((float) $invoice->paid_amount > 0 ? 'partial' : 'due');
        $name = $invoice->supplier?->name ?? '-';

        return [
            'id' => $invoice->id,
            'url' => route('purchase.invoice.show', $invoice->id),
            'avatar' => $this->initial($name),
            'title' => $name,
            'subtitle' => trim($invoice->invoice.' · '.$this->time($invoice->created_at), ' ·'),
            'date_group' => $this->dateGroup($invoice->purchase_date),
            'amount' => $this->money($invoice->grand_total),
            'note' => $due > 0 ? __('mobile.due').' '.$this->money($due) : null,
            'note_tone' => 'warning',
            'badge' => ['label' => __('invoices.status_'.$status), 'tone' => ['paid' => 'success', 'partial' => 'warning', 'due' => 'danger'][$status]],
            'meta' => [__('mobile.purchase_items', [
                'items' => LocalizedNumber::count($invoice->item_count ?? 0), 'qty' => LocalizedNumber::count($invoice->quantity_count ?? 0),
            ])],
            // The desktop page offers view, edit and (bulk) delete to everyone who can open it.
            'actions' => [
                $this->link(__('mobile.action_view'), route('purchase.invoice.show', $invoice->id), 'ri-file-text-line'),
                $this->link(__('mobile.action_edit'), route('purchase.invoice.edit', $invoice->id), 'ri-pencil-line'),
                $this->form(__('mobile.action_delete'), route('purchase.invoice.destroy', $invoice->id), 'ri-delete-bin-line', 'DELETE', __('mobile.confirm_delete_purchase')),
            ],
        ];
    }
}
