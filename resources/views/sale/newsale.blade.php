@extends('include') 
    @section('backTitle') 
        new sale 
    @endsection 
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="{{ route('saveSale') }}" id="saveSaleForm" class="row sale-page" method="POST" data-action-template="{{ route('saveSale') }}">
    @csrf
    <style>
        .sale-page .card .card-body { padding: 16px; }
        .sale-page .form-group label { font-weight:600; color:#111; }
        .input-group .btn { border-left:0; }
        .rn-btn-plus { width:40px; display:flex; align-items:center; justify-content:center; }
        .card-title h6 { font-size:1rem; font-weight:700; color:#111; }
        .rn-pay-box { background:#fff; border:1px solid #e6e6e6; }
        .table thead th { background:#fafafa; font-weight:600; color:#111; }
        /* Compact table spacing */
        .rn-table-pro td, .rn-table-pro th{ padding:8px; vertical-align:middle }
        /* Sticky payment panel on wide screens */
        @media(min-width: 992px){
            .payment-side{ position:sticky; top:90px; }
        }
        /* Make primary actions more prominent */
        .btn-primary.save-btn{ padding:10px 22px; font-weight:700 }
    </style>
    <div id="rn-route-seeds" class="d-none"
         data-newsale="{{ route('newsale') }}"
         data-products-template="{{ route('ajax.customer.products.public', ['id' => '__ID__']) }}"
         data-categories-url="{{ route('ajax.categories.public') }}"
         data-category-products-template="{{ route('ajax.category.products.public', ['id' => '__ID__']) }}"
         data-purchase-template="{{ route('ajax.sale.product.purchaseDetails.public', ['id' => '__ID__']) }}"
         data-purchase-by-id-template="{{ route('ajax.purchase.details.public', ['id' => '__ID__']) }}"
         data-product-details-template="{{ route('ajax.product.details.public', ['id' => '__ID__']) }}"
         data-purchase-serials-template="{{ route('ajax.purchase.serials.public', ['id' => '__ID__']) }}"></div>
        <div class="row">
            <div class="col-md-12 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" value="{{ date('Y-m-d') }}" name="date" placeholder="" required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <!-- add customer button moved inline beside the select -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="invoice" class="form-label">Invoice *</label>
                                    <input type="text" class="form-control" id="invoice" name="invoice" value="{{ $randomInvoiceNumber }}" readonly />
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <div class="form-group">
                                    <label>Select Customer * <span class="small text-muted">(use Walk-in for quick sale)</span></label>
                                    <label for="customerName" class="form-label">Customer</label>
                                    <div class="input-group">
                                        <select id="customerName" name="customerId" class="form-control" data-onchange="actSaleProduct()"
                                            data-products-url="{{ route('ajax.customer.products.public', ['id' => '__ID__']) }}">
                                                <option value="">Select customer</option>
                                          @if(!empty($customerList) && count($customerList)>0)
                                          @foreach($customerList as $customerData)
                                            <option value="{{$customerData->id}}" {{ (isset($walkingCustomerId) && $walkingCustomerId === $customerData->id) ? 'selected' : '' }}>{{$customerData->name}}</option>
                                            @endforeach
                                            @endif
                                        </select>
                                        <button type="button" class="btn btn-outline-primary rn-btn-plus rounded-0" title="Add customer" data-toggle="modal" data-target="#customerModal">
                                            <i class="las la-plus"></i>
                                        </button>
                                    </div>
                                    <div class="my-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="useWalkingCustomerBtn">Use Walk-in Customer</button>
                                        <span id="prevDueDisplay" class="badge bg-warning text-dark mx-2">Previous Due: 0.00</span>
                                        <button id="outOfStockBtn" type="button" class="btn btn-outline-danger btn-sm mx-2" style="display:none; height:30px; padding:4px 8px;" data-toggle="modal" data-target="#outOfStockModal" title="0 product(s) out of stock">View Out of Stock(0)</button>
                                        <span id="outOfStockNote" class="text-muted small ms-2" style="display:none">&nbsp;</span>
                                    </div>
                                </div>
                            </div>
                            @include('partials.business_selector', ['businesses' => $businesses ?? [] , 'selectedBusinessId' => null])
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Product Category</label>
                                    <select id="categorySelect" name="categoryId" class="form-control" disabled>
                                        <option value="">All Categories</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                <label>Select Product*</label>
                                <select id="productName" name="productName" class="form-control js-sale-product-select" disabled 
                                    data-purchase-url="{{ route('getSaleProductDetails', ['id' => '__ID__']) }}">
                                   <!--  form option show proccessing -->
                                            <option value="">Select</option>
                                  @if(!empty($productList) && count($productList)>0)
                                  @foreach($productList as $productData)
                                    <option value="{{$productData->id}}">{{$productData->name}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            </div>
                            <div class="col-8">
                                <label for="note">Note(if applicable)</label>
                                <textarea class="form-control" id="note" name="note" placeholder="Enter some notes if you have"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
         <div class="card ">
            <div class="card-body  product-table">
                <div class="header-title">
                    <h6 class="card-title">Product Details</h6>
                </div>
                <style>
                    .rn-table-wrap{overflow-x:auto; -webkit-overflow-scrolling:touch}
                    .rn-table-pro{min-width:960px}
                    .rn-table-pro thead th{position:sticky; top:0; background:#fff; z-index:1}
                    /* Ensure product name shows fully and aligns left */
                    .rn-table-pro thead th:nth-child(2),
                    .rn-table-pro tbody td:nth-child(2){
                        text-align:left !important;
                        min-width:260px;
                    }
                    .rn-table-pro tbody td:nth-child(2) *{
                        white-space:normal !important;
                        word-break:break-word;
                        overflow:visible;
                        text-overflow:clip;
                    }
                    .rn-table-pro tbody td:nth-child(2) .form-control{
                        width:100% !important;
                        min-width:0 !important;
                    }
                    @media (max-width: 992px){
                        .rn-table-pro{min-width:720px}
                        .rn-table-pro thead th:nth-child(3),
                        .rn-table-pro tbody td:nth-child(3),
                        .rn-table-pro thead th:nth-child(4),
                        .rn-table-pro tbody td:nth-child(4),
                        .rn-table-pro thead th:nth-child(5),
                        .rn-table-pro tbody td:nth-child(5),
                        .rn-table-pro thead th:nth-child(9),
                        .rn-table-pro tbody td:nth-child(9),
                        .rn-table-pro thead th:nth-child(10),
                        .rn-table-pro tbody td:nth-child(10),
                        .rn-table-pro thead th:nth-child(11),
                        .rn-table-pro tbody td:nth-child(11),
                        .rn-table-pro thead th:nth-child(12),
                        .rn-table-pro tbody td:nth-child(12){display:none}
                    }
                    @media (max-width: 576px){
                        .rn-table-pro{min-width:540px}
                        .rn-table-pro thead th:nth-child(8),
                        .rn-table-pro tbody td:nth-child(8){display:none}
                    }
                </style>
                <div class="table-responsive rn-table-wrap">
                    <table class="table table-sm mb-0 table-bordered rounded-0 rn-table-pro">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>Remove</th>
                                <th>Product Name</th>
                                <th>Purchase Data</th>
                                <th>Serials</th>
                                <th>Warranty (days)</th>
                                <th>Qty</th>
                                <th>Sale Price</th>
                                <th>Total</th>
                                <th>Purchase</th>
                                <th>Purchase Total</th>
                                <th>Profit Margin</th>
                                <th>Profit Total</th>
                            </tr>
                        </thead>
                        <tbody id="productDetails">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="header-title">
                            <h6 class="card-title">Payment Details</h6>
                        </div>
                        <div class="mb-3 product-table">
                            <style>
                                .rn-pay-box{border:1px solid #e9ecef; border-radius:8px; background:#fff; padding:16px}
                                .rn-pay-row{display:flex; align-items:center; margin-bottom:10px}
                                .rn-pay-label{width:240px; color:#333}
                                .rn-pay-value{flex:1}
                                .rn-pay-value .form-control{max-width:380px; margin-left:auto}
                                .rn-pay-actions{display:flex; justify-content:flex-end}
                                @media (max-width:768px){
                                    .rn-pay-row{flex-direction:column; align-items:stretch}
                                    .rn-pay-label{width:auto; margin-bottom:6px}
                                    .rn-pay-value .form-control{max-width:100%; margin-left:0}
                                    .rn-pay-actions{justify-content:flex-start}
                                }
                            </style>
                            <div id="paymentDetails" class="rn-pay-box">
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">Total:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="totalSaleAmount" name="totalSaleAmount" value="0" readonly />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">Discount Amount:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="discountAmount" data-onkeyup="getDiscountAmount()" name="discountAmount" value="0" />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">Additional Charge Name:</div>
                                    <div class="rn-pay-value">
                                        <input type="text" class="form-control" id="additionalChargeName" name="additionalChargeName" placeholder="e.g., Service Charge" />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">Additional Charge Amount:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="additionalChargeAmount" name="additionalChargeAmount" value="0" oninput="try{ if(typeof window.recalcFinancials === 'function'){ window.recalcFinancials(); } }catch(e){}" />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">Grand Total:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="grandTotal" name="grandTotal" value="0" readonly />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">Paid Amount:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="paidAmount" name="paidAmount" value="0" step="0.01" oninput="try{ if(typeof window.recalcTotals === 'function'){ window.recalcTotals(); } else if(typeof window.recalcFinancials === 'function'){ window.recalcFinancials(); } }catch(e){}" />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">Due Amount:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="dueAmount" name="dueAmount" value="0" readonly />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">Payment By</div>
                                    <div class="rn-pay-value">
                                        {{ optional(auth('admin')->user())->fullName ?? '' }}
                                    </div>
                                </div>
                                        <div class="rn-pay-row rn-pay-actions">
                                            <div class="rn-pay-label"></div>
                                            <div class="rn-pay-value">
                                                <button type="button" id="rnReceiveAmountBtn" class="btn btn-outline-danger">Receive Amount</button>
                                            </div>
                                        </div>
                            </div>
                            <!-- Hidden operational fields to preserve calculations -->
                            <input type="number" class="form-control d-none" id="prevDue" name="prevDue" value="0" readonly />
                            <input type="number" class="form-control d-none" id="curDue" name="curDue" value="0" readonly />
                        </div>
                        <div class="d-md-flex  mt-2 align-items-center">
                            <div id="saleErrorSummary" class="me-3" style="flex:1"></div>
                            <div class="text-end">
                                <button class="btn btn-outline-secondary btn-sm me-2" type="button" id="btnSaveDraft">Save Draft</button>
                                <button class="btn btn-outline-primary save-btn btn-sm me-w" type="submit">Save & Print</button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span id="totalOutstandingDisplay" class="badge bg-danger">Total Outstanding: 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Serial selection modal for sale rows -->
<div class="modal fade" id="saleSerialModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="saleSerialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="saleSerialModalLabel">Select Serials</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="saleSerialStatus" class="alert alert-info mb-2">Loading available serials...</div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="small text-muted" id="saleSerialMeta"></div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Serial actions">
                        <button type="button" class="btn btn-outline-secondary" id="saleSerialSelectAll">Select All</button>
                        <button type="button" class="btn btn-outline-secondary" id="saleSerialClear">Clear</button>
                    </div>
                </div>
                <div id="saleSerialList" class="list-group" style="max-height:320px; overflow:auto;"></div>
            </div>
            <div class="modal-footer">
                <div class="text-muted me-auto small" id="saleSerialHint"></div>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="applySaleSerials">Apply Serials</button>
            </div>
        </div>
    </div>
</div>

<!-- start_model -->
 <div class="modal fade" id="customerModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="customerModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5" id="customerModal">Create Customer</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('createCustomer') }}" method="GET" id="customerForm" data-ajax="true" data-target="#customerName" data-modal-id="customerModal">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Name *</label>
                                        <input type="text" class="form-control" placeholder="Enter Name" id="fullName" name="fullName" required />
                                        <div class="help-block with-errors"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Email *</label>
                                        <input type="email" class="form-control" placeholder="Enter Email" id="mail" name="mail" />
                                        <div class="help-block with-errors"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" class="form-control" placeholder="Enter Phone Number" id="mobile" name="mobile" />
                                        <div class="help-block with-errors"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="inputState" class="form-label">Country</label>

                                        <input type="text" class="form-control" placeholder="Enter The Country" id="country" name="country" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="inputState" class="form-label">State</label>

                                        <input type="text" class="form-control" placeholder="Enter The State" id="state" name="state" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="inputState" class="form-label">City</label>

                                        <input type="text" class="form-control" placeholder="Enter The City" id="city" name="city" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="inputState" class="form-label">Area</label>

                                        <input type="text" class="form-control" placeholder="Enter The area" id="area" name="area" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="openingBalance" class="form-label">Opening Balance
                                            <span class="ml-1" data-toggle="tooltip" title="Positive = customer owes you. Negative = you owe customer. Leave 0 for new customers.">
                                                <i class="ri-information-line"></i>
                                            </span>
                                        </label>
                                        <input type="number" step="1" class="form-control" placeholder="0" id="openingBalance" name="openingBalance" value="0" />
                                        <small class="text-muted">Use positive for receivable, negative for payable.</small>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mr-2" id="add-customer">Add Customer</button>
                            <button type="reset" class="btn btn-danger">Reset</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- end_model -->
<!-- Out of Stock Modal -->
<div class="modal fade" id="outOfStockModal" tabindex="-1" aria-labelledby="outOfStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="outOfStockModalLabel">Out of Stock Items</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="outOfStockList">
                    <p class="text-muted">No out-of-stock items.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @include('customScript')
    <script>
    (function(){
        document.addEventListener('DOMContentLoaded', function(){
            try{
                var btn = document.getElementById('useWalkingCustomerBtn');
                var sel = document.getElementById('customerName');
                if(btn && sel){
                    btn.addEventListener('click', function(){
                        try{
                            var walkingId = {{ isset($walkingCustomerId) && $walkingCustomerId ? (int)$walkingCustomerId : 'null' }};
                            if(!walkingId){ return; }
                            sel.value = String(walkingId);
                            // trigger change to load products and previous due
                            try{ var ev = new Event('change', { bubbles: true }); sel.dispatchEvent(ev); }catch(_){ }
                            // visual feedback
                            try{ if(typeof showToast === 'function') showToast('Customer selected','Using Walking Customer','success'); }catch(_){}
                        }catch(e){ console.warn('useWalkingCustomer click failed', e); }
                    });
                }
                // Receive Amount button: fill paidAmount with grandTotal
                var rbtn = document.getElementById('rnReceiveAmountBtn');
                if(rbtn){
                    rbtn.addEventListener('click', function(){
                        try{
                            var gt = document.getElementById('grandTotal');
                            var paid = document.getElementById('paidAmount');
                            if(gt && paid){
                                paid.value = String(gt.value || 0);
                                if(typeof dueSaleCalculate === 'function') dueSaleCalculate();
                                else if(typeof window.recalcTotals === 'function') window.recalcTotals();
                                else if(typeof window.recalcFinancials === 'function') window.recalcFinancials();
                                try{ if(typeof showToast === 'function') showToast('Payment updated','Paid set to Grand Total','info'); }catch(_){}
                            }
                        }catch(e){ console.warn('Receive Amount click failed', e); }
                    });
                }
            }catch(e){ }
        });
    })();
    </script>
@endsection