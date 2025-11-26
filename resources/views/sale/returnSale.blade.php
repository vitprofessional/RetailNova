@extends('include') @section('backTitle') sale return @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form class="card form" action="{{ route('saleReturnSave') }}" method="POST">
    @csrf
    <input type="hidden" name="customerId" value="{{ $customer->id }}">
    <input type="hidden" name="invoiceId" value="{{ $invoice->invoice }}">
    <div class="card-header text-center" style="color: #c20c0cff;">
        <h4>Sale Return</h4>
    </div>
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
                                    <label class="form-label" style="margin-bottom: 0px;">Due Amount:</label><input disabled="" class="form-control form-control-sm" id="dueAmount" type="text" value="{{ $invoice->curDue }}" style="width: 50%;" />
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
                            <th>Select</th>
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
                        
                        <input type="hidden" name="productId[]" value="{{ $item->productId }}">
                        <input type="hidden" name="purchaseId[]" value="{{ $item->purchaseId   }}">
                        <input type="hidden" name="saleId[]" value="{{ $item->saleId }}">
                        <tr class="product-row">
                            <td>{{ $sl }}</td>
                            <td>{{ $item->productName }}</td>
                            <td><input type="number" id="avlQty{{$sl}}" class="form-control form-control-sm" value="{{ $item->qty }}" readonly /></td>
                            <td><input type="number" step="0.01" id="salePrice{{$sl}}" class="form-control form-control-sm price" value="{{ $item->salePrice }}" readonly /></td>
                            <td>{{ number_format($item->totalSale ?? 0, 2, '.', ',') }}</td>
                            <td><input type="checkbox" /></td>
                            <td><input type="number" name="totalQty[]" id="rtnqty{{$sl}}" class="form-control form-control-sm quantity" data-onkeyup="returnQtyCalculate('avlQty{{$sl}}','rtnqty{{$sl}}','salePrice{{$sl}}','returnAmount{{$sl}}')" value="" min="0" step="1" /></td>
                            <td><input type="number" class="form-control form-control-sm" value="0" id="returnAmount{{$sl}}" /></td>
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
                            <td><input type="number" class="form-control form-control-sm" value="" /></td>
                            <td><input type="number" class="form-control form-control-sm" value="0" /></td>
                            <td></td>
                        </tr>
        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <div class="input-group mb-3">
                    <span class="input-group-text rounded-0 p-0 px-2 bg-light">Total Return:</span>
                    <input type="number" id="grandTotal" name="returnAmount" class="form-control" value="0" readonly name="grandTotal">
                </div>
            </div>
            <div class="col-6">
                <div class="input-group mb-3">
                    <span class="input-group-text rounded-0 p-0 px-2 bg-light">Adjust Amount</span>
                    <input type="number" name="adjustAmount" id="adjustAmount" data-onchange="adjustDue('grandTotal','adjustAmount')" class="form-control" value="0" @if($invoice->curDue == 0) readonly @endif>
                </div>
            </div>
            
        </div>
        
        <div class="row shadow p-3">
            <div class="col-12">
                <h5 class="card-title">Return Details(Optional)</h5>
                <form>
                    <div class="row">
                        <div class="col-md-6 mb-3"><textarea class="form-control" placeholder="Enter return note if any" rows="2"></textarea>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="d-flex gap-4 w-100">
                            <button type="submit" class="btn btn-success w-100">Submit Return</button>
                        </div>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="d-flex gap-4 w-100">
                            <button type="button" class="btn btn-success w-100">Return And Refund</button>
                        </div>
                    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
    @include('customScript')
@endsection
