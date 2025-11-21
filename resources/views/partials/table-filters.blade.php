@php
    // Expected params:
    // 'tableId' => (string) id of the table
    // 'searchId' => optional id for the search input
    // 'selects' => array of ['id'=>'filterCustomer','label'=>'Customer','options'=>Collection|array]
    // 'date' => true/false (show from/to)
    $tableId = $tableId ?? null;
    $searchId = $searchId ?? ($tableId ? $tableId.'-search' : 'table-search');
    $selects = $selects ?? [];
    $showDate = $date ?? false;
    $searchPlaceholder = $searchPlaceholder ?? 'Search...';
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        @foreach($selects as $s)
            <div class="form-inline">
                <select id="{{ $s['id'] }}" class="form-control rn-filter-input" data-filter-for="{{ $s['id'] }}" data-table-target="{{ $tableId }}">
                    <option value="">All {{ $s['label'] }}</option>
                    @if(!empty($s['options']))
                        @foreach($s['options'] as $opt)
                            @if(is_object($opt))
                                <option value="{{ $opt->id ?? $opt->name ?? $opt->title ?? $opt }}">{{ $opt->name ?? $opt->title ?? $opt }}</option>
                            @else
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endif
                        @endforeach
                    @endif
                </select>
            </div>
        @endforeach
        @if($showDate)
            <div class="form-inline d-flex align-items-center">
                <input type="date" id="{{ $tableId }}-filterDateFrom" class="form-control rn-filter-input" data-filter-date="from" data-table-target="{{ $tableId }}">
                <span class="mx-2">to</span>
                <input type="date" id="{{ $tableId }}-filterDateTo" class="form-control rn-filter-input" data-filter-date="to" data-table-target="{{ $tableId }}">
            </div>
        @endif
    </div>
    <div class="rn-search">
        <div class="rn-search-box">
            <i class="las la-search rn-search-icon"></i>
            <input id="{{ $searchId }}" type="text" class="form-control rn-search-input rn-filter-input" data-table-target="{{ $tableId }}" placeholder="{{ $searchPlaceholder }}" />
            <button class="rn-search-clear" type="button" aria-label="Clear search"><i class="las la-times"></i></button>
        </div>
    </div>
</div>