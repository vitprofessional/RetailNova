@extends('include')
    @section('backTitle')
        new sale
    @endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="{{ route('saveSale') }}" id="saveSaleForm" class="sale-page" method="POST" data-action-template="{{ route('saveSale') }}">
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
                                    <label>{{ __('sales.date') }}</label>
                                    <input type="date" class="form-control" value="{{ date('Y-m-d') }}" name="date" placeholder="" required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <!-- add customer button moved inline beside the select -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="invoice" class="form-label">{{ __('common.invoice') }} *</label>
                                    <input type="text" class="form-control" id="invoice" name="invoice" value="{{ $randomInvoiceNumber }}" readonly />
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <div class="form-group">
                                    <label>{{ __('sales.select_customer') }} * <span class="small text-muted">(use Walk-in for quick sale)</span></label>
                                    <label for="customerName" class="form-label">{{ __('sales.customer') }}</label>
                                    <div class="input-group">
                                        <select id="customerName" name="customerId" class="form-control" data-onchange="actSaleProduct()"
                                            data-products-url="{{ route('ajax.customer.products.public', ['id' => '__ID__']) }}">
                                                <option value="">{{ __('sales.select_customer') }}</option>
                                          @if(!empty($customerList) && count($customerList)>0)
                                          @foreach($customerList as $customerData)
                                            <option value="{{$customerData->id}}" {{ (isset($walkingCustomerId) && $walkingCustomerId === $customerData->id) ? 'selected' : '' }}>{{$customerData->name}}</option>
                                            @endforeach
                                            @endif
                                        </select>
                                        <button type="button" class="btn btn-outline-primary rn-btn-plus rounded-0" title="{{ __('common.add_customer') }}" data-toggle="modal" data-target="#customerModal">
                                            <i class="las la-plus"></i>
                                        </button>
                                    </div>
                                    <div class="my-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="useWalkingCustomerBtn">{{ __('sales.use_walk_in_customer') }}</button>
                                        <span id="prevDueDisplay" class="badge bg-warning text-dark mx-2">{{ __('common.previous_due') }}: 0.00</span>
                                        <button id="outOfStockBtn" type="button" class="btn btn-outline-danger btn-sm mx-2" style="display:none; height:30px; padding:4px 8px;" data-toggle="modal" data-target="#outOfStockModal" title="0 product(s) out of stock">{{ __('sales.view_out_of_stock_0') }}</button>
                                        <span id="outOfStockNote" class="text-muted small ms-2" style="display:none">&nbsp;</span>
                                    </div>
                                </div>
                            </div>
                            @include('partials.business_selector', ['businesses' => $businesses ?? [] , 'selectedBusinessId' => null])
                        </div>
                        <div class="row mt-3">
                            <!-- Barcode Scanner Component -->
                            <div class="col-md-12">
                                @include('components.barcode-scanner')
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('sales.product_category') }}</label>
                                    <select id="categorySelect" name="categoryId" class="form-control" disabled>
                                        <option value="">{{ __('sales.all_categories') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                <label>{{ __('common.select_product') }}*</label>
                                <select id="productName" name="productName" class="form-control js-sale-product-select" disabled
                                    data-purchase-url="{{ route('getSaleProductDetails', ['id' => '__ID__']) }}">
                                   <!--  form option show proccessing -->
                                            <option value="">{{ __('sales.select') }}</option>
                                  @if(!empty($productList) && count($productList)>0)
                                  @foreach($productList as $productData)
                                    <option value="{{$productData->id}}">{{$productData->name}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            </div>
                            <div class="col-8">
                                <label for="note">{{ __('sales.note_if_applicable') }}</label>
                                <textarea class="form-control" id="note" name="note" placeholder="{{ __('common.enter_some_notes_if_you_have') }}"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
         <div class="card ">
            <div class="card-body  product-table">
                <div class="header-title">
                    <h6 class="card-title">{{ __('sales.product_details') }}</h6>
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
                </style>
                @include('sale.mobile-styles')
                <div class="table-responsive rn-table-wrap">
                    <table class="table table-sm mb-0 table-bordered rounded-0 rn-table-pro rn-sale-mobile-table">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>{{ __('sales.remove') }}</th>
                                <th>{{ __('sales.product_name') }}</th>
                                <th>{{ __('sales.purchase_data') }}</th>
                                <th>{{ __('sales.serials') }}</th>
                                <th>{{ __('sales.warranty_days') }}</th>
                                <th>{{ __('sales.qty') }}</th>
                                <th>{{ __('sales.sale_price') }}</th>
                                <th>{{ __('sales.total') }}</th>
                                <th>{{ __('sales.purchase') }}</th>
                                <th>{{ __('sales.purchase_total') }}</th>
                                <th>{{ __('sales.profit_margin') }}</th>
                                <th>{{ __('sales.profit_total') }}</th>
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
                            <h6 class="card-title">{{ __('sales.payment_details') }}</h6>
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
                                    <div class="rn-pay-label">{{ __('common.total') }}:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="totalSaleAmount" name="totalSaleAmount" value="0" readonly />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">{{ __('common.discount_amount') }}:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="discountAmount" data-onkeyup="getDiscountAmount()" name="discountAmount" value="0" />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">{{ __('sales.additional_charge_name') }}:</div>
                                    <div class="rn-pay-value">
                                        <input type="text" class="form-control" id="additionalChargeName" name="additionalChargeName" placeholder="{{ __('common.e_g_service_charge') }}" />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">{{ __('sales.additional_charge_amount') }}:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="additionalChargeAmount" name="additionalChargeAmount" value="0" oninput="try{ if(typeof window.recalcFinancials === 'function'){ window.recalcFinancials(); } }catch(e){}" />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">{{ __('common.grand_total') }}:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="grandTotal" name="grandTotal" value="0" readonly />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">{{ __('common.paid_amount') }}:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="paidAmount" name="paidAmount" value="0" step="0.01" oninput="try{ if(typeof window.recalcTotals === 'function'){ window.recalcTotals(); } else if(typeof window.recalcFinancials === 'function'){ window.recalcFinancials(); } }catch(e){}" />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">{{ __('common.due_amount') }}:</div>
                                    <div class="rn-pay-value">
                                        <input type="number" class="form-control" id="dueAmount" name="dueAmount" value="0" readonly />
                                    </div>
                                </div>
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">{{ __('sales.payment_by') }}</div>
                                    <div class="rn-pay-value">
                                        {{ optional(auth('admin')->user())->fullName ?? '' }}
                                    </div>
                                </div>
                                @php
                                    $defaultInvoicePrinter = isset($defaultInvoicePrinter) ? (string)$defaultInvoicePrinter : (string) config('pos.default_invoice_printer', 'thermal80');
                                    if(!in_array($defaultInvoicePrinter, ['a4','thermal80','thermal58'], true)){
                                        $defaultInvoicePrinter = 'thermal80';
                                    }
                                @endphp
                                <div class="rn-pay-row">
                                    <div class="rn-pay-label">{{ __('sales.printer_profile') }}</div>
                                    <div class="rn-pay-value">
                                        <select class="form-control" id="printerProfile" name="printerProfile">
                                            <option value="a4" {{ $defaultInvoicePrinter === 'a4' ? 'selected' : '' }}>{{ __('sales.a4_invoice') }}</option>
                                            <option value="thermal80" {{ $defaultInvoicePrinter === 'thermal80' ? 'selected' : '' }}>{{ __('sales.pos_thermal_80mm') }}</option>
                                            <option value="thermal58" {{ $defaultInvoicePrinter === 'thermal58' ? 'selected' : '' }}>{{ __('sales.pos_thermal_58mm') }}</option>
                                        </select>
                                    </div>
                                </div>
                                        <div class="rn-pay-row rn-pay-actions">
                                            <div class="rn-pay-label"></div>
                                            <div class="rn-pay-value">
                                                <button type="button" id="rnReceiveAmountBtn" class="btn btn-outline-danger">{{ __('sales.receive_amount') }}</button>
                                            </div>
                                        </div>
                            </div>
                            <!-- Hidden operational fields to preserve calculations -->
                            <input type="number" class="form-control d-none" id="prevDue" name="prevDue" value="0" readonly />
                            <input type="number" class="form-control d-none" id="curDue" name="curDue" value="0" readonly />
                        </div>
                        <div class="d-md-flex  mt-2 align-items-center">
                            @if($errors->any())
                            <div class="alert alert-danger" role="alert">{{ $errors->first() }} {{ __('common.reopen_serial_selection_and_retry_with_the_current_sales_policy') }}</div>
                            @endif
                            <div id="saleErrorSummary" class="me-3" style="flex:1"></div>
                            <div class="text-end">
                                <button class="btn btn-outline-primary save-btn btn-sm me-w" type="submit">{{ __('sales.save_and_print') }}</button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span id="totalOutstandingDisplay" class="badge bg-danger">{{ __('common.total_outstanding_0_00') }}</span>
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
                <h6 class="modal-title" id="saleSerialModalLabel">{{ __('sales.select_serials') }}</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="{{ __('sales.close') }}"></button>
            </div>
            <div class="modal-body">
                <div id="saleSerialStatus" class="alert alert-info mb-2">{{ __('sales.loading_available_serials') }}</div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="small text-muted" id="saleSerialMeta"></div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('common.serial_actions') }}">
                        <button type="button" class="btn btn-outline-secondary" id="saleSerialSelectAll">{{ __('sales.select_all') }}</button>
                        <button type="button" class="btn btn-outline-secondary" id="saleSerialClear">{{ __('sales.clear') }}</button>
                    </div>
                </div>
                <div id="saleSerialList" class="list-group" style="max-height:320px; overflow:auto;"></div>
            </div>
            <div class="modal-footer">
                <div class="text-muted me-auto small" id="saleSerialHint"></div>
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('sales.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="applySaleSerials">{{ __('sales.apply_serials') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- start_model -->
 <div class="modal fade" id="customerModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="customerModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5" id="customerModal">{{ __('sales.create_customer') }}</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="{{ __('sales.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('createCustomer') }}" method="GET" id="customerForm" data-ajax="true" data-target="#customerName" data-modal-id="customerModal">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('common.name') }} *</label>
                                        <input type="text" class="form-control" placeholder="{{ __('common.enter_name') }}" id="fullName" name="fullName" required />
                                        <div class="help-block with-errors"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('common.email') }} *</label>
                                        <input type="email" class="form-control" placeholder="{{ __('common.enter_email') }}" id="mail" name="mail" />
                                        <div class="help-block with-errors"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('sales.phone_number') }}</label>
                                        <input type="text" class="form-control" placeholder="{{ __('common.enter_phone_number') }}" id="mobile" name="mobile" />
                                        <div class="help-block with-errors"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="inputState" class="form-label">{{ __('sales.country') }}</label>

                                        <input type="text" class="form-control" placeholder="{{ __('common.enter_the_country') }}" id="country" name="country" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="inputState" class="form-label">{{ __('sales.state') }}</label>

                                        <input type="text" class="form-control" placeholder="{{ __('common.enter_the_state') }}" id="state" name="state" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="inputState" class="form-label">{{ __('sales.city') }}</label>

                                        <input type="text" class="form-control" placeholder="{{ __('common.enter_the_city') }}" id="city" name="city" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="inputState" class="form-label">{{ __('sales.area') }}</label>

                                        <input type="text" class="form-control" placeholder="{{ __('common.enter_the_area') }}" id="area" name="area" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="openingBalance" class="form-label">{{ __('sales.opening_balance') }}
                                            <span class="ml-1" data-toggle="tooltip" title="{{ __('common.positive_customer_owes_you_negative_you_owe_customer_leave_0_for_new_customers') }}">
                                                <i class="ri-information-line"></i>
                                            </span>
                                        </label>
                                        <input type="number" step="1" class="form-control" placeholder="0" id="openingBalance" name="openingBalance" value="0" />
                                        <small class="text-muted">{{ __('sales.use_positive_for_receivable_negative_for_payable') }}</small>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mr-2" id="add-customer">{{ __('sales.add_customer') }}</button>
                            <button type="reset" class="btn btn-danger">{{ __('sales.reset') }}</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('sales.cancel') }}</button>
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
                <h5 class="modal-title" id="outOfStockModalLabel">{{ __('sales.out_of_stock_items') }}</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="{{ __('sales.close') }}"></button>
            </div>
            <div class="modal-body">
                <div id="outOfStockList">
                    <p class="text-muted">{{ __('sales.no_out_of_stock_items') }}</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('sales.close') }}</button>
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
                            try{ if(typeof showToast === 'function') showToast(window.RNTranslate('common.client_customer_selected'),window.RNTranslate('common.client_using_walking_customer'),'success'); }catch(_){}
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
                                try{ if(typeof showToast === 'function') showToast(window.RNTranslate('common.client_payment_updated'),window.RNTranslate('common.client_paid_set_to_grand_total'),'info'); }catch(_){}
                            }
                        }catch(e){ console.warn('Receive Amount click failed', e); }
                    });
                }
            }catch(e){ }
        });
    })();
    </script>
@endsection