{{--
    Filters in a sheet that opens from the bottom (UI-2). A GET form back to the page: "keep" holds
    values set elsewhere on the page (e.g. the search text); the slot holds <x-m.field> inputs.
--}}
@props(['id', 'title', 'action', 'resetUrl', 'keep' => []])
<div id="{{ $id }}" class="rn-m-bottom-sheet" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" data-rn-sheet hidden>
    <div class="rn-m-bottom-sheet-backdrop" data-rn-sheet-close></div>
    <form method="GET" action="{{ $action }}" class="rn-m-bottom-sheet-panel">
        <header class="rn-m-bottom-sheet-header">
            <h2 id="{{ $id }}-title">{{ $title }}</h2>
            <button type="button" class="rn-m-icon-btn" data-rn-sheet-close aria-label="{{ __('mobile.close') }}"><i class="ri-close-line" aria-hidden="true"></i></button>
        </header>
        @foreach($keep as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
        <div class="rn-m-bottom-sheet-body">{{ $slot }}</div>
        <x-m.sticky-actions :cancel-url="$resetUrl" :cancel-label="__('mobile.filter_reset')" :submit-label="__('mobile.filter_apply')" />
    </form>
</div>
