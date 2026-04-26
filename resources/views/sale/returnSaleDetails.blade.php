@extends('include')
@section('backTitle') sale return details @endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>

<div class="card">
    <div class="card-header text-center" style="color: #c20c0cff;">
        <h4>Sale Return Details</h4>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4">
                <h5>Customer Details</h5>
                <hr />
                <p><strong>Name:</strong> {{ $customer->name ?? '-' }}</p>
                <p><strong>Mobile:</strong> {{ $customer->mobile ?? '-' }}</p>
                <p><strong>Address:</strong> {{ trim(($customer->city ?? '') . ',' . ($customer->area ?? '')) ?: '-' }}</p>
            </div>
            <div class="col-md-4 mt-5">
                <p class="mt-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($returnRecord->created_at)->format('d-m-Y') }}</p>
                <p><strong>Reference:</strong> {{ $invoice->reference ?? '-' }}</p>
                <p><strong>Return Note:</strong> {{ $returnRecord->returnNote ?? '-' }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="card">
                    <div class="card-body">
                        <h6>Return Summary</h6>
                        <div class="row">
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Invoice:</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ $invoice->invoice ?? '-' }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Return Amount:</label>
                                    <input disabled class="form-control form-control-sm" type="text" value="{{ $returnRecord->totalReturnAmount ?? 0 }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Adjust Amount:</label>
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
                <h4>Returned Products</h4>
                <table class="table mb-0 table-bordered rounded-0 rn-table-pro">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Returned Qty</th>
                            <th>Return Amount</th>
                            <th>Purchase ID</th>
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
                                <td colspan="5" class="text-center">No returned items found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
