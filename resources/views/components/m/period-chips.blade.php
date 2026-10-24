{{-- Today / yesterday / this week / this month (UI-2). Chips are links; the active one links back to "any date". --}}
@props(['periods', 'label'])
<nav {{ $attributes->merge(['class' => 'rn-m-periods']) }} aria-label="{{ $label }}">
    @foreach($periods as $period)
        <a href="{{ $period['url'] }}" class="rn-m-chip{{ $period['active'] ? ' is-active' : '' }}" data-rn-period="{{ $period['key'] }}" @if($period['active']) aria-current="true" @endif>{{ $period['label'] }}</a>
    @endforeach
</nav>
