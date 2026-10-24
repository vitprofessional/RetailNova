{{--
    Cancel / Save bar that stays at the bottom of a mobile form or sheet (UI-2). Cancel is a link
    when "cancelUrl" is given, otherwise it closes the surrounding sheet. Save submits the form.
--}}
@props(['cancelUrl' => null, 'cancelLabel' => null, 'submitLabel' => null])
<div {{ $attributes->merge(['class' => 'rn-m-sticky-actions']) }}>
    @if($cancelUrl)
        <a href="{{ $cancelUrl }}" class="rn-m-btn rn-m-btn--outline">{{ $cancelLabel ?? __('mobile.cancel') }}</a>
    @else
        <button type="button" class="rn-m-btn rn-m-btn--outline" data-rn-sheet-close>{{ $cancelLabel ?? __('mobile.cancel') }}</button>
    @endif
    <button type="submit" class="rn-m-btn rn-m-btn--primary">{{ $submitLabel ?? __('mobile.save') }}</button>
</div>
