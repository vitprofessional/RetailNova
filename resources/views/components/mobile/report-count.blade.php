@props(['value' => 0])
@if(app(\App\Support\MobileView::class)->isMobile()){{ \App\Support\LocalizedNumber::count($value) }}@else{{ $value }}@endif
