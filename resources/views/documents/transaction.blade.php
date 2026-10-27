@php
    $documentItems = collect($receipt['items'] ?? []);
    $requestedPaper = $paper ?? request()->query('size', request()->query('printer'));
    $paper = in_array($requestedPaper, ['a4', 'thermal80', 'thermal58'], true)
        ? $requestedPaper
        : (in_array($business?->default_invoice_printer, ['a4', 'thermal80', 'thermal58'], true) ? $business->default_invoice_printer : 'thermal80');
    $logo = $business?->invoiceLogoUrl();
    $documentDate = $receipt['date'] ?? null;
    $isLedger = !empty($receipt['ledger']);
@endphp
<article class="rn-print-document rn-print-document--{{ $paper }}" data-rn-print-document data-paper="{{ $paper }}"
         data-qr-payload="{{ $receipt['document_label'].' '.$receipt['document_number'].' | '.$receipt['amount'] }}">
    @include('documents.partials.brand', ['business' => $business, 'logo' => $logo])
    <div class="rn-print-document__title">
        <h1>{{ $receipt['document_label'] }}</h1>
        <span data-latin-ok>{{ $receipt['document_number'] }}@if($documentDate) · {{ $documentDate }}@endif</span>
    </div>
    <section class="rn-print-document__party">
        <strong data-latin-ok>{{ $receipt['party_label'] }}: {{ $receipt['party_name'] }}</strong>
        @if(!empty($receipt['party_meta']))<span data-latin-ok>{{ $receipt['party_meta'] }}</span>@endif
        <span>{{ $receipt['status'] }}</span>
    </section>
    <table class="rn-print-document__items">
        <thead><tr><th>{{ __('documents.product') }}</th><th>{{ __('documents.quantity') }}</th><th>{{ __('documents.rate') }}</th><th>{{ __('documents.amount') }}</th></tr></thead>
        <tbody>
        @foreach($documentItems as $line)
            <tr>
                <td><strong data-latin-ok>{{ $line['title'] }}</strong><small class="rn-print-document__thermal-detail" data-latin-ok><span>{{ $line['quantity'] ?? '' }} × {{ $line['rate'] ?? '' }}</span><strong>{{ $line['amount'] ?? '' }}</strong></small>@if(!empty($line['serials']))<small class="rn-print-document__serials" data-latin-ok>{{ __('common.s_n') }}: {{ implode(', ', $line['serials']) }}</small>@endif</td>
                <td class="rn-print-document__numeric">{{ $line['quantity'] ?? '—' }}</td>
                <td class="rn-print-document__money">{{ $line['rate'] ?? '—' }}</td>
                <td class="rn-print-document__money">{{ $line['amount'] ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <dl class="rn-print-document__totals">
        @foreach($receipt['totals'] as $total)
            <div @if(!empty($total['is_total']))class="is-total"@endif><dt>{{ $total['label'] }}</dt><dd>{{ $total['amount'] }}</dd></div>
        @endforeach
    </dl>
    @unless($isLedger)
        @if($business?->invoiceFooter)<footer class="rn-print-document__footer">{{ $business->invoiceFooter }}</footer>@else<footer class="rn-print-document__footer">{{ __('common.thank_you_for_your_business') }}</footer>@endif
    @endunless
</article>
