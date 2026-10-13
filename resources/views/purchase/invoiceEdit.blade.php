@extends('include')
@section('backTitle'){{ __('purchases.edit_invoice') }}@endsection
@section('container')
<link rel="stylesheet" href="{{ asset('css/purchase-invoice.css') }}">
<div class="col-12 rn-invoice-shell"><div class="card"><div class="card-body">
@include('purchase.invoiceHeader',['editing'=>true])
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('purchase.invoice.update',$invoice->id) }}" id="purchaseInvoiceEdit" data-paid="{{ $invoice->paid_amount }}">@csrf
<section class="rn-invoice-section"><h2>{{ __('purchases.invoice_overview') }}</h2><div class="rn-invoice-fields"><div class="rn-invoice-field"><label for="invoiceDate">{{ __('purchases.date') }}</label><input id="invoiceDate" name="purchaseDate" type="date" value="{{ old('purchaseDate',$invoice->purchase_date->format('Y-m-d')) }}" class="form-control mb-3" required>
@error('purchaseDate')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="rn-invoice-field"><label>{{ __('purchases.supplier') }}</label><div>{{ $invoice->supplier?->name }}</div></div></div></section>
<section class="rn-invoice-section"><h2>{{ __('purchases.purchased_items') }}</h2><p class="rn-invoice-help">{{ __('purchases.serials_optional') }}</p><p class="rn-invoice-help">{{ __('purchases.invoice_scroll_hint') }}</p>
<div class="table-responsive rn-invoice-table-wrap" tabindex="0" role="region" aria-label="{{ __('purchases.purchased_items') }}"><table class="table table-bordered" id="purchaseInvoiceEditTable"><thead><tr>
@foreach(['product','quantity','buy_price','sale_price','vat_percent','include_vat','total','serials','remove_item'] as $heading)<th scope="col">{{ __('purchases.'.$heading) }}</th>@endforeach
</tr></thead><tbody id="purchaseInvoiceRows">
@php
    $submitted=old('rows');
    $editRows=is_array($submitted) ? $submitted : $invoice->items->mapWithKeys(fn($item)=>['line'.$item->id=>['id'=>$item->id]])->all();
@endphp
@foreach($editRows as $key=>$submittedRow)
@php $line=$invoice->items->firstWhere('id',$submittedRow['id']??null); @endphp
@include('purchase.invoiceItemFields',['key'=>$key,'line'=>$line])
@endforeach
</tbody></table></div>
<template id="purchaseInvoiceNewRow">@include('purchase.invoiceItemFields',['key'=>'__KEY__','line'=>null])</template>
<button type="button" id="purchaseInvoiceAddRow" class="btn btn-outline-primary mb-3">{{ __('purchases.add_product') }}</button>
</section><div class="rn-invoice-finance"><section class="rn-invoice-section"><h2>{{ __('purchases.discount') }}</h2><div class="rn-invoice-fields"><div class="rn-invoice-field"><label for="discountType">{{ __('purchases.discount') }}</label><select id="discountType" name="discountStatus" class="form-control"><option value="1" @selected(old('discountStatus',$invoice->discount_type)!=='2')>{{ __('purchases.amount') }}</option><option value="2" @selected(old('discountStatus',$invoice->discount_type)==='2')>{{ __('purchases.parcent') }}</option></select>
@error('discountStatus')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div><div class="rn-invoice-field"><label for="invoiceDiscount">{{ __('purchases.discount_amount') }}</label><input id="invoiceDiscount" name="discountAmount" type="number" step="0.01" min="0" value="{{ old('discountAmount',$invoice->discount_amount) }}" class="form-control">
@error('discountAmount')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div><div class="rn-invoice-field"><label for="invoicePercent">{{ __('purchases.discount_percent') }}</label><input id="invoicePercent" name="discountPercent" type="number" step="0.01" min="0" max="100" value="{{ old('discountPercent',$invoice->discount_percent) }}" class="form-control">
@error('discountPercent')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div></div><p class="rn-invoice-help">{{ __('purchases.vat_context') }}</p></section>
<section class="rn-invoice-section"><h2>{{ __('purchases.financial_summary') }}</h2><dl class="rn-invoice-summary"><div><dt>{{ __('common.subtotal') }}</dt><dd><output id="purchaseInvoiceSubtotal"></output></dd></div><div><dt>{{ __('purchases.discount') }}</dt><dd><output id="purchaseInvoiceDiscountPreview"></output></dd></div><div class="rn-invoice-grand"><dt>{{ __('purchases.preview_total') }}</dt><dd><output id="purchaseInvoicePreview"></output></dd></div><div><dt>{{ __('purchases.paid_amount') }}</dt><dd>@money($invoice->paid_amount)</dd></div><div class="rn-invoice-due"><dt>{{ __('purchases.due_amount') }}</dt><dd><output id="purchaseInvoiceDuePreview"></output></dd></div></dl><p class="rn-invoice-help">{{ __('purchases.payment_immutable') }}</p></section></div>
<section class="rn-invoice-section rn-invoice-field"><h2>{{ __('purchases.special_note') }}</h2>
<label for="invoiceNote">{{ __('purchases.special_note') }}</label><textarea id="invoiceNote" name="specialNote" class="form-control">{{ old('specialNote',$invoice->special_note) }}</textarea>@error('specialNote')<div class="invalid-feedback">{{ $message }}</div>@enderror
</section><div class="rn-invoice-footer"><a data-invoice-cancel class="btn btn-outline-secondary" href="{{ route('purchase.invoice.show',$invoice->id) }}">{{ __('common.cancel') }}</a><button id="purchaseInvoiceSaveButton" type="submit" class="btn btn-primary">{{ __('purchases.save_changes') }}</button></div>
</form></div></div></div>
@include('purchase.serialModal')
@include('purchase.invoiceSerialScript')
<script src="{{ asset('js/purchase-invoice.js') }}?v={{ filemtime(public_path('js/purchase-invoice.js')) }}" defer></script>
@endsection
