@extends('include') @section('backTitle') purchase return @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form class="card form" action="{{ route('purchaseReturnSave') }}" method="POST">
    @csrf
    <input type="hidden" name="supplierId" value="{{ $supplier->id ?? '' }}">
    <input type="hidden" name="purchaseId" value="{{ $purchase->id ?? '' }}">
    <input type="hidden" name="productId" value="{{ $product->id ?? '' }}">
    <div class="card-header text-center" style="color: #c20c0cff;">
        <h4>Purchase Return</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h5>Supplier Details</h5>
                <hr />
                <p><strong>Name:</strong> {{ $supplier->name ?? 'N/A' }}</p>
                <p><strong>Mobile:</strong> {{ $supplier->mobile ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $supplier->mail ?? 'N/A' }}</p>
                <p><strong>Location:</strong> {{ $supplier->city ?? 'N/A' }}@if($supplier->area), {{ $supplier->area }}@endif</p>
            </div>
            <div class="col-md-4 mt-5">
                <p class="mt-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($purchase->purchase_date ?? now())->format('d-m-Y') }}</p>
                <p><strong>Invoice:</strong> {{ $purchase->invoice ?? 'N/A' }}</p>
                <p><strong>Reference:</strong> {{ $purchase->reference ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="card">
                    <div class="card-body">
                        <h6>Purchase Summary</h6>
                        <div class="row">
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Total:</label><input disabled="" class="form-control form-control-sm" type="text" value="{{ $purchase->totalAmount ?? 0 }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Paid Amount:</label><input disabled="" class="form-control form-control-sm" type="text" value="{{ $purchase->paidAmount ?? 0 }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Due Amount:</label><input disabled="" class="form-control form-control-sm" id="dueAmount" type="text" value="{{ $purchase->dueAmount ?? 0 }}" style="width: 50%;" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row product-table">
            <div class="col-md-12">
                <h4>Products for Return</h4>
                <table class="table mb-0 table-bordered rounded-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Purchase Quantity</th>
                            <th>Current Stock</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Return Qty</th>
                            <th>Return Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($product && $stock)
                        <tr class="product-row">
                            <td>1</td>
                            <td>{{ $product->name }}</td>
                            <td><input type="number" id="purchaseQty" class="form-control form-control-sm" value="{{ $purchase->qty ?? 0 }}" readonly /></td>
                            <td><input type="number" id="currentStock" class="form-control form-control-sm" value="{{ $stock->currentStock ?? 0 }}" readonly /></td>
                            <td><input type="number" step="0.01" id="buyPrice" class="form-control form-control-sm price" value="{{ $purchase->buyPrice ?? 0 }}" readonly /></td>
                            <td>{{ number_format(($purchase->buyPrice ?? 0) * ($purchase->qty ?? 0), 2, '.', ',') }}</td>
                            <td><input type="number" name="returnQty" id="returnQty" class="form-control form-control-sm quantity" onkeyup="calculateReturnAmount()" value="" max="{{ $stock->currentStock ?? 0 }}" min="1" step="1" /></td>
                            <td><input type="number" class="form-control form-control-sm" value="0" id="returnAmount" readonly /></td>
                        </tr>
                        @else
                        <tr>
                            <td colspan="8" class="text-center">No product data available</td>
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
                    <input type="number" id="grandTotal" name="totalReturnAmount" class="form-control" value="0" readonly>
                </div>
            </div>
            <div class="col-6">
                <div class="input-group mb-3">
                    <span class="input-group-text rounded-0 p-0 px-2 bg-light">Adjust Amount</span>
                    <input type="number" name="adjustAmount" id="adjustAmount" class="form-control" value="0">
                </div>
            </div>
        </div>
        
        <div class="row shadow p-3">
            <div class="col-12">
                <h5 class="card-title">Return Details (Optional)</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Return Note:</label>
                        <textarea class="form-control" name="returnNote" placeholder="Enter return note if any" rows="2"></textarea>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="d-flex gap-4 w-100">
                            <button type="submit" class="btn btn-success w-100">Submit Return</button>
                        </div>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="d-flex gap-4 w-100">
                            <button type="button" class="btn btn-info w-100">Return And Refund</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function calculateReturnAmount() {
    const returnQty = document.getElementById('returnQty').value || 0;
    const buyPrice = document.getElementById('buyPrice').value || 0;
    const currentStock = document.getElementById('currentStock').value || 0;
    
    // Ensure return quantity doesn't exceed current stock and is integer
    if (parseInt(returnQty) > parseInt(currentStock)) {
        alert('Return quantity cannot exceed current stock (' + currentStock + ')');
        document.getElementById('returnQty').value = currentStock;
        return;
    }
    
    // Ensure quantity is integer
    if (returnQty % 1 !== 0) {
        alert('Stock quantity must be a whole number');
        document.getElementById('returnQty').value = Math.floor(returnQty);
        return;
    }
    
    const returnAmount = parseInt(returnQty) * parseFloat(buyPrice);
    document.getElementById('returnAmount').value = returnAmount.toFixed(2);
    document.getElementById('grandTotal').value = returnAmount.toFixed(2);
}
</script>
@endsection
@include('customScript')
