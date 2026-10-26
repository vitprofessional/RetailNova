@props(['value' => null])
<time data-rn-component="date" {{ $attributes->class(['rn-m-number']) }}>{{ \App\Support\MobileDateTime::date($value) }}</time>
