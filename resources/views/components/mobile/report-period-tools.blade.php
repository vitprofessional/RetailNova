@mobile
<div class="rn-report-period-tools" data-rn-report-period-tools>
    <nav class="rn-report-periods" aria-label="{{ __('reports.period') }}">
        @foreach(['today','week','month','custom'] as $periodKey)
            <button type="button" data-rn-report-period="{{ $periodKey }}" aria-pressed="false">{{ __('reports.period_'.$periodKey) }}</button>
        @endforeach
    </nav>
    <button type="button" class="rn-report-download" data-rn-report-download aria-label="{{ __('reports.download') }}"><i class="ri-download-line" aria-hidden="true"></i></button>
</div>
@endmobile
