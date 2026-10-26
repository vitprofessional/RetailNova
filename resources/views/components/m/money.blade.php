@props(['value' => 0, 'fit' => false, 'role' => 'amount'])
@php($negative = is_numeric($value) && (float) $value < 0)
<span data-rn-component="money" {{ $attributes->class(['rn-m-number', 'rn-m-money-fit' => $fit, 'is-negative' => $negative])->merge($fit ? ['data-rn-fit' => $role] : []) }}>{{ \App\Support\LocalizedNumber::money($value) }}</span>
