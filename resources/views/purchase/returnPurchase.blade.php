@extends('include') @section('backTitle'){{ __('navigation.purchase_returns') }}@endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form class="card form" action="{{ route('purchaseReturnSave') }}" method="POST">
    @csrf
    <input type="hidden" name="supplierId" value="{{ $supplier->id ?? '' }}">
    <input type="hidden" name="purchaseId" value="{{ $purchase->id ?? '' }}">
    <input type="hidden" name="productId" value="{{ $product->id ?? '' }}">
    <div class="card-header text-center" style="color: #c20c0cff;">
        <h4>{{ __('purchases.purchase_return') }}</h4>
    </div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @if($purchase->serials()->exists())<fieldset class="mb-3"><legend>{{ __('purchases.return_serials_required') }}</legend>
        @foreach($purchase->serials as $serial)@if($serial->saleId===null && in_array($serial->status,[null,'available'],true))
        <label class="mr-3"><input type="checkbox" name="serialIds[]" value="{{ $serial->id }}"> {{ $serial->serialNumber }}</label>
        @endif @endforeach</fieldset>@endif
        <div class="row">
            <div class="col-md-4">
                <h5>{{ __('purchases.supplier_details') }}</h5>
                <hr />
                <p><strong>{{ __('common.name') }}:</strong> {{ $supplier->name ?? 'N/A' }}</p>
                <p><strong>{{ __('common.mobile') }}:</strong> {{ $supplier->mobile ?? 'N/A' }}</p>
                <p><strong>{{ __('common.email') }}:</strong> {{ $supplier->mail ?? 'N/A' }}</p>
                <p><strong>{{ __('purchases.location') }}:</strong> {{ $supplier->city ?? 'N/A' }}@if($supplier->area), {{ $supplier->area }}@endif</p>
            </div>
            <div class="col-md-4 mt-5">
                <p class="mt-1"><strong>{{ __('common.date') }}:</strong> {{ \Carbon\Carbon::parse($purchase->purchase_date ?? now())->format('d-m-Y') }}</p>
                <p><strong>{{ __('common.invoice') }}:</strong> {{ $purchase->invoice ?? 'N/A' }}</p>
                <p><strong>{{ __('common.reference') }}:</strong> {{ $purchase->reference ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="card">
                    <div class="card-body">
                        <h6>{{ __('purchases.purchase_summary') }}</h6>
                        <div class="row">
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('common.total') }}:</label><input disabled="" class="form-control form-control-sm" type="text" value="{{ $purchase->totalAmount ?? 0 }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('common.paid_amount') }}:</label><input disabled="" class="form-control form-control-sm" type="text" value="{{ $purchase->paidAmount ?? 0 }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('common.due_amount') }}:</label><input disabled="" class="form-control form-control-sm" id="dueAmount" type="text" value="{{ $purchase->dueAmount ?? 0 }}" style="width: 50%;" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row product-table">
            <div class="col-md-12">
                <h4>{{ __('purchases.products_for_return') }}</h4>
                <table class="table mb-0 table-bordered rounded-0 rn-table-pro">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('purchases.name') }}</th>
                            <th>{{ __('purchases.purchase_quantity') }}</th>
                            <th>{{ __('purchases.current_stock') }}</th>
                            <th>{{ __('purchases.price') }}</th>
                            <th>{{ __('purchases.total') }}</th>
                            <th>{{ __('purchases.return_qty') }}</th>
                            <th>{{ __('purchases.return_amount') }}</th>
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
                            <td><input type="number" name="returnQty" id="returnQty" class="form-control form-control-sm quantity" data-onkeyup="calculateReturnAmount()" value="" max="{{ $stock->currentStock ?? 0 }}" min="1" step="1" /></td>
                            <td><input type="number" class="form-control form-control-sm" value="0" id="returnAmount" readonly /></td>
                        </tr>
                        @else
                        <tr>
                            <td colspan="8" class="text-center">{{ __('purchases.no_product_data_available') }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <div class="input-group mb-3">
                    <span class="input-group-text rounded-0 p-0 px-2 bg-light">{{ __('purchases.total_return') }}:</span>
                    <input type="number" id="grandTotal" name="totalReturnAmount" class="form-control" value="0" readonly>
                </div>
            </div>
            <div class="col-6">
                <div class="input-group mb-3">
                    <span class="input-group-text rounded-0 p-0 px-2 bg-light">{{ __('purchases.adjust_amount') }}</span>
                    <input type="number" name="adjustAmount" id="adjustAmount" class="form-control" value="0">
                </div>
            </div>
        </div>

        <div class="row shadow p-3">
            <div class="col-12">
                <h5 class="card-title">{{ __('purchases.return_details_optional') }}</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('purchases.return_note') }}:</label>
                        <textarea class="form-control" name="returnNote" placeholder="{{ __('common.enter_return_note_if_any') }}" rows="2"></textarea>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="d-flex gap-4 w-100">
                            <button type="submit" class="btn btn-success w-100">{{ __('purchases.submit_return') }}</button>
                        </div>
                    </div>

                    <div class="col-md-3 d-flex align-items-center">
                        <div class="d-flex gap-4 w-100">
                            <button type="button" class="btn btn-info w-100">{{ __('purchases.return_and_refund') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@section('scripts')
    @parent
    <script>
    function calculateReturnAmount() {
        const returnQtyEl = document.getElementById('returnQty');
        const returnQty = parseInt(returnQtyEl.value || 0, 10);
        const buyPrice = parseFloat(document.getElementById('buyPrice').value || 0);
        const currentStock = parseInt(document.getElementById('currentStock').value || 0, 10);

        if (isNaN(returnQty) || returnQty <= 0) {
            returnQtyEl.value = '';
            document.getElementById('returnAmount').value = '0.00';
            document.getElementById('grandTotal').value = '0.00';
            return;
        }

        // Ensure return quantity doesn't exceed current stock
        if (returnQty > currentStock) {
            alert(window.RNTranslate('common.client_return_quantity_cannot_exceed_current_stock') + currentStock + ')');
            returnQtyEl.value = currentStock;
        }

        const safeQty = Math.min(returnQty, currentStock);
        const returnAmount = safeQty * (isNaN(buyPrice) ? 0 : buyPrice);
        document.getElementById('returnAmount').value = returnAmount.toFixed(2);
        document.getElementById('grandTotal').value = returnAmount.toFixed(2);
    }
    </script>

    @include('customScript')
@endsection
