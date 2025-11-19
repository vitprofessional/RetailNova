@php
    $searchId = $searchId ?? 'table-search';
    $tableId = $tableId ?? null; // id of table to filter
    $placeholder = $placeholder ?? 'Search...';
@endphp
<div class="rn-search mb-3">
    <div class="rn-search-box">
        <i class="ri-search-line rn-search-icon"></i>
        <input type="text" id="{{ $searchId }}" data-table-target="{{ $tableId }}" class="form-control rn-search-input" placeholder="{{ $placeholder }}">
        <button type="button" class="rn-search-clear" title="Clear">
            <i class="ri-close-circle-line" style="font-size:18px;"></i>
        </button>
    </div>
</div>