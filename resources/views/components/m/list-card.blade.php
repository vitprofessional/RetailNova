{{--
    One row of a mobile list as a card (UI-2). Tapping the card opens its detail page; the ⋮ menu
    holds the row's own actions. Card keys come from App\Services\MobileLists\MobileList::card():
    id, url (or null), avatar, title, subtitle, amount, amount_tone, note, note_tone,
    badge {label, tone} (or null), meta [text, …], actions [link | form, …].
--}}
@props(['card'])
@php
    $tag = $card['url'] ? 'a' : 'div';
    $menuId = 'rnCardMenu-'.substr(md5(($card['url'] ?? '').'|'.$card['id'].'|'.$card['title']), 0, 12);
@endphp
<li class="rn-m-card" data-rn-card="{{ $card['id'] }}">
    <{{ $tag }} @if($card['url']) href="{{ $card['url'] }}" @endif class="rn-m-card-main">
        <span class="rn-m-card-top">
            <span class="rn-m-card-avatar" aria-hidden="true">@if(!empty($card['avatar_url']))<img src="{{ $card['avatar_url'] }}" alt="" loading="lazy">@else{{ \App\Support\LocalizedNumber::digits((string) $card['avatar']) }}@endif</span>
            <span class="rn-m-card-body">
                <strong class="rn-m-card-title">{{ $card['title'] }}</strong>
                @if(($card['subtitle'] ?? '') !== '')<span class="rn-m-card-sub">{{ $card['subtitle'] }}</span>@endif
            </span>
            @if(!empty($card['amount']))<span class="rn-m-card-amount is-{{ $card['amount_tone'] ?? 'ink' }}">{{ $card['amount'] }}</span>@endif
        </span>
        <span class="rn-m-card-footer">
            <span class="rn-m-card-tags">
                @if(!empty($card['meta']))@foreach($card['meta'] as $meta)<span class="rn-m-card-tag">{{ $meta }}</span>@endforeach @endif
                @if(!empty($card['badge']))<span class="rn-m-badge rn-m-badge--{{ $card['badge']['tone'] }}">{{ $card['badge']['label'] }}</span>@endif
                @if(!empty($card['note']))<small class="rn-m-card-note is-{{ $card['note_tone'] ?? 'muted' }}">{{ $card['note'] }}</small>@endif
            </span>
            @if($card['url'])<i class="ri-arrow-right-s-line rn-m-card-chevron" aria-hidden="true"></i>@endif
        </span>
    </{{ $tag }}>
    @if(!empty($card['actions']))
        <div class="rn-m-card-menu">
            <button type="button" class="rn-m-card-menu-btn" data-rn-card-menu aria-haspopup="menu" aria-expanded="false" aria-controls="{{ $menuId }}"
                    aria-label="{{ __('mobile.card_actions', ['name' => $card['title']]) }}"><i class="ri-more-2-fill" aria-hidden="true"></i></button>
            <div id="{{ $menuId }}" class="rn-m-card-actions" role="menu" hidden>
                @foreach($card['actions'] as $action)
                    @if($action['type'] === 'link')
                        <a role="menuitem" href="{{ $action['url'] }}" class="rn-m-card-action{{ ($action['tone'] ?? null) === 'danger' ? ' is-danger' : '' }}"
                           @if(!empty($action['confirm'])) data-confirm="{{ $action['confirm'] }}" @endif>
                            <i class="{{ $action['icon'] }}" aria-hidden="true"></i><span>{{ $action['label'] }}</span>
                        </a>
                    @else
                        <form method="POST" action="{{ $action['url'] }}" class="rn-m-card-action-form" @foreach($action['attributes'] as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach>
                            @csrf
                            @if($action['method'] !== 'POST')<input type="hidden" name="_method" value="{{ $action['method'] }}">@endif
                            @foreach($action['fields'] as $field => $value)<input type="hidden" name="{{ $field }}" value="{{ $value }}">@endforeach
                            <button type="submit" role="menuitem" class="rn-m-card-action{{ ($action['tone'] ?? null) === 'danger' ? ' is-danger' : '' }}"
                                    @if($action['confirm'] !== null) data-confirm="{{ $action['confirm'] }}" @endif>
                                <i class="{{ $action['icon'] }}" aria-hidden="true"></i><span>{{ $action['label'] }}</span>
                            </button>
                        </form>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</li>
