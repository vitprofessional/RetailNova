<?php

namespace App\Services\MobileLists;

use App\Models\AdminUser;
use App\Services\PurchaseWorkflow;
use App\Support\LocalizedNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Customers and suppliers: contact cards with what is still owed (desktop: coustomerSupplier). */
abstract class ContactsList extends MobileList
{
    /** Model query for the list, keeping the soft-delete scope (deleted contacts are not listed). */
    abstract protected function model(): Builder;

    /** Correlated SQL for the amount still owed by (customer) or to (supplier) the row. */
    abstract protected function dueSql(): string;

    /** Translation file of the desktop page: "customers" or "suppliers". */
    abstract protected function lang(): string;

    abstract protected function editRoute(): string;

    abstract protected function deleteRoute(): string;

    public function create(): ?array
    {
        // The mobile page shows its own add form for ?new=1 (customers / suppliers).
        return [route($this->route(), ['new' => 1]), __('mobile.new_'.rtrim($this->key(), 's'))];
    }

    public function filterFields(): array
    {
        return [
            'due' => ['label' => __('mobile.due_filter'), 'type' => 'select', 'options' => [
                '' => __('mobile.due_filter_all'), 'with' => __('mobile.due_filter_with'), 'none' => __('mobile.due_filter_none'),
            ]],
        ];
    }

    private function base(array $filters): Builder
    {
        $table = $this->model()->getModel()->getTable();
        $query = app(PurchaseWorkflow::class)->scoped($this->model());
        if (isset($filters['q'])) {
            $term = $this->like($filters['q']);
            $query->where(fn (Builder $q) => $q->where($table.'.name', 'like', $term)
                ->orWhere($table.'.mobile', 'like', $term)->orWhere($table.'.mail', 'like', $term));
        }
        match ($filters['due'] ?? null) {
            'with' => $query->whereRaw($this->dueSql().' > 0'),
            'none' => $query->whereRaw($this->dueSql().' <= 0'),
            default => null,
        };

        return $query;
    }

    protected function query(array $filters): Builder
    {
        $table = $this->model()->getModel()->getTable();

        return $this->base($filters)->select($table.'.*')->selectRaw($this->dueSql().' AS amount_due')->orderByDesc($table.'.id');
    }

    protected function summary(Builder $query, array $filters): array
    {
        $totals = $this->base($filters)->toBase()->selectRaw(
            'COUNT(*) AS people, COALESCE(SUM('.$this->dueSql().'), 0) AS due, '
            .'COALESCE(SUM(CASE WHEN '.$this->dueSql().' > 0 THEN 1 ELSE 0 END), 0) AS owing'
        )->first();

        return [
            ['label' => __('mobile.summary_'.$this->key()), 'value' => LocalizedNumber::count($totals->people ?? 0)],
            ['label' => __('mobile.summary_total_due'), 'value' => $this->money($totals->due ?? 0), 'tone' => 'warning'],
            ['label' => __('mobile.summary_owing'), 'value' => LocalizedNumber::count($totals->owing ?? 0)],
        ];
    }

    public function card(Model $contact): array
    {
        $due = (float) $contact->amount_due;
        $opening = (int) ($contact->openingBalance ?? 0);
        $actor = auth('admin')->user();
        $lang = $this->lang();

        $actions = [];
        if ($contact->mobile) {
            $actions[] = $this->link(__('mobile.action_call'), 'tel:'.preg_replace('/[^0-9+]/', '', (string) $contact->mobile), 'ri-phone-line');
        }
        $actions[] = $this->link(__('mobile.action_edit'), route($this->editRoute(), ['id' => $contact->id]), 'ri-pencil-line');
        $actions[] = $this->form(__($lang.'.delete_profile_only'), route($this->deleteRoute(), ['id' => $contact->id]), 'ri-delete-bin-line', 'DELETE',
            __($lang.'.delete_only_the_'.rtrim($lang, 's').'_profile_data'), ['deleteType' => 'profileOnly']);
        if ($actor instanceof AdminUser && $actor->canDeleteAllRelatedData()) {
            $actions[] = $this->form(__($lang.'.delete_all_data'), route($this->deleteRoute(), ['id' => $contact->id]), 'ri-delete-bin-2-line', 'DELETE',
                __($lang.'.delete_all_data_confirm'), ['deleteType' => 'fullDelete']);
        }

        return [
            'id' => $contact->id,
            'url' => route($this->editRoute(), ['id' => $contact->id]),
            'avatar' => $this->initial($contact->name),
            'title' => $contact->name,
            'subtitle' => $contact->mobile ?: ($contact->mail ?: ''),
            'amount' => $due > 0 ? $this->money($due) : __('mobile.no_due'),
            'amount_tone' => $due > 0 ? 'warning' : 'muted',
            'note' => $due > 0 ? __('mobile.due_label_'.$this->key()) : null,
            'note_tone' => 'muted',
            'badge' => null,
            'meta' => array_values(array_filter([
                $contact->area ?: $contact->city,
                $opening !== 0 ? __($lang.'.opening_balance').': '.$this->money($opening) : null,
            ])),
            'actions' => $actions,
        ];
    }
}
