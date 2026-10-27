@php
    $flashError = session('error') ?: session('business_context_warning');
    $flashWarning = session('warning');
    $flashSuccess = session('success') ?: session('status');
    $alertConfig = json_decode((string) session('alert.config', ''), true) ?: [];
    $alertType = $alertConfig['icon'] ?? 'info';
    $alertText = trim(collect([$alertConfig['text'] ?? null, $alertConfig['title'] ?? null])->filter()->implode(' — '));
    $errorMessages = $errors->getMessages();
    $bannerMessage = $errors->first() ?: $flashError ?: ($alertType === 'error' ? $alertText : null);
    $toastMessage = $flashSuccess ?: ($flashWarning ?: (($alertType === 'success' || $alertType === 'warning' || $alertType === 'info') ? $alertText : null));
    $deleteConfig = json_decode((string) session('alert.delete', ''), true) ?: [];
@endphp
<div class="rn-m-feedback" data-rn-feedback
     data-label-confirm="{{ __('common.confirm') }}"
     data-label-cancel="{{ __('common.cancel') }}"
     data-label-delete="{{ __('common.delete') }}"
     data-label-saving="{{ __('mobile.saving') }}"
     data-label-error="{{ __('mobile.save_retry') }}">
    @if($bannerMessage || $errors->any())
        <section class="rn-m-error-banner" data-rn-error-banner role="alert" aria-live="assertive">
            <span data-rn-error-message>{{ $bannerMessage ?: __('mobile.save_retry') }}</span>
            <button type="button" data-rn-error-close aria-label="{{ __('common.close') }}">×</button>
        </section>
    @else
        <section class="rn-m-error-banner" data-rn-error-banner role="alert" aria-live="assertive" hidden>
            <span data-rn-error-message></span>
            <button type="button" data-rn-error-close aria-label="{{ __('common.close') }}">×</button>
        </section>
    @endif
    <div class="rn-m-toast" data-rn-toast role="status" aria-live="polite" @unless($toastMessage) hidden @endunless>
        <span class="rn-m-toast-icon" aria-hidden="true">✓</span><span data-rn-toast-message>{{ $toastMessage }}</span><button type="button" data-rn-toast-undo hidden>{{ __('common.restore') }}</button>
    </div>
    <script type="application/json" data-rn-error-map>{!! json_encode($errorMessages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    @if($deleteConfig)
        <script type="application/json" data-rn-delete-config>{!! json_encode($deleteConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    @endif
</div>
