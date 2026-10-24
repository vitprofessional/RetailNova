{{-- Shown instead of cards when a list has nothing to show (UI-2). --}}
@props(['icon' => 'ri-inbox-line', 'title', 'text' => null, 'actionUrl' => null, 'actionLabel' => null])
<div {{ $attributes->merge(['class' => 'rn-m-empty-state']) }} role="status">
    <span class="rn-m-empty-state-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
    <p class="rn-m-empty-state-title">{{ $title }}</p>
    @if($text)<p class="rn-m-empty-state-text">{{ $text }}</p>@endif
    @if($actionUrl)<a href="{{ $actionUrl }}" class="rn-m-btn rn-m-btn--outline">{{ $actionLabel }}</a>@endif
</div>
