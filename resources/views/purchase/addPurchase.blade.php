@extends('include') @section('backTitle') {{ __('navigation.new_purchase') }} @endsection @section('container')
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
<link rel="stylesheet" href="{{ asset('css/new-purchase.css') }}?v={{ filemtime(public_path('css/new-purchase.css')) }}">
<div class="col-12 rn-new-purchase-shell"><form action="{{ route('savePurchase') }}" class="rn-new-purchase" method="POST" id="savePurchase" data-onsubmit="handleFormSubmit" data-leave-title="{{ __('purchases.unsaved_title') }}" data-leave-message="{{ __('purchases.unsaved_message') }}" data-leave-confirm="{{ __('purchases.leave_page') }}" data-leave-cancel="{{ __('common.cancel') }}">
 @csrf
 @include('purchase.new-purchase.header')
 @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
 <div class="rn-purchase-layout"><div class="rn-purchase-workspace">
 @include('purchase.new-purchase.information')
 @include('purchase.new-purchase.products')
 @include('purchase.new-purchase.items')
 </div><aside class="rn-purchase-billing" aria-label="{{ __('purchases.purchase_summary') }}">@include('purchase.new-purchase.summary')</aside></div>
 @include('purchase.serialModal')
</form></div>


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
    <script src="{{ asset('js/new-purchase.js') }}?v={{ filemtime(public_path('js/new-purchase.js')) }}"></script>
@endsection
