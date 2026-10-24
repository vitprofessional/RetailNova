<?php

namespace App\Services\MobileLists;

use App\Support\LocalizedNumber;
use App\Support\MobileDateTime;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * One list page of the mobile version (UI-2): the rows the desktop page shows, as cards.
 *
 * The first page is rendered with the page itself; further pages come from the read-only
 * JSON endpoint (MobileListController) PER_PAGE at a time. Both read the same filters from
 * the query string, and every query keeps the desktop page's business scope and permissions.
 */
abstract class MobileList
{
    public const PER_PAGE = 20;

    /** Route-safe key used by the JSON endpoint, e.g. "sales". */
    abstract public function key(): string;

    /** The desktop page this list belongs to (filters, chips and the search submit here). */
    abstract public function route(): string;

    /** Rows the user may see, filtered and ordered like the desktop page. */
    abstract protected function query(array $filters): Builder;

    /** @return array<int, array{label: string, value: string, tone?: string}> exactly three figures */
    abstract protected function summary(Builder $query, array $filters): array;

    /** Card data for one row (see components/m/list-card.blade.php for the keys). */
    abstract public function card(Model $row): array;

    /**
     * Filter fields of the filter sheet: name => [label, type (select|date|text), options?].
     * Only these names are read from the request.
     */
    abstract public function filterFields(): array;

    /** Stops the request the same way the desktop page would (e.g. a role that may not see it). */
    public function authorize(): void
    {
    }

    /** Dated lists get the today / yesterday / this week / this month chips (filters "from" and "to"). */
    public function dated(): bool
    {
        return false;
    }

    /** The "+ new" button: [url, label], or null. */
    public function create(): ?array
    {
        return null;
    }

    public function searchPlaceholder(): string
    {
        return __('mobile.list_search');
    }

    /** @return array{icon: string, title: string, text: string} */
    public function emptyState(bool $filtered): array
    {
        return [
            'icon' => 'ri-inbox-line',
            'title' => $filtered ? __('mobile.list_no_match') : __('mobile.list_empty'),
            'text' => $filtered ? __('mobile.list_no_match_hint') : '',
        ];
    }

    /** Known, non-empty filters of the request; anything malformed is ignored. */
    public function filters(Request $request): array
    {
        $filters = [];
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $filters['q'] = mb_substr($q, 0, 100);
        }
        foreach ($this->filterFields() as $name => $field) {
            $value = $request->query($name);
            if (!is_string($value) || trim($value) === '') {
                continue;
            }
            $value = trim($value);
            $valid = match ($field['type']) {
                'select' => array_key_exists($value, $field['options'] ?? []),
                'date' => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false,
                default => true,
            };
            if ($valid) {
                $filters[$name] = mb_substr($value, 0, 100);
            }
        }

        return $filters;
    }

    /** One page of cards with the totals of the whole filtered list. */
    public function page(array $filters, int $page = 1): array
    {
        $this->authorize();
        $query = $this->query($filters);
        $total = (clone $query)->toBase()->getCountForPagination();
        $rows = (clone $query)->forPage(max(1, $page), self::PER_PAGE)->get();
        $shown = min($total, ($page - 1) * self::PER_PAGE + $rows->count());

        return [
            'cards' => $rows->map(fn (Model $row) => $this->card($row))->values()->all(),
            'page' => $page,
            'next_page' => $shown < $total ? $page + 1 : null,
            'total' => $total,
            'shown' => $shown,
            'summary' => $this->summary($this->query($filters), $filters),
        ];
    }

    /** today / yesterday / this week / this month; tapping the active chip clears the period. */
    public function periods(array $filters): array
    {
        $today = Carbon::today();
        $ranges = [
            'today' => [$today, $today],
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
            'this_week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        ];
        $chips = [];
        foreach ($ranges as $key => [$from, $to]) {
            $active = ($filters['from'] ?? null) === $from->toDateString() && ($filters['to'] ?? null) === $to->toDateString();
            $chips[] = [
                'key' => $key,
                'label' => __('mobile.period_'.$key),
                'active' => $active,
                'url' => $this->url(array_merge($filters, $active ? ['from' => null, 'to' => null] : ['from' => $from->toDateString(), 'to' => $to->toDateString()])),
            ];
        }

        return $chips;
    }

    /** Page URL with these filters (null values dropped). */
    public function url(array $filters): string
    {
        return route($this->route(), array_filter($filters, fn ($value) => $value !== null && $value !== ''));
    }

    public function endpoint(array $filters): string
    {
        return route('m.lists', array_merge(['list' => $this->key()], $filters));
    }

    /** Filters counted on the filter button (search and the period chips are visible elsewhere). */
    public function activeFilterCount(array $filters): int
    {
        return count(array_diff_key($filters, array_flip(['q', 'from', 'to'])));
    }

    // ---- helpers for card() and summary() ----

    protected function money(int|float|string|null $amount): string
    {
        return LocalizedNumber::money($amount);
    }

    protected function date(mixed $value): string
    {
        if (!$value) {
            return '';
        }
        try {
            return MobileDateTime::date($value);
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /** Date heading for the grouped mobile transaction cards. */
    protected function dateGroup(mixed $value): ?array
    {
        if (!$value) {
            return null;
        }

        try {
            $date = Carbon::parse($value)->startOfDay();
            $today = Carbon::today();
            $period = $date->isSameDay($today)
                ? __('mobile.period_today')
                : ($date->isSameDay($today->copy()->subDay())
                    ? __('mobile.period_yesterday')
                    : null);
            $dateLabel = LocalizedNumber::digits($date->locale(app()->getLocale())->translatedFormat('j F'));

            return ['key' => $date->toDateString(), 'label' => $period ? $period.' · '.$dateLabel : $dateLabel];
        } catch (\Throwable) {
            return null;
        }
    }

    protected function time(mixed $value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return MobileDateTime::time($value);
        } catch (\Throwable) {
            return '';
        }
    }

    protected function like(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }

    /** First letter of a name, for the card avatar. */
    protected function initial(?string $name): string
    {
        return preg_match('/\p{L}/u', (string) $name, $letter) ? mb_strtoupper($letter[0]) : '#';
    }

    /** A ⋮ menu entry that is a plain link. */
    protected function link(string $label, string $url, string $icon): array
    {
        return ['type' => 'link', 'label' => $label, 'url' => $url, 'icon' => $icon];
    }

    /** A ⋮ menu entry that submits a POST form (method spoofing and confirmation optional). */
    protected function form(string $label, string $url, string $icon, string $method = 'DELETE', ?string $confirm = null, array $fields = [], array $attributes = []): array
    {
        return ['type' => 'form', 'label' => $label, 'url' => $url, 'icon' => $icon, 'method' => $method,
            'confirm' => $confirm, 'fields' => $fields, 'attributes' => $attributes, 'tone' => 'danger'];
    }
}
