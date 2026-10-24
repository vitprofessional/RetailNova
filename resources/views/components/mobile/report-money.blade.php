@props(['amount' => 0, 'format' => 'currency'])
@php
    $isMobileReport = app(\App\Support\MobileView::class)->isMobile();
    $renderedAmount = $isMobileReport
        ? \App\Support\LocalizedNumber::money($amount)
        : ($format === 'number'
            ? number_format((float) $amount, 2)
            : \App\Support\Currency::format((float) $amount, 2));
@endphp
{{ $renderedAmount }}
