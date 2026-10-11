@extends('include') @section('backTitle'){{ __('common.heading_purchase_history') }}@endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>{{ __('purchases.purchase_history') }}</h4>
        <div>
            <a href="{{ route('purchaseList') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line"></i> {{ __('purchases.back_to_list') }}
            </a>
            <button type="button" class="btn btn-info" onclick="printPurchase()">
                <i class="ri-printer-line"></i> {{ __('purchases.print') }}
            </button>
            <a href="{{ route('returnPurchase', ['id' => $purchaseId]) }}" class="btn btn-danger">
                <i class="ri-arrow-go-back-line"></i> {{ __('purchases.return_purchase') }}
            </a>
        </div>
    </div>
    <div id="rn-purchase-view-root" class="card-body">
        @if($purchase)
        <div class="row">
            <div class="col-md-4">
                <h5>{{ __('purchases.supplier_details') }}</h5>
                <hr />
                <p><strong>{{ __('common.name') }}:</strong> {{ $purchase->supplierName ?? 'N/A' }}</p>
                <p><strong>{{ __('common.mobile') }}:</strong> {{ $purchase->supplierMobile ?? 'N/A' }}</p>
                <p><strong>{{ __('common.email') }}:</strong> {{ $purchase->supplierEmail ?? 'N/A' }}</p>
                <p><strong>{{ __('purchases.country') }}:</strong> {{ $purchase->supplierCountry ?? 'N/A' }}</p>
                <p><strong>{{ __('purchases.state') }}:</strong> {{ $purchase->supplierState ?? 'N/A' }}</p>
                <p><strong>{{ __('purchases.location') }}:</strong> {{ $purchase->supplierCity ?? 'N/A' }}@if($purchase->supplierArea), {{ $purchase->supplierArea }}@endif</p>
            </div>
            <div class="col-md-4">
                <h5>{{ __('purchases.purchase_details') }}</h5>
                <hr />
                <p><strong>{{ __('common.invoice') }}:</strong> {{ $purchase->invoice ?? 'N/A' }}</p>
                <p><strong>{{ __('purchases.purchase_date') }}:</strong> {{ \Carbon\Carbon::parse($purchase->purchase_date ?? now())->format('d-m-Y') }}</p>
                <p><strong>{{ __('common.reference') }}:</strong> {{ $purchase->reference ?? 'N/A' }}</p>
                <p><strong>{{ __('purchases.vat_status') }}:</strong> {{ $purchase->vatStatus == 1 ? 'Yes' : 'No' }}</p>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>{{ __('purchases.purchase_summary') }}</h6>
                        @php
                            $returnedQty = \App\Models\ReturnPurchaseItem::where('purchaseId', $purchaseId)->sum('qty');
                            $returnedAmount = \App\Models\PurchaseReturn::where('purchaseId', $purchaseId)->sum('totalReturnAmount');
                            $adjustedGrand = max(0, ($purchase->grandTotal ?? 0) - $returnedAmount);
                        @endphp
                        <div class="row">
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('purchases.total_amount') }}:</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ number_format($purchase->totalAmount ?? 0, 2) }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('purchases.grand_total_adjusted') }}:</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ number_format($adjustedGrand, 2) }}" style="width: 50%;" />
                                    @if($returnedAmount > 0)
                                        <span class="badge bg-warning" title="{{ __('purchases.returned') }}">-{{ number_format($returnedAmount, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('common.paid_amount') }}:</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ number_format($purchase->paidAmount ?? 0, 2) }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('common.due_amount') }}:</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ number_format($purchase->dueAmount ?? 0, 2) }}" style="width: 50%;" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <h5>{{ __('purchases.product_details') }}</h5>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered rn-table-pro">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('purchases.product_name') }}</th>
                                        <th>{{ __('purchases.bar_code') }}</th>
                                        <th>{{ __('purchases.quantity') }}</th>
                                        <th>{{ __('purchases.buy_price_unit') }}</th>
                                        <th>{{ __('purchases.sale_price_ex_vat') }}</th>
                                        <th>{{ __('purchases.sale_price_inc_vat') }}</th>
                                        <th>{{ __('purchases.current_stock') }}</th>
                                        <th>{{ __('purchases.product_details') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ $purchase->productName ?? 'N/A' }}</td>
                                        <td>{{ $purchase->productBarCode ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ $purchase->qty ?? 0 }}</span>
                                        </td>
                                        <td>{{ number_format($purchase->buyPrice ?? 0, 2) }}</td>
                                        <td>{{ number_format($purchase->salePriceExVat ?? 0, 2) }}</td>
                                        <td>{{ number_format($purchase->salePriceInVat ?? 0, 2) }}</td>
                                        <td>
                                            @if($stock)
                                                <span class="badge {{ $stock->currentStock > 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $stock->currentStock ?? 0 }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">0</span>
                                            @endif
                                        </td>
                                        <td>{{ $purchase->productDetails ?? 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($returns && $returns->count() > 0)
        <div class="row mt-4">
            <div class="col-md-12">
                <h5>{{ __('purchases.return_history') }}</h5>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered rn-table-pro">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('purchases.return_date') }}</th>
                                        <th>{{ __('purchases.return_quantity') }}</th>
                                        <th>{{ __('purchases.return_amount') }}</th>
                                        <th>{{ __('purchases.adjust_amount') }}</th>
                                        <th>{{ __('purchases.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($returns as $return)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($return->created_at)->format('d-m-Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-warning">{{ $return->returnQty ?? 0 }}</span>
                                        </td>
                                        <td>{{ number_format($return->totalReturnAmount ?? 0, 2) }}</td>
                                        <td>{{ number_format($return->adjustAmount ?? 0, 2) }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ __('purchases.returned') }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @else
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading">{{ __('purchases.no_purchase_found') }}</h4>
            <p>{{ __('purchases.the_requested_purchase_record_could_not_be_found') }}</p>
            <hr>
            <p class="mb-0">{{ __('purchases.please_check_the_purchase_id_and_try_again') }}</p>
        </div>
        @endif
    </div>
</div>

<style>
@media print {
    .btn, .card-header .d-flex > div {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header {
        background: white !important;
        border: none !important;
        text-align: center !important;
    }
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    .badge {
        color: #000 !important;
        background-color: #f8f9fa !important;
        border: 1px solid #000 !important;
    }
}
</style>
@section('scripts')
    @parent
    <script>
        function printPurchase(){
            try{
                var root = document.getElementById('rn-purchase-view-root');
                if(!root){ alert(window.RNTranslate('common.client_nothing_to_print')); return; }

                // Prefer printing the entire card (header + body) so printed output matches the view
                var card = root.closest('.card') || root;

                var headNodes = document.querySelectorAll('link[rel="stylesheet"], style');
                var stylesHtml = '';
                headNodes.forEach(function(n){
                    if(n.tagName === 'LINK'){
                        try{ stylesHtml += '<link rel="stylesheet" href="'+n.href+'">'; }catch(e){}
                    }else{
                        stylesHtml += '<style>'+n.innerHTML+'</style>';
                    }
                });

                var w = window.open('', '_blank');
                if(!w){ alert(window.RNTranslate('common.client_please_allow_popups_to_print')); return; }

                var doc = w.document.open();
                var title = document.title || 'Purchase';
                var html = '<!doctype html><html><head><meta charset="utf-8"><title>'+title+'</title>'+stylesHtml+'<style>@page{margin:10mm;} body{margin:0;padding:8px; -webkit-print-color-adjust:exact;} .table{width:100%;}</style></head><body>' + card.outerHTML + '</body></html>';
                doc.write(html);
                doc.close();
                w.focus();
                setTimeout(function(){ try{ w.print(); w.close(); }catch(e){ console.warn('print failed', e); } }, 600);
            }catch(e){ console.warn('printPurchase error', e); alert(window.RNTranslate('common.client_unable_to_print')); }
        }
    </script>
@endsection
@endsection
