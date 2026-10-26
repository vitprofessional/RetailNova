@php
    $ledgerPaper = $paper ?? ($receipt['paper'] ?? 'thermal80');
    $logo = $business?->invoiceLogoUrl();
@endphp
<article class="rn-print-document rn-print-document--{{ $ledgerPaper }} rn-print-document--ledger" data-rn-print-document data-paper="{{ $ledgerPaper }}"
         data-qr-payload="{{ $receipt['document_label'].' '.$receipt['document_number'].' | '.$receipt['current_balance'] }}">
    @include('documents.partials.brand', ['business' => $business, 'logo' => $logo])
    <header class="rn-print-document__title">
        <h1>{{ $receipt['document_label'] }}</h1>
    </header>
    <p class="rn-print-document__ledger-contact">{{ $receipt['party_name'] }}</p>
    <table class="rn-print-document__ledger">
        <thead><tr><th>{{ __('common.date') }}</th><th>{{ __('common.description') }}</th><th>{{ __('documents.sale_purchase') }}</th><th>{{ __('documents.deposit') }}</th><th>{{ __('documents.balance') }}</th></tr></thead>
        <tbody>
        @foreach($receipt['ledger_rows'] as $row)
            <tr><td data-label="{{ __('common.date') }}">{{ $row['date'] }}</td><td data-label="{{ __('common.description') }}">{{ $row['description'] }}</td><td data-label="{{ __('documents.sale_purchase') }}">{{ $row['sale_purchase'] }}</td><td data-label="{{ __('documents.deposit') }}">{{ $row['deposit'] }}</td><td data-label="{{ __('documents.balance') }}">{{ $row['balance'] }}</td></tr>
        @endforeach
        @if(empty($receipt['ledger_rows']))<tr><td colspan="5">{{ __('invoices.no_items_found') }}</td></tr>@endif
        </tbody>
    </table>
    <p class="rn-print-document__current-balance"><strong>{{ __('documents.current_balance') }}</strong><b>{{ $receipt['current_balance'] }}</b></p>
</article>
