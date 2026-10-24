<?php

namespace App\Services\MobileLists;

use App\Models\PurchaseProduct;
use App\Services\PurchaseWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Older purchase lines without an invoice header (the desktop "legacy records" section of the purchase list). */
class PurchaseLinesList extends MobileList
{
    public function key(): string
    {
        return 'purchase-lines';
    }

    public function route(): string
    {
        return 'purchaseList';
    }

    public function filterFields(): array
    {
        return [];
    }

    /** Unfiltered, like the desktop section; the page's search and filters apply to invoices only. */
    public function filters(\Illuminate\Http\Request $request): array
    {
        return [];
    }

    protected function query(array $filters): Builder
    {
        return app(PurchaseWorkflow::class)->scoped(PurchaseProduct::withoutGlobalScopes())
            ->whereNull('purchase_invoice_id')->with(['supplier', 'product'])->orderByDesc('id');
    }

    protected function summary(Builder $query, array $filters): array
    {
        return [];
    }

    public function card(Model $line): array
    {
        /** @var PurchaseProduct $line */
        $name = $line->product?->name ?? '-';

        return [
            'id' => $line->id,
            'url' => route('purchaseView', $line->id),
            'avatar' => $this->initial($name),
            'title' => $name,
            'subtitle' => trim($line->invoice.' · '.($line->getRelation('supplier')?->name ?? ''), ' ·'),
            'amount' => $this->money($line->grandTotal ?? $line->totalAmount ?? 0),
            'note' => (float) $line->dueAmount > 0 ? __('mobile.due').' '.$this->money($line->dueAmount) : null,
            'note_tone' => 'warning',
            'badge' => null,
            'meta' => array_values(array_filter([$this->date($line->purchase_date)])),
            'actions' => [$this->link(__('mobile.action_view'), route('purchaseView', $line->id), 'ri-file-text-line')],
        ];
    }
}
