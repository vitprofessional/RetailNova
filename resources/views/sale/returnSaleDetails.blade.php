@extends('include')
@section('backTitle'){{ __('common.heading_sale_return_details') }}@endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>

<div class="card">
    <div class="card-header text-center" style="color: #c20c0cff;">
        <h4>{{ __('sales.sale_return_details') }}</h4>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4">
                <h5>{{ __('sales.customer_details') }}</h5>
                <hr />
                <p><strong>{{ __('common.name') }}:</strong> {{ $customer->name ?? '-' }}</p>
                <p><strong>{{ __('common.mobile') }}:</strong> {{ $customer->mobile ?? '-' }}</p>
                <p><strong>{{ __('common.address') }}:</strong> {{ trim(($customer->city ?? '') . ',' . ($customer->area ?? '')) ?: '-' }}</p>
            </div>
            <div class="col-md-4 mt-5">
                <p class="mt-1"><strong>{{ __('common.date') }}:</strong> {{ \Carbon\Carbon::parse($returnRecord->created_at)->format('d-m-Y') }}</p>
                <p><strong>{{ __('common.reference') }}:</strong> {{ $invoice->reference ?? '-' }}</p>
                <p><strong>{{ __('sales.return_note') }}:</strong> {{ $returnRecord->returnNote ?? '-' }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="card">
                    <div class="card-body">
                        <h6>{{ __('sales.return_summary') }}</h6>
                        <div class="row">
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('common.invoice') }}:</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ $invoice->invoice ?? '-' }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('sales.return_amount') }}:</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ $returnRecord->totalReturnAmount ?? 0 }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('sales.adjust_amount') }}:</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ $returnRecord->adjustAmount ?? 0 }}" style="width: 50%;" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row product-table">
            <div class="col-md-12">
                <h4>{{ __('sales.returned_products') }}</h4>
                <table class="table mb-0 table-bordered rounded-0 rn-table-pro">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('sales.name') }}</th>
                            <th>{{ __('sales.returned_qty') }}</th>
                            <th>{{ __('sales.return_amount') }}</th>
                            <th>{{ __('sales.purchase_id') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($items && count($items) > 0)
                            @php $sl = 1; @endphp
                            @foreach($items as $item)
                                <tr>
                                    <td>{{ $sl }}</td>
                                    <td>{{ $item->productName }}</td>
                                    <td>{{ $item->qty }}</td>
                                    <td>{{ number_format(0, 2, '.', ',') }}</td>
                                    <td>{{ $item->purchaseId }}</td>
                                </tr>
                                @php $sl++; @endphp
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="text-center">{{ __('sales.no_returned_items_found') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
