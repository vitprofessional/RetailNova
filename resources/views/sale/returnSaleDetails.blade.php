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
        @mobile
        <div class="rn-m-detail-summary"><span class="rn-m-detail-summary__status">{{ __('sales.sale_return_details') }}</span><strong>{{ \App\Support\LocalizedNumber::money($returnRecord->totalReturnAmount ?? 0) }}</strong><small>{{ __('common.invoice') }} · {{ $invoice->invoice }}</small></div>
        @endmobile
        <div class="row mb-4">
            <div class="col-md-4">
                <h5>{{ __('sales.customer_details') }}</h5>
                <hr />
                <p><strong>{{ __('common.name') }}:</strong> {{ \App\Models\Customer::displayLabel($customer, $invoice->customerId ?? null, $invoice->businessId ?? null) }}</p>
                @if(!empty($customer->mobile))<p><strong>{{ __('common.mobile') }}:</strong> {{ $customer->mobile }}</p>@endif
                @php $customerAddress = trim(trim((string)($customer->city ?? '')).', '.trim((string)($customer->area ?? '')), ' ,'); @endphp
                @if($customerAddress !== '')<p><strong>{{ __('common.address') }}:</strong> {{ $customerAddress }}</p>@endif
            </div>
            <div class="col-md-4 mt-5">
                <p class="mt-1"><strong>{{ __('common.date') }}:</strong> @mobile{{ \App\Support\MobileDateTime::date($returnRecord->created_at) }}@else{{ \Carbon\Carbon::parse($returnRecord->created_at)->format('d-m-Y') }}@endmobile</p>
                @if(!empty($invoice->reference))<p><strong>{{ __('common.reference') }}:</strong> {{ $invoice->reference }}</p>@endif
                @if(!empty($returnRecord->returnNote))<p><strong>{{ __('sales.return_note') }}:</strong> {{ $returnRecord->returnNote }}</p>@endif
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

        <div class="row product-table rn-m-detail-page">
            <div class="col-md-12">
                <h4>{{ __('sales.returned_products') }}</h4>
                <table class="table mb-0 table-bordered rounded-0 rn-table-pro rn-m-detail-lines">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('sales.name') }}</th>
                            <th>{{ __('sales.returned_qty') }}</th>
                            <th>{{ __('sales.return_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($items && count($items) > 0)
                            @php $sl = 1; @endphp
                            @foreach($items as $item)
                                <tr>
                                    <td class="rn-m-detail-index">{{ $sl }}</td>
                                    <td data-label="{{ __('sales.name') }}">{{ $item->productName }}</td>
                                    <td data-label="{{ __('sales.returned_qty') }}">{{ $item->qty }}</td>
                                    <td data-label="{{ __('sales.return_amount') }}">{{ number_format(0, 2, '.', ',') }}</td>
                                </tr>
                                @php $sl++; @endphp
                            @endforeach
                        @else
                            <tr>
                                <td colspan="4" class="text-center">{{ __('sales.no_returned_items_found') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
