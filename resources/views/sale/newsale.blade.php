@extends('include')
@section('backTitle'){{ __('sales.new_sale') }}@endsection
@section('container')
<link rel="stylesheet" href="{{ asset('css/new-sale.css') }}?v={{ filemtime(public_path('css/new-sale.css')) }}">
<div class="col-12 rn-new-sale-shell">@include('sweetalert::alert')
<form action="{{ route('saveSale') }}" id="saveSaleForm" class="sale-page rn-new-sale" method="POST" data-action-template="{{ route('saveSale') }}">
@csrf
<input type="hidden" id="saleAction" name="saleAction" value="save_print">
    <div id="rn-route-seeds" class="d-none"
         data-barcode-url="{{ route('product.findByBarcode') }}" data-newsale="{{ route('newsale') }}"
         data-products-template="{{ route('ajax.customer.products.public', ['id' => '__ID__']) }}"
         data-categories-url="{{ route('ajax.categories.public') }}"
         data-category-products-template="{{ route('ajax.category.products.public', ['id' => '__ID__']) }}"
         data-purchase-template="{{ route('ajax.sale.product.purchaseDetails.public', ['id' => '__ID__']) }}"
         data-purchase-by-id-template="{{ route('ajax.purchase.details.public', ['id' => '__ID__']) }}"
         data-product-details-template="{{ route('ajax.product.details.public', ['id' => '__ID__']) }}"
         data-purchase-serials-template="{{ route('ajax.purchase.serials.public', ['id' => '__ID__']) }}"></div>

@include('sale.new-sale.header')
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach<div>{{ __('common.reopen_serial_selection_and_retry_with_the_current_sales_policy') }}</div></div>@endif
<div class="rn-sale-layout"><div class="rn-sale-workspace">
@include('sale.new-sale.customer')
@include('sale.new-sale.search')
@include('sale.new-sale.items')
</div><aside class="rn-sale-billing" aria-label="{{ __('sales.billing_summary') }}">@include('sale.new-sale.billing')</aside></div>
</form></div>
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
 <div class="modal fade" id="customerModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5" id="customerModalLabel">{{ __('sales.create_customer') }}</h6>
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
<script src="{{ asset('js/new-sale.js') }}?v={{ filemtime(public_path('js/new-sale.js')) }}"></script>
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
                                paid.dispatchEvent(new Event('input', { bubbles: true }));
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
