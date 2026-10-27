<header class="rn-print-document__brand">
    @if($logo)<img src="{{ $logo }}" alt="" class="rn-print-document__logo">@endif
    <div class="rn-print-document__brand-copy">
        <strong data-latin-ok>{{ $business?->businessName ?: config('app.name') }}</strong>
        @if($business?->businessLocation)<span>{{ $business->businessLocation }}</span>@endif
        @if($business?->mobile)<span data-latin-ok>{{ __('common.phone') }}: {{ $business->mobile }}</span>@endif
        @if($business?->email)<span data-latin-ok>{{ __('common.email') }}: {{ $business->email }}</span>@endif
    </div>
    <div class="rn-print-document__qr" data-document-qr aria-label="{{ __('mobile.document_qr') }}"></div>
</header>
