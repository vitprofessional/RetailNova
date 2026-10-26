@props(['amount' => 0, 'format' => 'currency'])
@php($isMobileReport = app(\App\Support\MobileView::class)->isMobile())
@if($isMobileReport && $format !== 'number')
    <x-m.money :value="$amount" />
@else
    <span @if($isMobileReport) class="rn-m-number{{ is_numeric($amount) && (float) $amount < 0 ? ' is-negative' : '' }}" data-rn-component="money" @endif>{{ $isMobileReport ? \App\Support\LocalizedNumber::digits(number_format((float) $amount, 2)) : ($format === 'number' ? number_format((float) $amount, 2) : \App\Support\Currency::format((float) $amount, 2)) }}</span>
@endif
