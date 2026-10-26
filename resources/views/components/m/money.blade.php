@props(['value' => 0, 'fit' => false, 'role' => 'amount'])
<span data-rn-component="money" {{ $attributes->class(['rn-m-number', 'rn-m-money-fit' => $fit])->merge($fit ? ['data-rn-fit' => $role] : []) }}>{{ \App\Support\LocalizedNumber::money($value) }}</span>
