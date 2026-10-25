@extends('include')
@section('backTitle'){{ __('purchases.purchase_invoice') }}@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/purchase-invoice.css') }}">
@endpush
@section('container')
<div class="col-12 rn-invoice-shell">
@php $paymentStatus=$invoice->due_amount<=0 ? 'paid' : ($invoice->paid_amount>0 ? 'partial' : 'due'); @endphp
@mobile
@include('partials.mobile-document-receipt', ['receipt' => [
    'status' => __('purchases.payment_status').' · '.__('purchases.'.$paymentStatus),
    'amount' => \App\Support\LocalizedNumber::money($invoice->grand_total), 'document_label' => __('purchases.invoice'), 'document_number' => $invoice->invoice,
    'party_label' => __('purchases.supplier'), 'party_name' => $invoice->supplier?->name ?: __('mobile.suppliers'), 'party_meta' => null,
    'items' => $items->map(fn ($line) => ['title' => $line->product?->name ?: __('common.product').' #'.$line->productName, 'detail' => \App\Support\LocalizedNumber::count($line->qty).' × '.\App\Support\LocalizedNumber::money($line->buyPrice), 'amount' => \App\Support\LocalizedNumber::money($line->totalAmount)]),
    'empty_label' => __('purchases.no_items'),
    'totals' => [
        ['label' => __('common.subtotal'), 'amount' => \App\Support\LocalizedNumber::money($invoice->total_amount)],
        ['label' => __('purchases.discount'), 'amount' => \App\Support\LocalizedNumber::money($invoice->discount_amount)],
        ['label' => __('purchases.grand_total'), 'amount' => \App\Support\LocalizedNumber::money($invoice->grand_total), 'is_total' => true],
        ['label' => __('purchases.paid_amount'), 'amount' => \App\Support\LocalizedNumber::money($invoice->paid_amount)],
        ['label' => __('purchases.due_amount'), 'amount' => \App\Support\LocalizedNumber::money($invoice->due_amount)],
    ],
    'actions' => [
        ['type' => 'print', 'label' => __('common.print'), 'icon' => 'ri-printer-line'],
        ['type' => 'share', 'label' => __('common.share'), 'icon' => 'ri-share-line'],
        ['type' => 'copy', 'label' => __('common.copy'), 'icon' => 'ri-file-copy-line'],
        ['type' => 'pdf', 'label' => __('common.pdf'), 'icon' => 'ri-file-pdf-line'],
    ],
    'primary_url' => route('addPurchase'), 'primary_label' => __('mobile.purchase'),
    'secondary_url' => route('purchaseList'), 'secondary_label' => __('purchases.purchased_items'),
]])
@endmobile
@desktop
<div class="card"><div class="card-body" id="purchaseInvoicePrint">
@include('purchase.invoiceHeader',['editing'=>false])
@if(session('status'))<p class="alert alert-success rn-purchase-no-print" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="alert alert-danger rn-purchase-no-print" role="alert">{{ $errors->first() }}</div>@endif
<section class="rn-invoice-section"><h2>{{ __('purchases.invoice_overview') }}</h2>
<dl class="rn-invoice-overview"><div><dt>{{ __('purchases.invoice') }}</dt><dd>{{ $invoice->invoice }}</dd></div><div><dt>{{ __('purchases.date') }}</dt><dd>@mobile{{ \App\Support\MobileDateTime::date($invoice->purchase_date) }}@else{{ $invoice->purchase_date->format('d M Y') }}@endmobile</dd></div><div><dt>{{ __('purchases.supplier') }}</dt><dd>{{ $invoice->supplier?->name }}</dd></div><div><dt>{{ __('purchases.payment_status') }}</dt><dd><span class="rn-invoice-status rn-invoice-status--{{ $paymentStatus }}">{{ __('purchases.'.$paymentStatus) }}</span></dd></div></dl></section>
<section class="rn-invoice-section"><h2>{{ __('purchases.purchased_items') }}</h2><p class="rn-invoice-help rn-purchase-no-print">{{ __('purchases.invoice_scroll_hint') }}</p>
<div class="table-responsive rn-invoice-table-wrap rn-m-detail-page" tabindex="0" role="region" aria-label="{{ __('purchases.purchased_items') }}"><table class="table rn-m-detail-lines"><thead><tr>
@foreach(['product','quantity','buy_price','sale_vat','total','serials'] as $heading)<th scope="col">{{ __('purchases.'.$heading) }}</th>@endforeach<th scope="col">{{ __('navigation.purchase_returns') }}</th></tr></thead><tbody>
@forelse($items as $line)<tr data-purchase-line="{{ $line->id }}"><td data-label="{{ __('purchases.product') }}">{{ $line->product?->name }}</td><td data-label="{{ __('purchases.quantity') }}" class="rn-invoice-number">{{ $line->qty }}</td><td data-label="{{ __('purchases.buy_price') }}" class="rn-invoice-number">@mobile{{ \App\Support\LocalizedNumber::money($line->buyPrice) }}@else@money($line->buyPrice)@endmobile</td><td data-label="{{ __('purchases.sale_vat') }}" class="rn-invoice-number">{{ $line->vatPercent }}%</td><td data-label="{{ __('purchases.total') }}" class="rn-invoice-number">@mobile{{ \App\Support\LocalizedNumber::money($line->totalAmount) }}@else@money($line->totalAmount)@endmobile</td>
<td data-label="{{ __('purchases.serials') }}">@if($line->serials->isEmpty()){{ __('purchases.no_serials') }}@else<details data-invoice-serials><summary>{{ __('purchases.serials') }}: {{ $line->serials->count() }}</summary>@foreach($line->serials as $serial)<div class="rn-invoice-serial">{{ $serial->serialNumber }} <small>{{ __('purchases.serial_status_'.$serial->status) }}</small></div>@endforeach</details>@endif</td>
<td data-label="{{ __('navigation.purchase_returns') }}">@foreach($line->returns as $return)<div>{{ __('purchases.return_amount') }}: @mobile{{ \App\Support\LocalizedNumber::money($return->totalReturnAmount) }}@else@money($return->totalReturnAmount)@endmobile</div>@endforeach<a class="btn btn-sm btn-outline-secondary rn-purchase-no-print" href="{{ route('returnPurchase',$line->id) }}">{{ __('navigation.purchase_returns') }}</a></td></tr>
@empty<tr><td colspan="7">{{ __('purchases.no_items') }}</td></tr>@endforelse
</tbody></table></div><p class="rn-invoice-help">{{ __('purchases.vat_context') }}</p></section>
<div class="rn-invoice-finance"><section class="rn-invoice-section"><h2>{{ __('purchases.special_note') }}</h2><div class="rn-invoice-note">{{ $invoice->special_note ?: __('purchases.no_notes') }}</div></section>
<section class="rn-invoice-section"><h2>{{ __('purchases.financial_summary') }}</h2><dl class="rn-invoice-summary">
<div><dt>{{ __('common.subtotal') }}</dt><dd>@mobile{{ \App\Support\LocalizedNumber::money($invoice->total_amount) }}@else@money($invoice->total_amount)@endmobile</dd></div><div><dt>{{ __('purchases.discount') }}</dt><dd>@mobile{{ \App\Support\LocalizedNumber::money($invoice->discount_amount) }}@else@money($invoice->discount_amount)@endmobile</dd></div><div class="rn-invoice-grand"><dt>{{ __('purchases.grand_total') }}</dt><dd>@mobile{{ \App\Support\LocalizedNumber::money($invoice->grand_total) }}@else@money($invoice->grand_total)@endmobile</dd></div><div><dt>{{ __('purchases.paid_amount') }}</dt><dd>@mobile{{ \App\Support\LocalizedNumber::money($invoice->paid_amount) }}@else@money($invoice->paid_amount)@endmobile</dd></div><div class="rn-invoice-due"><dt>{{ __('purchases.due_amount') }}</dt><dd>@mobile{{ \App\Support\LocalizedNumber::money($invoice->due_amount) }}@else@money($invoice->due_amount)@endmobile</dd></div></dl></section></div>
<div class="rn-purchase-no-print rn-invoice-footer"><form method="POST" action="{{ route('purchase.invoice.destroy',$invoice->id) }}">@csrf @method('DELETE')<button type="submit" data-confirm="delete" class="btn btn-outline-danger">{{ __('common.delete') }}</button></form></div>
</div></div><script src="{{ asset('js/purchase-invoice.js') }}" defer></script>
@enddesktop
</div>
@endsection
