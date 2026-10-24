{{-- Floating "+ New" button above the tab bar (UI-2). The label names what is created, for screen readers. --}}
@props(['url', 'label'])
<a href="{{ $url }}" {{ $attributes->merge(['class' => 'rn-m-fab']) }} aria-label="{{ $label }}">
    <i class="ri-add-line" aria-hidden="true"></i><span aria-hidden="true">{{ __('mobile.fab_new') }}</span>
</a>
