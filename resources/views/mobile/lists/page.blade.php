{{--
    A mobile list page (UI-2): search, period chips, three-figure summary, the first page of cards,
    "load more", "+ new" and the filter sheet. Everything is read from the query string, so links,
    the back button and a reload keep the filters. $list: App\Services\MobileLists\MobileList.
--}}
@php
    $filters = $list->filters(request());
    $page = $list->page($filters);
    $key = $list->key();
    $sheetId = 'rnFilters-'.$key;
    $filterCount = $list->activeFilterCount($filters);
    $create = $list->create();
    $empty = $list->emptyState($filters !== []);
@endphp
@include('sweetalert::alert')
<section class="rn-m-list-page" data-rn-list="{{ $key }}">
    <div class="rn-m-list-header">
        <div class="rn-m-list-toolbar">
            <form method="GET" action="{{ route($list->route()) }}" class="rn-m-search" role="search">
                @foreach(array_diff_key($filters, ['q' => true]) as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                <i class="ri-search-line" aria-hidden="true"></i>
                <label class="sr-only" for="rnSearch-{{ $key }}">{{ $list->searchPlaceholder() }}</label>
                <input id="rnSearch-{{ $key }}" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ $list->searchPlaceholder() }}" enterkeyhint="search" autocomplete="off">
            </form>
            @if($list->filterFields())
                <button type="button" class="rn-m-filter-btn{{ $filterCount ? ' is-active' : '' }}" data-rn-sheet-open="{{ $sheetId }}" aria-controls="{{ $sheetId }}" aria-expanded="false" aria-label="{{ __('mobile.filter') }}">
                    <i class="ri-equalizer-line" aria-hidden="true"></i>
                    @if($filterCount)<span class="rn-m-filter-count">{{ \App\Support\LocalizedNumber::count($filterCount) }}</span>@endif
                </button>
            @endif
        </div>

        @if($list->dated())
            <x-m.period-chips :periods="$list->periods($filters)" :label="__('mobile.period')" />
        @endif
    </div>

    <x-m.summary-bar :items="$page['summary']" />

    <ul id="rnCards-{{ $key }}" class="rn-m-cards" role="list" data-rn-cards>
        @include('mobile.lists.cards', ['cards' => $page['cards']])
    </ul>
    @if($page['total'] === 0)
        <x-m.empty-state :icon="$empty['icon']" :title="$empty['title']" :text="$empty['text']"
                         :action-url="$filters !== [] ? route($list->route()) : null" :action-label="__('mobile.filter_reset')" />
    @endif
    <x-m.load-more :url="$page['next_page'] ? $list->endpoint($filters + ['page' => $page['next_page']]) : null"
                   :target="'rnCards-'.$key" :shown="$page['shown']" :total="$page['total']" />

    @if($create)
        <x-m.fab :url="$create[0]" :label="$create[1]" />
    @endif

    @if($list->filterFields())
        <x-m.filter-sheet :id="$sheetId" :title="__('mobile.filter')" :action="route($list->route())" :reset-url="route($list->route())"
                          :keep="array_intersect_key($filters, ['q' => true])">
            <div class="rn-m-filter-fields">
            @foreach($list->filterFields() as $name => $field)
                @if(in_array($name, ['status', 'method'], true) && $field['type'] === 'select')
                    <fieldset class="rn-m-filter-chip-group">
                        <legend>{{ $field['label'] }}</legend>
                        @foreach($field['options'] as $value => $label)
                            <label class="rn-m-filter-chip{{ (string) ($filters[$name] ?? '') === (string) $value ? ' is-selected' : '' }}">
                                <input type="radio" name="{{ $name }}" value="{{ $value }}" @checked((string) ($filters[$name] ?? '') === (string) $value)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                @elseif(in_array($name, ['from', 'to'], true))
                    @if($name === 'from')
                        <div class="rn-m-filter-date-grid">
                            @foreach(['from', 'to'] as $dateName)
                                @if(isset($list->filterFields()[$dateName]))
                                    @php($dateField = $list->filterFields()[$dateName])
                                    <x-m.field :name="$dateName" :label="$dateField['label']" type="date" :value="$filters[$dateName] ?? ''" :id="$sheetId.'-'.$dateName" />
                                @endif
                            @endforeach
                        </div>
                    @endif
                @else
                    <x-m.field :name="$name" :label="$field['label']" :type="$field['type']" :options="$field['options'] ?? null"
                               :value="$filters[$name] ?? ''" :id="$sheetId.'-'.$name" />
                @endif
            @endforeach
            </div>
        </x-m.filter-sheet>
    @endif
</section>
