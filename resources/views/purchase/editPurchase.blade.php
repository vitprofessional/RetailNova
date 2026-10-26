@extends('include') @section('backTitle'){{ __('mobile.title_purchase') }}@endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="{{ route('updatePurchase') }}" class="row" method="POST" id="savePurchase">
    @csrf
    <input type="hidden" name="purchaseId" value="{{ $purchaseData->id ?? '' }}" />
    <div class="col-12">
        <div class="row">
            <div class="col-md-12 col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="{{ __('common.breadcrumb') }}">
                                <ol class="breadcrumb mb-1 bg-transparent p-0">
                                    <li class="breadcrumb-item"><a href="{{ route('purchaseList') }}">{{ __('purchases.purchases') }}</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">{{ __('purchases.edit') }}</li>
                                </ol>
                            </nav>
                            <h4 class="card-title mb-0">{{ __('purchases.edit_purchase') }}</h4>
                            <small class="text-muted">{{ __('purchases.update_purchase_details_serials_and_stock') }}</small>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary" title="{{ __('common.save_changes') }}"> <i class="ri-save-line mr-1"></i> {{ __('purchases.update_purchase') }}</button>
                            <a href="{{ route('purchaseList') }}" class="btn btn-outline-secondary ml-2">{{ __('purchases.back_to_list') }}</a>
                        </div>
                    </div>
                    <div class="card-body py-3">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date" class="form-label">{{ __('common.date') }} *</label>
                                    <input type="date" class="form-control" id="date" name="purchaseDate" value="{{ !empty($purchaseData->purchase_date) ? \Carbon\Carbon::parse($purchaseData->purchase_date)->format('Y-m-d') : '' }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group d-flex align-items-center">
                                    <label for="supplierName" class="form-label mr-2">{{ __('common.supplier') }} *</label>
                                    <select id="supplierName" name="supplierName" data-onchange="actProductList()" class="form-control" required>
                                    <option value="">-</option>
                                    <!--  form option show proccessing -->
                                    @if(!empty($supplierList) && count($supplierList)>0)
                                    @foreach($supplierList as $supplierData)
                                        <option value="{{$supplierData->id}}" {{ (!empty($purchaseData) && $purchaseData->supplier == $supplierData->id) ? 'selected' : '' }}>{{$supplierData->name}}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                    <span id="supplierBadge" class="badge badge-info ml-2" style="font-size:.9rem;">{{ $supplierList->firstWhere('id', $purchaseData->supplier)->name ?? '' }}</span>
                                </div>
                                </div>

                            <div class="col-md-2 mt-4 p-0">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#supplier"><i class="las la-plus mr-2"></i>{{ __('purchases.new_supplier') }}</button>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="invoice" class="form-label">{{ __('common.invoice') }} *</label>
                                    <input type="text" class="form-control" id="invoice" name="invoiceData" value="{{ $purchaseData->invoice ?? $generatedInvoice ?? '' }}" />
                                </div>
                            </div>
                            <div class="col-md-7">
                                @mobile<x-m.product-picker mode="purchase" :label="__('mobile.search_products')" />@endmobile
                                <div class="form-group">
                                    <label for="productName" class="form-label">{{ __('common.product') }} *</label>
                                    <select id="productName" name="productName_select" class="form-control js-product-select" >
                                    <!--  form option show proccessing -->
                                        <option value="">{{ __('purchases.select') }}</option>
                                    @if(!empty($productList) && count($productList)>0)
                                    @foreach($productList as $productData)
                                        @php
                                            $brand = \App\Models\Brand::find($productData->brand);
                                            $brandName = $brand ? ' - ' . $brand->name : '';
                                        @endphp
                                        <option value="{{$productData->id}}" {{ (!empty($purchaseData) && $purchaseData->productName == $productData->id) ? 'selected' : '' }}>{{$productData->name}}{{$brandName}}</option>
                                    @endforeach
                                    @endif
                                    </select>
                                    <!-- Hidden input to ensure product ID is submitted even if select is disabled -->
                                    <input type="hidden" id="productNameHidden" name="productName" value="{{ $purchaseData->productName ?? '' }}" />
                                </div>
                            </div>

                            <div class="col-md-2 mt-4 p-0">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#newProduct"><i class="las la-plus mr-2"></i>{{ __('purchases.new_product') }}</button>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="reference" class="form-label">{{ __('common.reference') }} *</label>
                                    <input type="text" class="form-control" id="reference" name="refData" value="{{ $purchaseData->reference ?? '' }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="mb-3 table-responsive product-table">
                    <table class="table table-striped table-bordered mb-0 rn-table-pro">
                        <thead class="bg-light text-uppercase small">
                            <tr>
                                <th style="min-width:220px">{{ __('purchases.product') }}</th>
                                <th style="width:140px">{{ __('purchases.serials') }}</th>
                                <th style="width:110px" class="text-center">{{ __('purchases.qty') }}</th>
                                <th style="width:120px" class="text-center">{{ __('purchases.stock') }}</th>
                                <th style="width:120px" class="text-right">{{ __('purchases.buy_price') }}</th>
                                <th style="width:130px" class="text-right">{{ __('purchases.sale_ex_vat') }}</th>
                                <th style="width:100px" class="text-center">{{ __('purchases.vat') }}</th>
                                <th style="width:130px" class="text-right">{{ __('purchases.sale_inc') }}</th>
                                <th style="width:120px" class="text-right">{{ __('purchases.profit') }}</th>
                                <th style="width:140px" class="text-right">{{ __('purchases.total') }}</th>
                            </tr>
                        </thead>
                        <tbody id="productDetails">
                            <tr class="product-row" data-idx="{{ $purchaseData->id ?? 0 }}" data-purchase-id="{{ $purchaseData->id ?? '' }}" data-current-stock="{{ $totalStock ?? ($stock->currentStock ?? 0) }}">
                                <td>
                                    @php
                                        $selectedProduct = $productList->firstWhere('id', $purchaseData->productName);
                                        $productDisplay = $selectedProduct ? $selectedProduct->name : '';
                                        if($selectedProduct) {
                                            $brand = \App\Models\Brand::find($selectedProduct->brand);
                                            if($brand) {
                                                $productDisplay .= ' - ' . $brand->name;
                                            }
                                        }
                                    @endphp
                                    <div class="d-flex align-items-center">
                                        @if($selectedProduct?->image_thumbnail_url)
                                            <img src="{{ $selectedProduct->image_thumbnail_url }}" alt="" class="rn-entity-thumbnail mr-3" loading="lazy" />
                                        @else
                                            <span class="rn-entity-thumbnail rn-entity-thumbnail--fallback mr-3" aria-hidden="true">{{ mb_substr($productDisplay, 0, 1) }}</span>
                                        @endif
                                        <div>
                                            <div class="font-weight-bold">{{ $productDisplay }}</div>
                                            <div class="small text-muted">{{ __('common.invoice') }}: {{ $purchaseData->invoice ?? $generatedInvoice ?? '-' }} | Ref: {{ $purchaseData->reference ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-2">
                                        <button type="button" class="btn btn-outline-success btn-sm" data-toggle="modal" data-target="#serialModal" data-purchase-id="{{ $purchaseData->id ?? '' }}">{{ __('purchases.add_view') }}</button>
                                    </div>
                                    <div>
                                        @if(!empty($serials) && $serials->count() > 0)
                                            <ul class="list-unstyled mb-0 small">
                                                @foreach($serials as $s)
                                                    @php $label = $s->serialNumber ?? $s->serial ?? $s->serial_number ?? $s->number ?? $s->id; @endphp
                                                    <li class="mb-1">
                                                        <span class="text-truncate d-inline-block w-100">{{ $label }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="text-muted small">{{ __('purchases.no_serials') }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <input type="number" class="form-control form-control-sm text-center" id="quantity" name="quantity" min="1" step="1" value="{{ $purchaseData->qty ?? '' }}" data-onkeyup="totalPriceCalculate()" />
                                </td>
                                <td class="text-center align-middle">
                                    <input type="number" class="form-control form-control-sm text-center" id="currentStock" value="{{ $totalStock ?? ($stock->currentStock ?? 0) }}" readonly />
                                    <input type="hidden" name="currentStock" value="{{ $totalStock ?? ($stock->currentStock ?? 0) }}" />
                                </td>
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-sm text-right" id="buyPrice" name="buyPrice" value="{{ $purchaseData->buyPrice ?? '' }}" data-onkeyup="totalPriceCalculate()" step="0.01" />
                                </td>
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-sm text-right" id="salePriceExVat" name="salePriceExVat" value="{{ $purchaseData->salePriceExVat ?? '' }}" data-onkeyup="priceCalculation()" step="0.01" />
                                </td>
                                <td class="align-middle text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div class="form-check mr-2">
                                            <input type="checkbox" class="form-check-input include-vat" id="includeVat_edit" {{ (isset($purchaseData) && $purchaseData->vatStatus) ? 'checked' : '' }} />
                                            <input type="hidden" name="vatStatus" id="vatStatus_hidden" value="{{ (isset($purchaseData) && $purchaseData->vatStatus) ? 1 : 0 }}" />
                                            <label class="form-check-label" for="includeVat_edit">{{ __('purchases.yes') }}</label>
                                        </div>
                                        <span class="vat-badge" id="vatBadge_edit" style="display:{{ (isset($purchaseData) && $purchaseData->vatStatus) ? 'inline-block' : 'none' }}" title="{{ __('purchases.vat') }}">{{ __('purchases.vat') }}</span>
                                        <div style="display:flex; flex-direction:column; margin-left:8px;">
                                            <input type="number" name="vatPercent[]" class="form-control vat-percent form-control-sm" step="0.01" style="width:100px;" value="{{ $purchaseData->vatStatus ? ($purchaseData->vatPercent ?? 10) : 10 }}" {{ (isset($purchaseData) && $purchaseData->vatStatus) ? '' : 'disabled' }} />
                                            <div class="small text-muted row-vat-amount" id="rowVat_edit"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-sm text-right sale-price-inc" id="salePriceInVat" name="salePriceInVat" value="{{ $purchaseData->salePriceInVat ?? '' }}" readonly />
                                </td>
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-sm text-right" id="profitMargin" name="profitMargin" value="{{ $purchaseData->profit ?? '' }}" data-onkeyup="profitCalculation()" step="0.01" />
                                </td>
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-sm text-right font-weight-bold" id="totalAmount" name="totalAmount" value="{{ $purchaseData->totalAmount ?? '' }}" readonly />
                                </td>
                            </tr>

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
                            <h6 class="card-title">{{ __('purchases.other_details') }}</h6>
                        </div>
                        <div class="mb-3 table-responsive product-table">
                            <table class="table mb-0 table-bordered rounded-0 rn-table-pro">
                                <thead class="bg-white text-uppercase">
                                    <tr>
                                        <th>{{ __('purchases.discount_type') }}</th>
                                        <th>{{ __('purchases.discount_amount') }}</th>
                                        <th>{{ __('purchases.discount_parcent') }}</th>
                                        <th>{{ __('purchases.grand_total') }}</th>
                                        <th>{{ __('purchases.paid_amount') }}</th>
                                        <th>{{ __('purchases.due_amount') }}</th>
                                        <th>{{ __('purchases.special_note') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="discountDetails">
                                    <tr>
                                        <td>
                                            <select name="discountStatus" id="discountStatus" data-onchange="discountType()" class="form-control">
                                                <option value="">-</option>
                                                <option value="1" {{ (!empty($purchaseData) && $purchaseData->disType == 1) ? 'selected' : '' }}>{{ __('purchases.amount') }}</option>
                                                <option value="2" {{ (!empty($purchaseData) && $purchaseData->disType == 2) ? 'selected' : '' }}>{{ __('purchases.parcent') }}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="discountAmount" data-onkeyup="discountAmountChange()" name="discountAmount" value="{{ $purchaseData->disAmount ?? '' }}"  />
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="discountPercent" data-onkeyup="discountPercentChange()" name="discountPercent" value="{{ $purchaseData->disParcent ?? '' }}"  />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="grandTotal" name="grandTotal" value="{{ $purchaseData->grandTotal ?? '' }}"  />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="paidAmount" name="paidAmount" value="{{ $purchaseData->paidAmount ?? 0 }}" min="0" step="0.01" data-onkeyup="dueCalculate()"  />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="dueAmount" name="dueAmount" value="{{ $purchaseData->dueAmount ?? '' }}" min="0" step="0.01" readonly  />
                                        </td>
                                        <td>
                                            <textarea class="form-control" id="specialNote" name="specialNote" >{{ $purchaseData->specialNote ?? '' }}</textarea>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--  serial number model -->

    <div class="modal fade" id="serialModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="serialModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fs-5">{{ __('purchases.new_serial') }}</h6>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="{{ __('purchases.close') }}"></button>
                </div>
                <div class="modal-body" id="resetSerial">
                    {{-- Existing serials for this purchase (now editable) --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('purchases.existing_serials') }}</label>
                        <div id="existingSerialsList">
                            @if(!empty($serials) && $serials->count() > 0)
                                @foreach($serials as $s)
                                    @php $label = $s->serialNumber ?? $s->serial ?? $s->serial_number ?? $s->number ?? $s->id; @endphp
                                    <div id="serial-row-{{ $s->id }}" class="serial-input-row d-flex mb-2 align-items-center existing-serial-row" data-serial-id="{{ $s->id }}">
                                        <input type="text" class="form-control flex-grow-1 existing-serial-input" data-serial-id="{{ $s->id }}" data-original="{{ $label }}" value="{{ $label }}" style="margin-right:12px;" />
                                        <div class="d-flex">
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-serial" data-id="{{ $s->id }}">{{ __('purchases.remove') }}</button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-muted small" id="existingSerialsEmpty">{{ __('purchases.no_serials_for_this_purchase') }}</div>
                            @endif
                        </div>
                    </div>

                    <hr />

                    <div class="p-0">
                        <label for="serialNumber" class="form-label">{{ __('purchases.add_serial_number_s') }}</label>
                    </div>
                    <div id="serialNumberBox">
                        <div class="serial-input-row d-flex mb-3 align-items-center">
                            <input type="text" class="form-control flex-grow-1" name="serialNumber[]" placeholder="{{ __('common.enter_serial_number') }}" style="margin-right:12px;" />
                            <div class="d-flex">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-serial">{{ __('purchases.remove') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-info auto-gen-serial-row" style="margin-left:6px;">{{ __('purchases.auto') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div>
                            <button type="button" class="btn btn-success btn-sm rounded-0" id="add-serial">{{ __('purchases.add_serial') }}</button>
                            <button type="button" class="btn btn-info btn-sm rounded-0" id="autoGenerateSerials" style="margin-left:8px;">{{ __('purchases.auto_generate_serials') }}</button>
                            <button type="button" class="btn btn-danger btn-sm rounded-0" id="removeAllSerials" style="margin-left:8px;">{{ __('purchases.remove_all') }}</button>
                        </div>
                        <small id="serialCapacityInfo" class="text-danger ml-3" style="display:none;">{{ __('purchases.serial_limit_reached_for_current_quantity') }}</small>
                    </div>
                </div>
                    <div class="modal-footer">
                    <button type="button" data-onclick="resetSerial()" class="btn btn-warning">{{ __('purchases.clear') }}</button>
                    <button type="button" class="btn btn-primary" id="saveSerials">{{ __('purchases.save') }}</button>
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('purchases.cancel') }}</button>
                </div>
            </div>
        </div>
    </div>

        <!-- actions moved to header toolbar -->
</form>


<!-- Page end  -->
<script>
document.addEventListener('DOMContentLoaded', function(){
    try{
        // Wire inputs to centralized recalculation where available
        var qty = document.getElementById('quantity'); if(qty) qty.addEventListener('input', function(e){ try{ if(typeof recalcPurchaseRow === 'function') recalcPurchaseRow(e); }catch(_){} });
        var buy = document.getElementById('buyPrice'); if(buy) buy.addEventListener('input', function(e){ try{ if(typeof recalcPurchaseRow === 'function') recalcPurchaseRow(e); }catch(_){} });
        var sale = document.getElementById('salePriceExVat'); if(sale) sale.addEventListener('input', function(e){ try{ if(typeof recalcPurchaseRow === 'function') recalcPurchaseRow(e); }catch(_){} });
        var vat = document.getElementById('vatStatus'); if(vat) vat.addEventListener('change', function(e){ try{ if(typeof recalcPurchaseRow === 'function') recalcPurchaseRow(e); }catch(_){} });

        // When serial modal opens, set data-current-idx so global syncSerialsFromModal can find the row
        var serialButtons = document.querySelectorAll('[data-target="#serialModal"][data-purchase-id]');
        serialButtons.forEach(function(btn){ btn.addEventListener('click', function(){
            try{
                var modal = document.getElementById('serialModal');
                if(modal) {
                    modal.setAttribute('data-current-idx', btn.getAttribute('data-purchase-id') || '');
                    // Enforce capacity immediately after setting the current idx
                    setTimeout(function(){ enforceSerialCapacity(modal); }, 100);
                }
            }catch(e){ console.warn('Serial button click handler error:', e); }
        }); });

        // Sync VAT checkbox with hidden vatStatus input
        var vatCheckbox = document.getElementById('includeVat_edit');
        var vatHidden = document.getElementById('vatStatus_hidden');
        if(vatCheckbox && vatHidden){
            vatCheckbox.addEventListener('change', function(){
                vatHidden.value = this.checked ? 1 : 0;
                console.log('Synced vatStatus to ' + vatHidden.value);
            });
        }

        // Form submit UX: disable save button and show saving text and sync VAT before submit
        var form = document.getElementById('savePurchase');
        if(form){
            form.addEventListener('submit', function(ev){
                try{
                    // Sync VAT checkbox before submission
                    if(vatCheckbox && vatHidden){
                        vatHidden.value = vatCheckbox.checked ? 1 : 0;
                        console.log('Before submit: synced vatStatus to ' + vatHidden.value);
                    }
                    var b = form.querySelector('button[type="submit"]');
                    if(b){ b.disabled = true; b.setAttribute('data-old', b.innerHTML); b.innerHTML = 'Saving...'; }
                }catch(e){}
            });
        }

        // If saveSerials button clicked inside modal, save and close
        var saveSerialsBtn = document.getElementById('saveSerials');
        if(saveSerialsBtn){ saveSerialsBtn.addEventListener('click', function(){ try{ if(typeof saveSerialsFromModal === 'function'){ saveSerialsFromModal(); } }catch(e){ console.warn('saveSerials click', e); } }); }

        // --- Product select enabling parity with Add Purchase ---
        try{
            var productSelect = document.getElementById('productName');
            var supplierSelect = document.getElementById('supplierName');
            if(productSelect){
                // store original options so we can restore them when a supplier is chosen
                if(!productSelect.dataset.origOptions) productSelect.dataset.origOptions = productSelect.innerHTML;
                // if supplier not selected initially, disable product select and show placeholder
                if(supplierSelect && (!supplierSelect.value || supplierSelect.value === '')){
                    productSelect.innerHTML = '<option value="">Please select a Supplier First</option>';
                    productSelect.disabled = true;
                }
                // when supplier changes, try to fetch supplier-filtered product options (public AJAX)
                if(supplierSelect){
                    // template URL (blade route helper) — we'll replace __ID__ with the supplier id
                    var productsUrlTemplate = '{!! route("ajax.customer.products.public", ["id" => "__ID__"]) !!}';
                    supplierSelect.addEventListener('change', function(){
                        try{
                            var sid = supplierSelect.value || '';
                            if(sid !== ''){
                                productSelect.disabled = false;
                                // try fetch supplier-specific options
                                (function(sidLocal){
                                    try{
                                        var url = productsUrlTemplate.replace('__ID__', encodeURIComponent(sidLocal));
                                        fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                            .then(function(resp){
                                                if(!resp.ok) throw new Error('Network response not ok');
                                                var ct = resp.headers.get('content-type') || '';
                                                if(ct.indexOf('application/json') !== -1){
                                                    // if JSON returned, try to extract html property if present
                                                    return resp.json().then(function(j){ return j.html || j.options || ''; });
                                                }
                                                return resp.text();
                                            })
                                            .then(function(body){
                                                if(!body || body.trim() === ''){
                                                    // fallback to original options if server returned nothing
                                                    if(productSelect.dataset.origOptions) productSelect.innerHTML = productSelect.dataset.origOptions;
                                                }else{
                                                    productSelect.innerHTML = body;
                                                }
                                            })
                                            .catch(function(e){
                                                console.warn('Failed to load products for supplier', e);
                                                if(productSelect.dataset.origOptions) productSelect.innerHTML = productSelect.dataset.origOptions;
                                            });
                                    }catch(e){}
                                })(sid);
                            }else{
                                productSelect.disabled = true;
                                productSelect.innerHTML = '<option value="">Please select a Supplier First</option>';
                            }
                        }catch(er){}
                    });
                }
            }
        }catch(e){}
    }catch(e){ console.warn('editPurchase init failed', e); }
});
</script>
<!-- new supplier Modal -->
<div class="modal fade" id="supplier" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="supplier" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="supplier">{{ __('purchases.create_supplier') }}</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="{{ __('purchases.close') }}"></button>
            </div>
            <div class="modal-body">
                    <div class="card-body">
                <div class="row">
                    <form action="#" method="POST" id="supplierForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('common.name') }} *</label>
                                    <input type="text" class="form-control" placeholder="{{ __('common.enter_name') }}"  id="fullName" name="fullName"  required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('purchases.email') }}</label>
                                    <input type="email" id="userMail" class="form-control" placeholder="{{ __('common.enter_email') }}" name="mail" />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('purchases.phone_number') }}</label>
                                    <input type="text" class="form-control" placeholder="{{ __('common.enter_phone_number') }}" id="mobile" name="mobile" />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="inputState" class="form-label">{{ __('purchases.country') }}</label>

                                    <input type="text" class="form-control" placeholder="{{ __('common.enter_the_country') }}" id="country" name="country" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="inputState" class="form-label">{{ __('purchases.state') }}</label>

                                    <input type="text" class="form-control" placeholder="{{ __('common.enter_the_state') }}" id="state" name="state" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="inputState" class="form-label">{{ __('purchases.city') }}</label>

                                    <input type="text" class="form-control" placeholder="{{ __('common.enter_the_city') }}" id="city" name="city" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="area" class="form-label">{{ __('purchases.area') }}</label>

                                    <input type="text" class="form-control" placeholder="{{ __('common.enter_the_area') }}" id="area" name="area" />
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary mr-2" id="add-supplier">{{ __('purchases.add_supplier') }}</button>
                        <button type="reset" class="btn btn-danger">{{ __('purchases.reset') }}</button>
                    </form>
                </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('purchases.cancle') }}</button>
            </div>
        </div>
    </div>
