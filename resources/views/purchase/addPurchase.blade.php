@extends('include') @section('backTitle') purchase @endsection @section('container')
@php
    // Load currency symbol from BusinessSetup or default to BDT '৳'
    $__businessSetup = \App\Models\BusinessSetup::orderBy('id','desc')->first();
    $currencySymbol = ($__businessSetup && $__businessSetup->currencySymbol) ? $__businessSetup->currencySymbol : '৳';
@endphp
<style>
    /* Visual highlight when VAT is enabled for a row */
    tr.product-row.vat-enabled{ background-color: #fffbe6; }
    .vat-badge{ display:inline-block; margin-left:6px; padding:2px 6px; font-size:11px; border-radius:3px; background:#17a2b8; color:#fff; }
    .row-vat-amount{ margin-top:4px; font-size:11px; color:#6c757d; }
</style>
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="{{ route('savePurchase') }}" class="row" method="POST" id="savePurchase" data-onsubmit="handleFormSubmit">
    @csrf
    <div class="col-12">
        <div class="row">
            <div class="col-md-12 col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ __('purchases.create_new_purchase') }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            @include('partials.business_selector', ['businesses' => $businesses ?? [] , 'selectedBusinessId' => null])
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date" class="form-label">{{ __('common.date') }} *</label>
                                    <input type="date" class="form-control" id="date" name="purchaseDate" value="{{ old('purchaseDate', date('Y-m-d')) }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="supplierName" class="form-label">{{ __('common.supplier') }} *</label>
                                    <select id="supplierName" name="supplierName" data-onchange="actProductList()" class="form-control" required>
                                    <option value="">-</option>
                                    <!--  form option show proccessing -->
                                    @if(!empty($supplierList) && count($supplierList)>0)
                                    @foreach($supplierList as $supplierData)
                                        <option value="{{$supplierData->id}}">{{$supplierData->name}}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 mt-4 p-0">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#supplier"><i class="las la-plus mr-2"></i>{{ __('purchases.new_supplier') }}</button>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="invoice" class="form-label">{{ __('common.invoice') }} *</label>
                                    <input type="text" class="form-control" id="invoice" name="invoiceData" value="{{ $generatedInvoice ?? '' }}" readonly placeholder="{{ __('common.auto_generated_invoice') }}" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="productName" class="form-label">{{ __('common.product') }} *</label>
                                    <!-- productAdd is used to pick a product to append as a purchase row. Disabled until supplier selected. -->
                                    <select id="productName" name="productAdd" class="form-control js-product-select">
                                        <option value="">{{ __('purchases.please_select_a_supplier_first') }}</option>
                                    @if(!empty($productList) && count($productList)>0)
                                        @php
                                            // Render product options into a data container so JS can restore them when supplier is chosen
                                            $productOptions = '';
                                        @endphp
                                        @foreach($productList as $productData)
                                            @php
                                                $brand = \App\Models\Brand::find($productData->brand);
                                                $brandName = $brand ? ' - ' . $brand->name : '';
                                                $productOptions .= '<option value="'. $productData->id .'">'. htmlspecialchars($productData->name . $brandName) .'</option>';
                                            @endphp
                                        @endforeach
                                        {!! '<!--product-options-start-->' . $productOptions . '<!--product-options-end-->' !!}
                                    @endif
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2 mt-4 p-0">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#newProduct"><i class="las la-plus mr-2"></i>{{ __('purchases.new_product') }}</button>
                            </div>
                            <div class="col-md-2 mt-4 p-0">
                                <button type="button" id="addProductRow" class="btn btn-primary btn-sm">{{ __('purchases.add_to_list') }}</button>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="reference" class="form-label">{{ __('common.reference') }} *</label>
                                    <input type="text" class="form-control" id="reference" name="refData" value="N/A" placeholder="{{ __('common.n_a_you_can_change') }}" />
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
                    <table class="table mb-0 table-bordered rounded-0 rn-table-pro">
                                <thead class="bg-white text-uppercase">
                            <tr>
                                <th>{{ __('purchases.product_name') }}</th>
                                <th>{{ __('purchases.serial') }}</th>
                                <th>{{ __('purchases.purchase_qty') }}</th>
                                <th>{{ __('purchases.current_stock') }}</th>
                                <th>{{ __('common.buy_price') }} ({{ $currencySymbol }})</th>
                                <th>{{ __('common.sale_price_ex_vat') }} ({{ $currencySymbol }})</th>
                                <th>{{ __('purchases.vat_include') }}</th>
                                <th>{{ __('common.sale_price_inc_vat') }} ({{ $currencySymbol }})</th>
                                <th>{{ __('purchases.profit') }}</th>
                                <th>{{ __('common.total_price') }} ({{ $currencySymbol }})</th>
                            </tr>
                        </thead>
                        <tbody id="productDetails">
                            <!-- purchase rows will be appended here -->
                        </tbody>
                    </table>
                    <!-- Row template (used by JS) -->
                    <template id="purchase-row-template">
                        <tr class="product-row" data-idx="__IDX__">
                            <td width="20%">
                                <input type="hidden" name="productName[]" value="__PRODUCT_ID__" />
                                <input type="text" class="form-control" name="selectProductName[]" value="__PRODUCT_NAME__" readonly />
                            </td>
                            <td width="8%">
                                <button type="button" class="btn btn-sm btn-outline-secondary open-serials" data-idx="__IDX__">{{ __('purchases.serials') }}</button>
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control quantity" id="quantity__IDX__" name="quantity[]" min="1" step="1" placeholder="{{ __('purchases.qty') }}" />
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control current-stock" id="currentStock__IDX__" name="currentStock[]" readonly />
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control" id="buyPrice__IDX__" name="buyPrice[]" placeholder="{{ __('common.buy_price') }}" />
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control sale-price" id="salePriceExVat__IDX__" name="salePriceExVat[]" placeholder="{{ __('common.sale_price_ex_vat') }}" />
                            </td>
                            <td width="9%">
                                    <div class="d-flex align-items-center">
                                    <div class="form-check mr-2">
                                        <input type="checkbox" class="form-check-input include-vat" id="includeVat__IDX__" />
                                        <label class="form-check-label" for="includeVat__IDX__">{{ __('purchases.yes') }}</label>
                                    </div>
                                    <span class="vat-badge" id="vatBadge__IDX__" style="display:none" title="">{{ __('purchases.vat') }}</span>
                                    <!-- Numeric VAT percent per-row. A small readonly text below shows the computed VAT amount for this row. -->
                                    <div style="display:flex; flex-direction:column;">
                                        <input type="number" name="vatPercent[]" class="form-control vat-percent" step="0.01" style="margin-top:0; width:100px;" disabled placeholder="%" />
                                        <div class="small text-muted row-vat-amount" id="rowVat__IDX__"></div>
                                    </div>
                                </div>
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control sale-price-inc" id="salePriceInVat__IDX__" name="salePriceInVat[]" readonly placeholder="{{ __('common.inc_vat') }}" />
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control" id="profitMargin__IDX__" name="profitMargin[]" readonly placeholder="%" />
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control" id="totalAmount__IDX__" name="totalAmount[]" readonly placeholder="{{ __('purchases.total') }}" />
                            </td>
                            <td width="4%">
                                <button type="button" class="btn btn-danger btn-sm remove-row">{{ __('purchases.remove') }}</button>
                            </td>
                        </tr>
                    </template>
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
                        <div class="row mb-3">
                            <!-- Global VAT controls removed: VAT is now managed per-row via the 'Vat Include' column and 'VAT %' input on each product row. -->
                        </div>
                        <div class="mb-3 table-responsive product-table">
                            <table class="table mb-0 table-bordered rounded-0 rn-table-pro">
                                <thead class="bg-white text-uppercase">
                                    <tr>
                                        <th>{{ __('purchases.discount_type') }}</th>
                                        <th>{{ __('common.discount_amount') }} ({{ $currencySymbol }})</th>
                                        <th>{{ __('purchases.discount_parcent') }}</th>
                                        <th>{{ __('common.grand_total') }} ({{ $currencySymbol }})</th>
                                        <th>{{ __('common.paid_amount') }} ({{ $currencySymbol }})</th>
                                        <th>{{ __('common.due_amount') }} ({{ $currencySymbol }})</th>
                                        <th>{{ __('purchases.special_note') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="discountDetails">
                                    <tr>
                                        <td>
                                            <select name="discountStatus" id="discountStatus" data-onchange="discountType()" class="form-control">
                                                <option value="">-</option>
                                                <option value="1">{{ __('purchases.amount') }}</option>
                                                <option value="2">{{ __('purchases.parcent') }}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="discountAmount" data-onkeyup="discountAmountChange()" name="discountAmount" />
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="discountPercent" data-onkeyup="discountPercentChange()" name="discountPercent" />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="grandTotal" name="grandTotal" readonly />
                                            <small class="text-muted">Grand total is calculated from Buy Price × Qty. Sale Price affects profit only.</small>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="paidAmount" name="paidAmount" value="0" min="0" step="0.01" data-onkeyup="dueCalculate()" />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="dueAmount" name="dueAmount" readonly min="0" step="0.01" />
                                        </td>
                                        <td>
                                            <textarea class="form-control" id="specialNote" name="specialNote"></textarea>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="saveButton" class="mt-2">
                            <button class="btn btn-primary btn-sm" type="submit">{{ __('purchases.save') }}</button>
                            <button class="btn btn-warning btn-sm" type="button" data-onclick="debugForm()">{{ __('purchases.debug') }}</button>
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
                    <div class="p-0">
                        <label for="serialNumber" class="form-label">{{ __('purchases.serial_number') }}</label>
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
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <div>
                        <button type="button" class="btn btn-success btn-sm rounded-0" id="add-serial">{{ __('purchases.add_serial') }}</button>
                        <button type="button" class="btn btn-info btn-sm rounded-0" id="autoGenerateSerials" style="margin-left:8px;">{{ __('purchases.auto_generate_serials') }}</button>
                    </div>
                    <div>
                        <button type="button" data-onclick="resetSerial()" class="btn btn-warning" data-dismiss="modal">{{ __('purchases.clear') }}</button>
                        <button type="button" id="saveSerials" class="btn btn-primary" data-dismiss="modal">{{ __('purchases.save') }}</button>
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('purchases.cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>


<!-- Page end  -->
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
                    <form action="{{ route('createSupplier') }}" method="GET" id="supplierForm" data-ajax="true" data-target="#supplierName" data-modal-id="supplier">
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
                                    <input type="email" id="userMail" class="form-control" placeholder="{{ __('common.enter_email') }}" name="email" />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('purchases.phone_number') }}</label>
                                    <input type="text" class="form-control" placeholder="{{ __('common.enter_phone_number') }}" id="mobile" name="phoneNumber" />
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
                        <input type="hidden" name="openingBalance" value="0" />
                        <button type="submit" class="btn btn-primary mr-2" id="add-supplier">{{ __('purchases.add_supplier') }}</button>
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
                    <form action="{{ route('createProduct') }}" method="GET" id="productForm" data-ajax="true" data-target="#productName" data-modal-id="newProduct">
                    @csrf
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('common.product_name') }} *</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_name') }}" id="productNameModal" name="fullName" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('purchases.brand_name') }} *</label>
                                <label for="brandName" class="form-label"></label>
                                <select id="brandName" class="form-control" name="brand">
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
                                <select id="categoryName" class="form-control" name="category">

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
                                <select id="unit" class="form-control" name="unitName">

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
                                <input type="text" class="form-control" placeholder="{{ __('common.optional') }}" id="quantityName" name="quantity" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="detailsName" class="form-label">{{ __('purchases.deatils') }}</label>
                                <input type="text" class="form-control" id="detailsName" name="details" placeholder="{{ __('common.optional') }}"  />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="barCodeNum" class="form-label">{{ __('purchases.barcode') }}</label>
                                <input type="text" class="form-control" id="barCodeNum" name="barCode" placeholder="{{ __('common.optional') }}"/>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-4 mr-2" id="add-product"> {{ __('purchases.add_product') }}</button>
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
                <form action="{{ route('createBrand') }}" method="GET" id="brandForm" data-ajax="true" data-target="#brandName" data-modal-id="createBrand">
                    @csrf
                    <div class="mb-3">
                        <label for="NewBrand" class="form-label">{{ __('purchases.brand_name') }}</label>
                        <input type="text" class="form-control" id="NewBrand" name="name" placeholder="{{ __('common.enter_brand_name') }}" />
                    </div>
                <button type="submit" class="btn btn-primary" id="saveBrand">{{ __('purchases.save') }}</button>
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
                <form action="{{ route('createCategory') }}" method="GET" id="categoryForm" data-ajax="true" data-target="#categoryName" data-modal-id="categoryModal">
                    @csrf
                <div class="mb-3">
                    <label for="NewCategory" class="form-label">{{ __('purchases.category') }}</label>
                    <input type="text" class="form-control" id="NewCategory" name="name" placeholder="{{ __('common.enter_category_name') }}" />
                </div>
                <button type="submit" class="btn btn-primary" id="add-category">{{ __('purchases.save') }}</button>
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
                <form action="{{ route('createProductUnit') }}" method="GET" id="productUnitForm" data-ajax="true" data-target="#unit" data-modal-id="productUnitModal">
                    @csrf
                <div class="mb-3">
                    <label for="productUnitName" class="form-label">{{ __('purchases.product_unit') }}</label>
                    <input type="text" class="form-control" id="productUnitName" name="name" placeholder="{{ __('common.enter_product_unit_name') }}" />
                </div>
                <button type="submit" class="btn btn-primary" id="add-productUnit">{{ __('purchases.save') }}</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-onclick="closeModel('productUnitModal','productUnitForm')">{{ __('purchases.cancel') }}</button>
            </div>
        </div>
    </div>
</div>






@endsection

@section('scripts')
    @include('customScript')
@endsection
