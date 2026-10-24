{{--
    "Load more" under a list (UI-2): fetches the next page of cards as JSON (MobileListController)
    and appends its HTML to the list with id "target". Hidden when every card is shown.
--}}
@props(['url' => null, 'target', 'shown', 'total'])
<div class="rn-m-load-more" data-rn-load-more="{{ $target }}" data-i18n-count="{{ __('mobile.showing_count') }}" data-i18n-failed="{{ __('mobile.load_failed') }}" @unless($url) hidden @endunless>
    <p class="rn-m-load-more-count" data-rn-load-more-count aria-live="polite">{{ __('mobile.showing_count', ['shown' => \App\Support\LocalizedNumber::count($shown), 'total' => \App\Support\LocalizedNumber::count($total)]) }}</p>
    <button type="button" class="rn-m-btn rn-m-btn--outline rn-m-btn--block" data-rn-load-more-button data-url="{{ $url }}">{{ __('mobile.load_more') }}</button>
</div>
