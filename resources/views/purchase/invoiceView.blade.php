@extends('include')
@section('backTitle'){{ __('purchases.purchase_invoice') }}@endsection
@section('container')
<link rel="stylesheet" href="{{ asset('css/purchase-invoice.css') }}">
<div class="col-12 rn-invoice-shell"><div class="card"><div class="card-body" id="purchaseInvoicePrint">
@include('purchase.invoiceHeader',['editing'=>false])
@if(session('status'))<p class="alert alert-success rn-purchase-no-print" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="alert alert-danger rn-purchase-no-print" role="alert">{{ $errors->first() }}</div>@endif
<section class="rn-invoice-section"><h2>{{ __('purchases.invoice_overview') }}</h2>
@php $paymentStatus=$invoice->due_amount<=0 ? 'paid' : ($invoice->paid_amount>0 ? 'partial' : 'due'); @endphp
<dl class="rn-invoice-overview"><div><dt>{{ __('purchases.invoice') }}</dt><dd>{{ $invoice->invoice }}</dd></div><div><dt>{{ __('purchases.date') }}</dt><dd>{{ $invoice->purchase_date->format('d M Y') }}</dd></div><div><dt>{{ __('purchases.supplier') }}</dt><dd>{{ $invoice->supplier?->name }}</dd></div><div><dt>{{ __('purchases.payment_status') }}</dt><dd><span class="rn-invoice-status rn-invoice-status--{{ $paymentStatus }}">{{ __('purchases.'.$paymentStatus) }}</span></dd></div></dl></section>
<section class="rn-invoice-section"><h2>{{ __('purchases.purchased_items') }}</h2><p class="rn-invoice-help rn-purchase-no-print">{{ __('purchases.invoice_scroll_hint') }}</p>
<div class="table-responsive rn-invoice-table-wrap" tabindex="0" role="region" aria-label="{{ __('purchases.purchased_items') }}"><table class="table"><thead><tr>
@foreach(['product','quantity','buy_price','sale_vat','total','serials'] as $heading)<th scope="col">{{ __('purchases.'.$heading) }}</th>@endforeach<th scope="col">{{ __('navigation.purchase_returns') }}</th></tr></thead><tbody>
@forelse($invoice->items as $line)<tr data-purchase-line="{{ $line->id }}"><td>{{ $line->product?->name }}</td><td class="rn-invoice-number">{{ $line->qty }}</td><td class="rn-invoice-number">@money($line->buyPrice)</td><td class="rn-invoice-number">{{ $line->vatPercent }}%</td><td class="rn-invoice-number">@money($line->totalAmount)</td>
<td>@if($line->serials->isEmpty()){{ __('purchases.no_serials') }}@else<details data-invoice-serials><summary>{{ __('purchases.serials') }}: {{ $line->serials->count() }}</summary>@foreach($line->serials as $serial)<div class="rn-invoice-serial">{{ $serial->serialNumber }} <small>{{ __('purchases.serial_status_'.$serial->status) }}</small></div>@endforeach</details>@endif</td>
<td>@foreach($line->returns as $return)<div>{{ __('purchases.return_amount') }}: @money($return->totalReturnAmount)</div>@endforeach<a class="btn btn-sm btn-outline-secondary rn-purchase-no-print" href="{{ route('returnPurchase',$line->id) }}">{{ __('navigation.purchase_returns') }}</a></td></tr>
@empty<tr><td colspan="7">{{ __('purchases.no_items') }}</td></tr>@endforelse
</tbody></table></div><p class="rn-invoice-help">{{ __('purchases.vat_context') }}</p></section>
<div class="rn-invoice-finance"><section class="rn-invoice-section"><h2>{{ __('purchases.special_note') }}</h2><div class="rn-invoice-note">{{ $invoice->special_note ?: __('purchases.no_notes') }}</div></section>
<section class="rn-invoice-section"><h2>{{ __('purchases.financial_summary') }}</h2><dl class="rn-invoice-summary">
<div><dt>{{ __('common.subtotal') }}</dt><dd>@money($invoice->total_amount)</dd></div><div><dt>{{ __('purchases.discount') }}</dt><dd>@money($invoice->discount_amount)</dd></div><div class="rn-invoice-grand"><dt>{{ __('purchases.grand_total') }}</dt><dd>@money($invoice->grand_total)</dd></div><div><dt>{{ __('purchases.paid_amount') }}</dt><dd>@money($invoice->paid_amount)</dd></div><div class="rn-invoice-due"><dt>{{ __('purchases.due_amount') }}</dt><dd>@money($invoice->due_amount)</dd></div></dl></section></div>
<div class="rn-purchase-no-print rn-invoice-footer"><form method="POST" action="{{ route('purchase.invoice.destroy',$invoice->id) }}">@csrf @method('DELETE')<button type="submit" data-confirm="delete" class="btn btn-outline-danger">{{ __('common.delete') }}</button></form></div>
</div></div></div><script src="{{ asset('js/purchase-invoice.js') }}" defer></script>
@endsection
