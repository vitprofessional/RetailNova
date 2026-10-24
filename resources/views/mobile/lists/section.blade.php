{{-- A secondary list under a mobile list page, folded away (e.g. older purchase lines). Hidden when empty. --}}
@php
    $page = $list->page([]);
    $key = $list->key();
@endphp
@if($page['total'] > 0)
    <details class="rn-m-list-section" data-rn-list="{{ $key }}">
        <summary>{{ $title }} <span class="rn-m-list-section-count">{{ \App\Support\LocalizedNumber::count($page['total']) }}</span></summary>
        <ul id="rnCards-{{ $key }}" class="rn-m-cards" role="list" data-rn-cards>
            @include('mobile.lists.cards', ['cards' => $page['cards']])
        </ul>
        <x-m.load-more :url="$page['next_page'] ? $list->endpoint(['page' => $page['next_page']]) : null"
                       :target="'rnCards-'.$key" :shown="$page['shown']" :total="$page['total']" />
    </details>
@endif
