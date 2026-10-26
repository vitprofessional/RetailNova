@php
    $documentItems = collect($receipt['items'] ?? []);
    $requestedPaper = request()->query('size', request()->query('printer'));
    $paper = in_array($requestedPaper, ['a4', 'thermal80', 'thermal58'], true)
        ? $requestedPaper
        : (in_array($business?->default_invoice_printer, ['a4', 'thermal80', 'thermal58'], true) ? $business->default_invoice_printer : 'thermal80');
    $logo = $business?->invoiceLogoUrl() ?: asset('logo.png');
    $documentDate = $receipt['date'] ?? null;
@endphp
<article class="rn-print-document rn-print-document--{{ $paper }}" data-rn-print-document data-paper="{{ $paper }}"
         data-qr-payload="{{ $receipt['document_label'].' '.$receipt['document_number'].' | '.$receipt['amount'] }}">
    <header class="rn-print-document__brand">
        @if($logo)<img src="{{ $logo }}" alt="" class="rn-print-document__logo">@endif
        <div>
            <strong>{{ $business?->businessName ?: config('app.name') }}</strong>
            @if($business?->businessLocation)<span>{{ $business->businessLocation }}</span>@endif
            @if($business?->mobile)<span>{{ __('common.phone') }}: {{ $business->mobile }}</span>@endif
            @if($business?->email)<span>{{ __('common.email') }}: {{ $business->email }}</span>@endif
        </div>
        <div class="rn-print-document__qr" data-document-qr aria-label="{{ __('mobile.document_qr') }}"></div>
    </header>
    <div class="rn-print-document__title">
        <h1>{{ $receipt['document_label'] }}</h1>
        <span>{{ $receipt['document_number'] }}@if($documentDate) · {{ $documentDate }}@endif</span>
    </div>
    <section class="rn-print-document__party">
        <strong>{{ $receipt['party_label'] }}: {{ $receipt['party_name'] }}</strong>
        @if(!empty($receipt['party_meta']))<span>{{ $receipt['party_meta'] }}</span>@endif
        <span>{{ $receipt['status'] }}</span>
    </section>
    <table class="rn-print-document__items">
        <thead><tr><th>{{ __('common.product') }}</th><th>{{ __('common.details') }}</th><th>{{ __('common.amount') }}</th></tr></thead>
        <tbody>
        @foreach($documentItems as $line)
            <tr><td><strong>{{ $line['title'] }}</strong><small class="rn-print-document__thermal-detail">{{ $line['detail'] ?? '' }}</small></td><td class="rn-print-document__a4-detail">{{ $line['detail'] ?? '' }}</td><td>{{ $line['amount'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <dl class="rn-print-document__totals">
        @foreach($receipt['totals'] as $total)
            <div @if(!empty($total['is_total']))class="is-total"@endif><dt>{{ $total['label'] }}</dt><dd>{{ $total['amount'] }}</dd></div>
        @endforeach
    </dl>
    @if($business?->invoiceFooter)<footer class="rn-print-document__footer">{{ $business->invoiceFooter }}</footer>@else<footer class="rn-print-document__footer">{{ __('common.thank_you_for_your_business') }}</footer>@endif
</article>
