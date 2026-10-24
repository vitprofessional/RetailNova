{{-- A phone or tablet showing the desktop version (its own choice) gets a way back. Real desktops never see this. --}}
@php $viewMode = app(\App\Support\MobileView::class); @endphp
@if($viewMode->isMobileDevice() && $viewMode->choice() === 'desktop')
    <form method="POST" action="{{ route('view.mode') }}" class="rn-view-return">
        @csrf
        <button type="submit" name="mode" value="mobile"><i class="ri-smartphone-line" aria-hidden="true"></i>{{ __('mobile.view_mobile') }}</button>
    </form>
    <style>
        .rn-view-return { position: fixed; right: 12px; bottom: 12px; z-index: 1080; margin: 0; }
        .rn-view-return button {
            display: inline-flex; align-items: center; gap: 6px; min-height: 44px; padding: 0 18px;
            border: 0; border-radius: var(--rn-radius-pill); background: var(--rn-color-primary);
            color: var(--rn-color-on-primary); font-weight: 600; box-shadow: var(--rn-shadow-raised);
        }
        @media print { .rn-view-return { display: none; } }
    </style>
@endif
