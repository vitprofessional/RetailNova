@extends('include') @section('backTitle') sale return @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
@if(!empty($readOnlyReturnHistory))
<div class="col-12 mb-3">
    <div class="alert alert-info mb-0">
        This sale has already been returned and the original invoice lines were cleared. The page is shown in history-only mode.
    </div>
</div>
@endif
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
                <table class="table mb-0 table-bordered rounded-0 rn-table-pro">
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
                        @php $historyOnly = !empty($readOnlyReturnHistory); @endphp
                        <input type="hidden" name="productId[]" value="{{ $item->productId }}">
                        <input type="hidden" name="purchaseId[]" value="{{ $item->purchaseId }}">
                        <input type="hidden" name="saleId[]" value="{{ $item->saleId }}">
                        <tr class="product-row">
                            <td>{{ $sl }}</td>
                            <td>{{ $item->productName }}</td>
                            <td><input type="number" id="avlQty{{$sl}}" class="form-control form-control-sm" value="{{ $item->qty }}" readonly /></td>
                            <td><input type="number" step="0.01" id="salePrice{{$sl}}" class="form-control form-control-sm price" value="{{ $item->salePrice }}" readonly /></td>
                            <td>{{ number_format($item->totalSale ?? 0, 2, '.', ',') }}</td>
                            <td><input type="checkbox" class="return-checkbox" data-row="{{ $sl }}" @if($historyOnly) disabled @endif /></td>
                            <td><input type="number" name="totalQty[]" id="rtnqty{{$sl}}" class="form-control form-control-sm quantity" value="0" min="0" max="{{ (int)($item->qty ?? 0) }}" step="1" disabled @if($historyOnly) readonly disabled @endif /></td>
                            <td><input type="number" class="form-control form-control-sm return-amount" value="0" id="returnAmount{{$sl}}" readonly /></td>
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
                    <input type="number" id="totalReturnAmount" name="totalReturnAmount" class="form-control" value="0" readonly>
                </div>
            </div>
            <div class="col-6">
                <div class="input-group mb-3">
                    <span class="input-group-text rounded-0 p-0 px-2 bg-light">Adjust Amount</span>
                    <input type="number" name="adjustAmount" id="adjustAmount" class="form-control" value="0" @if($invoice->curDue == 0 || !empty($readOnlyReturnHistory)) readonly @endif>
                </div>
            </div>
        </div>
        
        <div class="row shadow p-3">
            <div class="col-12">
                <h5 class="card-title">Return Details (Optional)</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Return Note</label>
                        <textarea class="form-control" name="returnNote" placeholder="Enter return note if any" rows="2"></textarea>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            @if(empty($readOnlyReturnHistory))
                                <button type="submit" class="btn btn-success flex-grow-1">Submit Return</button>
                                <button type="button" class="btn btn-primary flex-grow-1" id="returnAndRefundBtn">Return & Refund</button>
                            @else
                                <button type="button" class="btn btn-secondary flex-grow-1" disabled>Return already recorded</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
    @include('customScript')
    <script>
    (function() {
        function toNumber(value) {
            const parsed = parseFloat(value);
            return isNaN(parsed) ? 0 : parsed;
        }

        function toInt(value) {
            const parsed = parseInt(value, 10);
            return isNaN(parsed) ? 0 : parsed;
        }

        function clampQty(input, rowNum) {
            const availQtyInput = document.getElementById('avlQty' + rowNum);
            const maxQty = availQtyInput ? toInt(availQtyInput.value) : 0;
            let qty = toInt(input.value);
            if (qty < 0) {
                qty = 0;
            }
            if (qty > maxQty) {
                qty = maxQty;
                alert('Return quantity cannot exceed available quantity (' + maxQty + ')');
            }
            input.value = String(qty);
            return qty;
        }

        function calculateReturnAmounts() {
            let totalReturn = 0;

            document.querySelectorAll('.quantity').forEach(function(input) {
                const match = (input.id || '').match(/rtnqty(\d+)/);
                if (!match) {
                    return;
                }

                const rowNum = match[1];
                const qty = input.disabled ? 0 : clampQty(input, rowNum);
                const priceInput = document.getElementById('salePrice' + rowNum);
                const amountInput = document.getElementById('returnAmount' + rowNum);
                const price = priceInput ? toNumber(priceInput.value) : 0;
                const amount = qty * price;

                if (amountInput) {
                    amountInput.value = amount.toFixed(2);
                }
                totalReturn += amount;
            });

            const totalReturnInput = document.getElementById('totalReturnAmount');
            if (totalReturnInput) {
                totalReturnInput.value = totalReturn.toFixed(2);
            }

            const adjustInput = document.getElementById('adjustAmount');
            const dueInput = document.getElementById('dueAmount');
            if (adjustInput && !adjustInput.readOnly) {
                const dueAmount = dueInput ? toNumber(dueInput.value) : 0;
                const maxAdjust = Math.min(totalReturn, dueAmount);
                let adjustAmount = toNumber(adjustInput.value);
                if (adjustAmount < 0) {
                    adjustAmount = 0;
                }
                if (adjustAmount > maxAdjust) {
                    adjustAmount = maxAdjust;
                }
                adjustInput.value = adjustAmount.toFixed(2);
            }
        }

        function enableAllQtyForSubmit(form) {
            form.querySelectorAll('input[name="totalQty[]"]').forEach(function(input) {
                input.disabled = false;
            });
        }

        function hasAnyReturnQty(form) {
            return Array.from(form.querySelectorAll('input[name="totalQty[]"]')).some(function(input) {
                return toInt(input.value) > 0;
            });
        }

        function initializeReturnForm() {
            const form = document.querySelector('form.card.form');
            if (!form) {
                return;
            }

            document.querySelectorAll('.return-checkbox').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const rowNum = this.getAttribute('data-row');
                    const qtyInput = document.getElementById('rtnqty' + rowNum);
                    if (!qtyInput) {
                        return;
                    }

                    if (this.checked) {
                        qtyInput.disabled = false;
                        if (toInt(qtyInput.value) <= 0) {
                            qtyInput.value = '1';
                        }
                        qtyInput.focus();
                    } else {
                        qtyInput.value = '0';
                        qtyInput.disabled = true;
                    }
                    calculateReturnAmounts();
                });
            });

            document.querySelectorAll('.quantity').forEach(function(input) {
                input.addEventListener('input', function() {
                    const match = (this.id || '').match(/rtnqty(\d+)/);
                    if (match) {
                        clampQty(this, match[1]);
                    }
                    calculateReturnAmounts();
                });
            });

            const adjustInput = document.getElementById('adjustAmount');
            if (adjustInput) {
                adjustInput.addEventListener('input', calculateReturnAmounts);
            }

            form.addEventListener('submit', function(e) {
                calculateReturnAmounts();
                if (!hasAnyReturnQty(form)) {
                    e.preventDefault();
                    alert('Please select at least one item quantity to return.');
                    return;
                }
                enableAllQtyForSubmit(form);
            });

            const refundBtn = document.getElementById('returnAndRefundBtn');
            if (refundBtn) {
                refundBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    calculateReturnAmounts();

                    const totalReturnAmount = toNumber(document.getElementById('totalReturnAmount').value);
                    const adjustAmount = toNumber((document.getElementById('adjustAmount') || {}).value);
                    const finalAmount = totalReturnAmount + adjustAmount;

                    if (totalReturnAmount <= 0) {
                        alert('Please select items to return');
                        return;
                    }

                    if (confirm('Return amount: ' + finalAmount.toFixed(2) + '\n\nProceed with return and refund?')) {
                        enableAllQtyForSubmit(form);
                        form.submit();
                    }
                });
            }

            calculateReturnAmounts();
        }

        document.addEventListener('DOMContentLoaded', initializeReturnForm);
    })();
    </script>
@endsection
