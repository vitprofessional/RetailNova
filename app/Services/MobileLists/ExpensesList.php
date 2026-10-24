<?php

namespace App\Services\MobileLists;

use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Support\LocalizedNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Expenses (desktop: ExpenseManagementController@expenseList, expense/list.blade.php). */
class ExpensesList extends MobileList
{
    public function key(): string
    {
        return 'expenses';
    }

    public function route(): string
    {
        return 'expense.list';
    }

    /** Same rule as the expense controller: store managers have no access to expenses. */
    public function authorize(): void
    {
        abort_if(strtolower((string) auth('admin')->user()?->role) === 'storemanager', 403, 'Unauthorized');
    }

    public function dated(): bool
    {
        return true;
    }

    public function create(): ?array
    {
        return [route('expense.create'), __('mobile.new_expense')];
    }

    public function searchPlaceholder(): string
    {
        return __('mobile.search_expenses');
    }

    public function filterFields(): array
    {
        return [
            'category' => ['label' => __('common.category'), 'type' => 'select',
                'options' => ['' => __('mobile.all_categories_filter')] + ExpenseCategory::active()->orderBy('name')->pluck('name', 'id')->all()],
            'method' => ['label' => __('common.payment_method'), 'type' => 'select',
                'options' => ['' => __('mobile.all_methods')] + collect(array_keys(ExpenseEntry::getPaymentMethods()))->mapWithKeys(fn ($m) => [$m => __('mobile.expense_method_'.$m)])->all()],
            'from' => ['label' => __('common.date_from'), 'type' => 'date'],
            'to' => ['label' => __('common.date_to'), 'type' => 'date'],
        ];
    }

    protected function query(array $filters): Builder
    {
        $query = ExpenseEntry::query()->with('category')->orderByDesc('expense_date')->orderByDesc('created_at');
        if (isset($filters['q'])) {
            $term = $this->like($filters['q']);
            $query->where(fn (Builder $q) => $q->where('reference_no', 'like', $term)->orWhere('description', 'like', $term));
        }
        if (isset($filters['from'])) {
            $query->where('expense_date', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->where('expense_date', '<=', $filters['to']);
        }
        if (isset($filters['category'])) {
            $query->where('category_id', (int) $filters['category']);
        }
        if (isset($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }

        return $query;
    }

    protected function summary(Builder $query, array $filters): array
    {
        $totals = $query->reorder()->toBase()->select(DB::raw('COALESCE(SUM(amount), 0) AS total, COUNT(*) AS entries'))->first();
        $entries = (int) ($totals->entries ?? 0);

        return [
            ['label' => __('mobile.summary_expenses'), 'value' => $this->money($totals->total ?? 0), 'tone' => 'danger'],
            ['label' => __('mobile.summary_entries'), 'value' => LocalizedNumber::count($entries)],
            ['label' => __('mobile.summary_average'), 'value' => $this->money($entries ? (float) $totals->total / $entries : 0)],
        ];
    }

    public function card(Model $expense): array
    {
        /** @var ExpenseEntry $expense */
        $category = $expense->category?->name ?? '-';

        return [
            'id' => $expense->id,
            'url' => route('expense.edit', $expense->id),
            'avatar' => $this->initial($category),
            'title' => $category,
            'subtitle' => $this->time($expense->created_at),
            'date_group' => $this->dateGroup($expense->expense_date),
            'amount' => $this->money($expense->amount),
            'amount_tone' => 'danger',
            'note' => $expense->reference_no ?: null,
            'note_tone' => 'muted',
            'badge' => null,
            'meta' => array_values(array_filter([
                __('mobile.expense_method_'.$expense->payment_method),
                $expense->description ? Str::limit($expense->description, 60) : null,
            ])),
            'actions' => [
                $this->link(__('mobile.action_edit'), route('expense.edit', $expense->id), 'ri-pencil-line'),
                // A delete link: the layout's handler confirms it and sends it as DELETE.
                ['type' => 'link', 'label' => __('mobile.action_delete'), 'url' => route('expense.delete', $expense->id), 'icon' => 'ri-delete-bin-line', 'tone' => 'danger'],
            ],
        ];
    }
}
