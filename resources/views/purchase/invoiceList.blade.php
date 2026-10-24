@extends('include')
@section('backTitle'){{ __('navigation.purchase_history') }}@endsection
@section('container')
{{-- UI-2: the mobile version is cards (App\Services\MobileLists); the desktop markup below is unchanged. --}}
@mobile
@include('mobile.lists.page', ['list' => app(\App\Services\MobileLists\PurchasesList::class)])
@include('mobile.lists.section', ['list' => app(\App\Services\MobileLists\PurchaseLinesList::class), 'title' => __('purchases.legacy_records')])
@else
<link rel="stylesheet" href="{{ asset('css/purchase-list.css') }}">
<div class="col-12 rn-purchase-shell"><div class="card rn-purchase-list"><div class="card-body">
<div id="purchaseListPrint">
<header class="rn-purchase-header"><div><h4 id="purchaseListTitle">{{ __('navigation.purchase_history') }}</h4><p>{{ trans_choice('purchases.list_summary',$invoices->total(),['count'=>$invoices->total()]) }}</p></div>
<a class="btn btn-primary rn-purchase-no-print" href="{{ route('addPurchase') }}">{{ __('navigation.new_purchase') }}</a></header>
@if(session('status'))<p class="alert alert-success">{{ session('status') }}</p>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="GET" action="{{ route('purchaseList') }}" class="rn-purchase-filters rn-purchase-no-print" aria-label="{{ __('common.search') }}">
<div><label for="purchaseSearch">{{ __('common.search') }}</label><input id="purchaseSearch" name="q" type="search" value="{{ request('q') }}" class="form-control"></div>
<div><label for="purchaseSupplier">{{ __('purchases.supplier') }}</label><select id="purchaseSupplier" name="supplier" class="form-control"><option value="">{{ __('purchases.all_suppliers') }}</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)request('supplier')===(string)$supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div>
<div><label for="purchaseFrom">{{ __('common.from') }}</label><input id="purchaseFrom" type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
<div><label for="purchaseTo">{{ __('common.to') }}</label><input id="purchaseTo" type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
<div><label for="purchaseStatus">{{ __('purchases.payment_status') }}</label><select id="purchaseStatus" name="status" class="form-control"><option value="">{{ __('purchases.all_payment_statuses') }}</option>@foreach(['paid','due','partial'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ __('invoices.status_'.$status) }}</option>@endforeach</select></div>
<div><label for="purchaseSort">{{ __('purchases.sort_by') }}</label><select id="purchaseSort" name="sort" class="form-control">@foreach(['id'=>'sort_created','invoice'=>'invoice_no','purchase_date'=>'date','grand_total'=>'grand_total'] as $value=>$label)<option value="{{ $value }}" @selected(request('sort','id')===$value)>{{ __('purchases.'.$label) }}</option>@endforeach</select></div>
<div><label for="purchaseDirection">{{ __('purchases.sort_direction') }}</label><select id="purchaseDirection" name="direction" class="form-control">@foreach(['desc','asc'] as $direction)<option value="{{ $direction }}" @selected(request('direction','desc')===$direction)>{{ __('purchases.sort_'.$direction) }}</option>@endforeach</select></div>
<div class="rn-purchase-filter-actions"><button class="btn btn-primary" type="submit">{{ __('common.search') }}</button><a class="btn btn-outline-secondary" href="{{ route('purchaseList') }}">{{ __('common.reset') }}</a></div>
</form>
<form id="purchaseInvoiceBulkForm" class="purchase-invoice-bulk-form" method="POST" action="{{ route('purchase.invoice.bulkDelete') }}">@csrf
<div class="rn-purchase-table-toolbar rn-purchase-no-print">
<div class="rn-purchase-selection"><label class="rn-purchase-select-page" for="purchaseSelectPage"><input id="purchaseSelectPage" type="checkbox"><span>{{ __('purchases.select_page') }}</span></label>
<span id="purchaseSelectionCount" role="status" aria-live="polite" data-label="{{ __('common.selected') }}">{{ __('common.selected') }}: 0</span></div>
<div class="rn-purchase-toolbar-actions"><button id="purchaseListPrintButton" type="button" class="btn btn-outline-primary">{{ __('common.print') }}</button>
@auth('admin')<button id="purchaseBulkDeleteButton" type="submit" class="btn btn-outline-danger" data-confirm="delete" disabled>{{ __('purchases.delete_selected_invoices') }}</button>@endauth</div></div>
<p id="purchaseScrollHint" class="rn-purchase-scroll-hint rn-purchase-no-print">{{ __('purchases.scroll_hint') }}</p>
<p id="purchaseMobileHint" class="rn-purchase-mobile-hint rn-purchase-no-print">{{ __('purchases.mobile_list_hint') }}</p>
<div class="table-responsive rn-purchase-table-scroll" tabindex="0" role="region" aria-labelledby="purchaseListTitle" aria-describedby="purchaseScrollHint"><table class="table" id="purchaseInvoiceTable" role="table"><caption class="sr-only">{{ __('navigation.purchase_history') }}</caption><thead role="rowgroup"><tr role="row">
<th scope="col" class="rn-purchase-no-print rn-purchase-select">{{ __('common.select') }}</th><th scope="col">{{ __('purchases.invoice') }}</th><th scope="col">{{ __('purchases.supplier') }}</th><th scope="col">{{ __('purchases.date') }}</th>
<th scope="col">{{ __('purchases.items') }}</th><th scope="col">{{ __('purchases.quantity') }}</th><th scope="col">{{ __('purchases.grand_total') }}</th>
<th scope="col">{{ __('purchases.paid_amount') }}</th><th scope="col">{{ __('purchases.due_amount') }}</th><th scope="col">{{ __('common.status') }}</th><th scope="col" class="rn-purchase-no-print rn-purchase-actions-cell">{{ __('common.actions') }}</th>
</tr></thead><tbody role="rowgroup">
@forelse($invoices as $invoice)
<tr role="row" data-purchase-invoice="{{ $invoice->id }}"><td role="cell" class="rn-purchase-no-print rn-purchase-select"><input aria-label="{{ __('common.select') }} {{ $invoice->invoice }}" type="checkbox" name="ids[]" value="{{ $invoice->id }}"></td>
<td role="cell" data-label="{{ __('purchases.invoice_no') }}" class="rn-purchase-invoice"><a href="{{ route('purchase.invoice.show',$invoice->id) }}">{{ $invoice->invoice }}</a></td><td role="cell" data-label="{{ __('purchases.supplier') }}" class="rn-purchase-supplier">{{ $invoice->supplier?->name }}</td>
<td role="cell" data-label="{{ __('purchases.date') }}" class="rn-purchase-date"><time datetime="{{ $invoice->purchase_date->format('Y-m-d') }}" title="{{ $invoice->purchase_date->format('Y-m-d') }}">{{ $invoice->purchase_date->format('d/m/Y') }}</time></td><td role="cell" data-label="{{ __('purchases.items') }}" class="rn-purchase-count">{{ $invoice->item_count }}</td><td role="cell" data-label="{{ __('purchases.quantity') }}" class="rn-purchase-count">{{ $invoice->quantity_count }}</td>
<td role="cell" data-label="{{ __('purchases.grand_total') }}" class="rn-purchase-money">@money($invoice->grand_total)</td><td role="cell" data-label="{{ __('purchases.paid_amount') }}" class="rn-purchase-money">@money($invoice->paid_amount)</td><td role="cell" data-label="{{ __('purchases.due_amount') }}" class="rn-purchase-money">@money($invoice->due_amount)</td>
@php $paymentStatus=(float)$invoice->due_amount===0.0?'paid':((float)$invoice->paid_amount>0?'partial':'due'); @endphp
<td role="cell" data-label="{{ __('common.status') }}"><span class="rn-purchase-status rn-purchase-status-{{ $paymentStatus }}">{{ __('invoices.status_'.$paymentStatus) }}</span></td>
<td role="cell" class="rn-purchase-no-print rn-purchase-actions-cell"><div class="rn-purchase-row-actions"><a class="btn btn-sm btn-outline-primary" href="{{ route('purchase.invoice.show',$invoice->id) }}">{{ __('common.view') }}</a>
<a class="btn btn-sm btn-outline-secondary" href="{{ route('purchase.invoice.edit',$invoice->id) }}">{{ __('common.edit') }}</a></div></td></tr>
@empty<tr><td colspan="11">{{ __('common.no_data_found') }}</td></tr>@endforelse
</tbody></table></div>
</form><nav class="rn-purchase-pagination rn-purchase-no-print" aria-label="{{ __('navigation.purchase_history') }}">{{ $invoices->links() }}</nav>
@if($legacy->count())
<details id="purchaseLegacyRecords" class="rn-purchase-legacy" @if(request()->has('legacy_page')) open @endif><summary>{{ __('purchases.legacy_records') }} <span class="rn-purchase-legacy-count">{{ $legacy->total() }}</span><small>{{ __('purchases.legacy_compatibility') }}</small></summary><div class="rn-purchase-legacy-body"><p class="alert alert-warning">{{ __('purchases.legacy_identity_required') }}</p>
<div class="table-responsive" tabindex="0" role="region" aria-label="{{ __('purchases.legacy_records') }}"><table class="table rn-purchase-legacy-table"><thead><tr><th scope="col">{{ __('purchases.invoice') }}</th><th scope="col">{{ __('purchases.product') }}</th><th scope="col">{{ __('purchases.supplier') }}</th><th scope="col">{{ __('common.view') }}</th></tr></thead><tbody>
@foreach($legacy as $line)<tr data-legacy-purchase="{{ $line->id }}"><td>{{ $line->invoice }}</td><td>{{ $line->product?->name }}</td><td>{{ $line->getRelation('supplier')?->name }}</td>
<td><a href="{{ route('purchaseView',$line->id) }}">{{ __('common.view') }}</a></td></tr>@endforeach
</tbody></table></div>{{ $legacy->links() }}</div></details>@endif
</div></div></div></div>
@endmobile
@endsection
@section('scripts')
@desktop
<script src="{{ asset('js/purchase-list.js') }}" defer></script>
@enddesktop
@endsection
