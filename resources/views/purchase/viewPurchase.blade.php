@extends('include') @section('backTitle') Purchase History @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Purchase History</h4>
        <div>
            <a href="{{ route('purchaseList') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line"></i> Back to List
            </a>
            <button type="button" class="btn btn-info" onclick="printPurchase()">
                <i class="ri-printer-line"></i> Print
            </button>
            <a href="{{ route('returnPurchase', ['id' => $purchaseId]) }}" class="btn btn-danger">
                <i class="ri-arrow-go-back-line"></i> Return Purchase
            </a>
        </div>
    </div>
    <div id="rn-purchase-view-root" class="card-body">
        @if($purchase)
        <div class="row">
            <div class="col-md-4">
                <h5>Supplier Details</h5>
                <hr />
                <p><strong>Name:</strong> {{ $purchase->supplierName ?? 'N/A' }}</p>
                <p><strong>Mobile:</strong> {{ $purchase->supplierMobile ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $purchase->supplierEmail ?? 'N/A' }}</p>
                <p><strong>Country:</strong> {{ $purchase->supplierCountry ?? 'N/A' }}</p>
                <p><strong>State:</strong> {{ $purchase->supplierState ?? 'N/A' }}</p>
                <p><strong>Location:</strong> {{ $purchase->supplierCity ?? 'N/A' }}@if($purchase->supplierArea), {{ $purchase->supplierArea }}@endif</p>
            </div>
            <div class="col-md-4">
                <h5>Purchase Details</h5>
                <hr />
                <p><strong>Invoice:</strong> {{ $purchase->invoice ?? 'N/A' }}</p>
                <p><strong>Purchase Date:</strong> {{ \Carbon\Carbon::parse($purchase->purchase_date ?? now())->format('d-m-Y') }}</p>
                <p><strong>Reference:</strong> {{ $purchase->reference ?? 'N/A' }}</p>
                <p><strong>VAT Status:</strong> {{ $purchase->vatStatus == 1 ? 'Yes' : 'No' }}</p>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>Purchase Summary</h6>
                        @php
                            $returnedQty = \App\Models\ReturnPurchaseItem::where('purchaseId', $purchaseId)->sum('qty');
                            $returnedAmount = \App\Models\PurchaseReturn::where('purchaseId', $purchaseId)->sum('totalReturnAmount');
                            $adjustedGrand = max(0, ($purchase->grandTotal ?? 0) - $returnedAmount);
                        @endphp
                        <div class="row">
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Total Amount:</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ number_format($purchase->totalAmount ?? 0, 2) }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Grand Total (Adjusted):</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ number_format($adjustedGrand, 2) }}" style="width: 50%;" />
                                    @if($returnedAmount > 0)
                                        <span class="badge bg-warning" title="Returned">-{{ number_format($returnedAmount, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Paid Amount:</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ number_format($purchase->paidAmount ?? 0, 2) }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Due Amount:</label>
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
                <h5>Product Details</h5>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Bar Code</th>
                                        <th>Quantity</th>
                                        <th>Buy Price (Unit)</th>
                                        <th>Sale Price (Ex VAT)</th>
                                        <th>Sale Price (Inc VAT)</th>
                                        <th>Current Stock</th>
                                        <th>Product Details</th>
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
                <h5>Return History</h5>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Return Date</th>
                                        <th>Return Quantity</th>
                                        <th>Return Amount</th>
                                        <th>Adjust Amount</th>
                                        <th>Status</th>
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
                                            <span class="badge bg-info">Returned</span>
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
            <h4 class="alert-heading">No Purchase Found!</h4>
            <p>The requested purchase record could not be found.</p>
            <hr>
            <p class="mb-0">Please check the purchase ID and try again.</p>
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
                if(!root){ alert('Nothing to print'); return; }

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
                if(!w){ alert('Please allow popups to print'); return; }

                var doc = w.document.open();
                var title = document.title || 'Purchase';
                var html = '<!doctype html><html><head><meta charset="utf-8"><title>'+title+'</title>'+stylesHtml+'<style>@page{margin:10mm;} body{margin:0;padding:8px; -webkit-print-color-adjust:exact;} .table{width:100%;}</style></head><body>' + card.outerHTML + '</body></html>';
                doc.write(html);
                doc.close();
                w.focus();
                setTimeout(function(){ try{ w.print(); w.close(); }catch(e){ console.warn('print failed', e); } }, 600);
            }catch(e){ console.warn('printPurchase error', e); alert('Unable to print'); }
        }
    </script>
@endsection
@endsection
