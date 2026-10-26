@props(['value' => 0, 'decimals' => 0])
<span data-rn-component="qty" {{ $attributes->class(['rn-m-number']) }}>{{ \App\Support\LocalizedNumber::grouped($value, null, (int) $decimals) }}</span>
