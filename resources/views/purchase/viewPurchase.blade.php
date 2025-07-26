@extends('include') @section('backTitle') purchase view @endsection @section('container')

<div class="card">
    <div class="card-header text-center"><h4>View Purchase</h4></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h5>Customer Details</h5>
                <hr />
                <p><strong>Name:</strong> {{ $customer->name }}</p>
                <p><strong>Mobile:</strong> {{ $customer->mobile }}</p>
                <p><strong>Address:</strong> {{ $customer->city }},{{ $customer->area }}</p>
            </div>
            <div class="col-md-4 mt-5">
                <p class="mt-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->date)->format('d-m-Y') }}</p>
                <p><strong>Reference:</strong> {{ $invoice->reference }}</p>
                <p><strong>Note:</strong>{{ $invoice->note }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="card">
                    <div class="card-body">
                        <h6>Sale Summary</h6>
                        <div class="row">
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Total:</label><input disabled="" class="form-control form-control-sm" type="text" value="{{ $invoice->totalSale }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Paid Amount:</label><input disabled="" class="form-control form-control-sm" type="text" value="{{ $invoice->paidAmount }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Due Amount:</label><input disabled="" class="form-control form-control-sm" type="text" value="{{ $invoice->curDue }}" style="width: 50%;" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row  product-table">
            <div class="col-md-12">
                <h4>Products for Return</h4>
                <table class="table mb-0 table-bordered rounded-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Sale Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Return Qty</th>
                            <th>Return Amount</th>
                            <th>Serial Nos (if any)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($items)
                        @php
                        $sl = 1;
                        @endphp
                        @foreach($items as $item)
                        <tr class="product-row">
                            <td>{{ $sl }}</td>
                            <td>{{ $item->productName }}</td>
                            <td>{{ $item->qty }}</td>
                            <td>{{ $item->salePrice }}</td>
                            <td>{{ number_format($item->totalSale ?? 0, 2, '.', ',') }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        @php
                        $sl++;
                        @endphp
                        @endforeach
                        @else
                        <tr>
                            <td>2</td>
                            <td>Crime Chake</td>
                            <td>20</td>
                            <td>10</td>
                            <td>200</td>
                            <td><input type="checkbox" /></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm" value="" /></td>
                            <td><input type="number" class="form-control form-control-sm" value="0" /></td>
                            <td></td>
                        </tr>
        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@include('customScript')