</div>
<!-- new product Modal -->
<div class="modal fade" id="newProduct" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="newProduct" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5">{{ __('purchases.create_product') }}</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="{{ __('purchases.close') }}"></button>
            </div>
            <div class="modal-body">
                    <div class="card-body">

                <div class="row">
                    <form action="#" method="POST" id="productForm">
                    @csrf
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('common.product_name') }} *</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_name') }}" id="productNameModal" name="productName" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('purchases.brand_name') }} *</label>
                                <label for="brandName" class="form-label"></label>
                                <select id="brandName" class="form-control" >
                                    <!--  form option show proccessing -->
                                    <option value="">{{ __('purchases.select') }}</option>
                                    @if(!empty($brandList) && count($brandList)>0)
                                        @foreach($brandList as $brandData)
                                        <option value="{{$brandData->id}}">{{$brandData->name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1 mt-4 p-0">
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#createBrand" ><i class="las la-plus mr-2"></i>{{ __('purchases.brand') }}</button>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('purchases.product_category') }}</label>
                                <label for="categoryName" class="form-label"></label>
                                <select id="categoryName" class="form-control" >

                                  <!--  form option show proccessing -->
                                    <option value="">{{ __('purchases.select') }}</option>
                                  @if(!empty($categoryList) && count($categoryList)>0)
                                  @foreach($categoryList as $categoryData)
                                    <option value="{{$categoryData->id}}">{{$categoryData->name}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 mt-4 p-0">
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#categoryModal" ><i class="las la-plus mr-2"></i>{{ __('purchases.category') }}</button>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('purchases.product_unit') }}</label>
                                <label for="unit" class="form-label"></label>
                                <select id="unit" class="form-control" >

                                  <!--  form option show proccessing -->
                                    <option value="">{{ __('purchases.select') }}</option>
                                  @if(!empty($productUnitList) && count($productUnitList)>0)
                                  @foreach($productUnitList as $productUnitData)
                                    <option value="{{$productUnitData->id}}">{{$productUnitData->name}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 mt-4 p-0">
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#productUnitModal" ><i class="las la-plus mr-2"></i>{{ __('purchases.product_unit') }}</button>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="quantityName" class="form-label">{{ __('purchases.alert_quantity') }}</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.optional') }}" id="quantityName"  />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="detailsName" class="form-label">{{ __('purchases.deatils') }}</label>
                                <input type="text" class="form-control" id="detailsName" placeholder="{{ __('common.optional') }}"  />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="barCodeNum" class="form-label">{{ __('purchases.barcode') }}</label>
                                <input type="text" class="form-control" id="barCodeNum" placeholder="{{ __('common.optional') }}"/>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary mt-4 mr-2" id="add-product"> {{ __('purchases.add_product') }}</button>
                    <button type="reset" class="btn btn-danger mt-4 mr-2">{{ __('purchases.reset') }}</button>
                </form>
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('purchases.cancel') }}</button>
        </div>
    </div>
</div>

<!-- brand modal -->
<div class="modal fade" id="createBrand" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="createBrand" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">{{ __('purchases.creat_brand') }}</h6>
                                        <button type="button" class="btn-close"  data-onclick="closeModel('createBrand','brandForm')" aria-label="{{ __('purchases.close') }}"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="POST" id="brandForm">
                    @csrf
                    <div class="mb-3">
                        <label for="NewBrand" class="form-label">{{ __('purchases.brand_name') }}</label>
                        <input type="text" class="form-control" id="NewBrand" name="NewBrand" placeholder="{{ __('common.enter_brand_name') }}" />
                    </div>

                <button type="button" class="btn btn-primary" id="saveBrand">{{ __('purchases.save') }}</button>
                </form>
            </div>
            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-onclick="closeModel('createBrand','brandForm')">{{ __('purchases.cancel') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- category modal -->
<div class="modal fade" id="categoryModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="categoryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">{{ __('purchases.creat_category') }}</h6>
                <button type="button" class="btn-close"  data-onclick="closeModel('categoryModal','categoryForm')" aria-label="{{ __('purchases.close') }}"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="categoryForm">
                    @csrf
                <div class="mb-3">
                    <label for="NewCategory" class="form-label">{{ __('purchases.category') }}</label>
                    <input type="text" class="form-control" id="NewCategory" name="NewCategory" placeholder="{{ __('common.enter_category_name') }}" />
                </div>
                <button type="button" class="btn btn-primary" id="add-category">{{ __('purchases.save') }}</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-onclick="closeModel('categoryModal','categoryForm')">{{ __('purchases.cancel') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- product unit -->

<div class="modal fade" id="productUnitModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="productUnitModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5" >{{ __('purchases.preoduct_unit') }}</h6>
                <button type="button" class="btn-close" data-onclick="closeModel('productUnitModal','productUnitForm')" aria-label="{{ __('purchases.close') }}"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="productUnitForm">
                    @csrf
                <div class="mb-3">
                    <label for="productUnitName" class="form-label">{{ __('purchases.product_unit') }}</label>
                    <input type="text" class="form-control" id="productUnitName" name="productUnitName" placeholder="{{ __('common.enter_product_unit_name') }}" />
                </div>
                <button type="button" class="btn btn-primary" id="add-productUnit">{{ __('purchases.save') }}</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-onclick="closeModel('productUnitModal','productUnitForm')">{{ __('purchases.cancel') }}</button>
            </div>
        </div>
    </div>
</div>






@endsection

@include('customScript')
<script>
document.querySelector('[data-rn-product-picker][data-mode="purchase"]')?.addEventListener('rn:product-picker:selected', function (event) {
    const id = String(event.detail.product_id);
    const select = document.getElementById('productName');
    const hidden = document.getElementById('productNameHidden');
    if (!select || !hidden) return;
    let option = [...select.options].find(item => item.value === id);
    if (!option) { option = new Option(event.detail.name, id); select.add(option); }
    select.value = id;
    hidden.value = id;
    select.dispatchEvent(new Event('change', { bubbles: true }));
});
</script>
