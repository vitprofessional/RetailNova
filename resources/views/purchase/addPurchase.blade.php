@extends('include') @section('backTitle') {{ __('navigation.new_purchase') }} @endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/new-purchase.css') }}?v={{ filemtime(public_path('css/new-purchase.css')) }}">
<link rel="stylesheet" href="{{ asset('css/inventory-products.css') }}?v={{ filemtime(public_path('css/inventory-products.css')) }}">
<link rel="stylesheet" href="{{ asset('css/q6-purchase.css') }}?v={{ filemtime(public_path('css/q6-purchase.css')) }}">
@endpush
@section('container')
<style>
    /* Visual highlight when VAT is enabled for a row */
    tr.product-row.vat-enabled{ background-color: #fffbe6; }
    .vat-badge{ display:inline-block; margin-left:6px; padding:2px 6px; font-size:11px; border-radius:3px; background:#17a2b8; color:#fff; }
    .row-vat-amount{ margin-top:4px; font-size:11px; color:#6c757d; }
</style>
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="col-12 rn-new-purchase-shell"><form action="{{ route('savePurchase') }}" class="rn-new-purchase rn-m-form" method="POST" id="savePurchase" data-onsubmit="handleFormSubmit" data-leave-title="{{ __('purchases.unsaved_title') }}" data-leave-message="{{ __('purchases.unsaved_message') }}" data-leave-confirm="{{ __('purchases.leave_page') }}" data-leave-cancel="{{ __('common.cancel') }}">
 @csrf
 @include('purchase.new-purchase.header')
 @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
 @include('purchase.new-purchase.mobile-flow')
 <div class="rn-purchase-layout"><div class="rn-purchase-workspace">
 @include('purchase.new-purchase.information')
 @include('purchase.new-purchase.products')
 @include('purchase.new-purchase.items')
 </div><aside class="rn-purchase-billing" aria-label="{{ __('purchases.purchase_summary') }}">@include('purchase.new-purchase.summary')</aside></div>
 @include('purchase.serialModal')
</form></div>
<script src="{{ asset('js/rn-q6-purchase.js') }}?v={{ filemtime(public_path('js/rn-q6-purchase.js')) }}" defer></script>


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
                    <form action="{{ route('createSupplier') }}" method="POST" id="supplierForm" data-ajax="true" data-target="#supplierName" data-modal-id="supplier">
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
<div class="modal fade rn-purchase-product-modal" id="newProduct" data-backdrop="static" data-keyboard="true" tabindex="-1" aria-labelledby="newProductTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <span class="rn-product-modal-mark" aria-hidden="true"><i class="las la-cube"></i></span>
                <div class="rn-product-modal-heading">
                    <h2 class="modal-title" id="newProductTitle">{{ __('purchases.create_product') }}</h2>
                    <p class="rn-product-modal-subtitle">{{ __('purchases.product_modal_intro') }}</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('purchases.close') }}"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('createProduct') }}" method="POST" id="productForm" data-ajax="true" data-target="#productName" data-modal-id="newProduct">
                    @csrf
                    <input type="hidden" name="businessId" value="{{ old('businessId', '') }}" data-purchase-business-context>
                    <div class="rn-ajax-form-errors alert alert-danger" role="alert" aria-live="polite" hidden></div>
                    <section class="rn-product-modal-section" aria-labelledby="rn-product-modal-info">
                        <div class="rn-product-modal-section-heading"><i class="las la-store" aria-hidden="true"></i><h3 id="rn-product-modal-info">{{ __('purchases.product_information') }}</h3></div>
                        <div class="rn-product-modal-section-body">
                            <div class="form-group"><label for="productNameModal">{{ __('common.product_name') }} <span class="text-danger">*</span></label><div class="rn-product-modal-input"><span aria-hidden="true"><i class="las la-cube"></i></span><input type="text" class="form-control" placeholder="{{ __('common.enter_name') }}" id="productNameModal" name="fullName" required></div></div>
                            <div class="form-group"><label for="barCodeNum">{{ __('purchases.barcode') }}</label><div class="rn-product-modal-input"><span aria-hidden="true"><i class="las la-barcode"></i></span><input type="text" class="form-control" id="barCodeNum" name="barCode" placeholder="{{ __('common.optional') }}"></div></div>
                        </div>
                    </section>
                    <section class="rn-product-modal-section" aria-labelledby="rn-product-modal-classification">
                        <div class="rn-product-modal-section-heading"><i class="las la-tags" aria-hidden="true"></i><h3 id="rn-product-modal-classification">{{ __('purchases.product_classification') }}</h3></div>
                        <div class="rn-product-modal-section-body rn-product-modal-three">
                            @mobile
                            <div class="rn-product-modal-mobile-fields">
                                <x-m.field name="brand" id="brandName" :label="__('purchases.brand_name')" :options="['' => __('purchases.select')] + collect($brandList ?? [])->pluck('name', 'id')->all()" required>
                                    <x-slot:action><button type="button" class="rn-m-field-action-button" data-toggle="modal" data-target="#createBrand"><i class="ri-add-line" aria-hidden="true"></i>{{ __('inventory.quick_add_brand') }}</button></x-slot:action>
                                </x-m.field>
                                <x-m.field name="category" id="categoryName" :label="__('purchases.product_category')" :options="['' => __('purchases.select')] + collect($categoryList ?? [])->pluck('name', 'id')->all()" required>
                                    <x-slot:action><button type="button" class="rn-m-field-action-button" data-toggle="modal" data-target="#categoryModal"><i class="ri-add-line" aria-hidden="true"></i>{{ __('inventory.quick_add_category') }}</button></x-slot:action>
                                </x-m.field>
                                <x-m.field name="unitName" id="unit" :label="__('purchases.product_unit')" :options="['' => __('purchases.select')] + collect($productUnitList ?? [])->pluck('name', 'id')->all()" required>
                                    <x-slot:action><button type="button" class="rn-m-field-action-button" data-toggle="modal" data-target="#productUnitModal"><i class="ri-add-line" aria-hidden="true"></i>{{ __('inventory.quick_add_unit') }}</button></x-slot:action>
                                </x-m.field>
                            </div>
                            @endmobile
                            @desktop
                            <div class="form-group"><label for="brandName">{{ __('purchases.brand_name') }} <span class="text-danger">*</span></label><div class="rn-product-select-action"><span class="rn-product-select-icon" aria-hidden="true"><i class="las la-building"></i></span><select id="brandName" class="form-control" name="brand" required><option value="">{{ __('purchases.select') }}</option>@foreach($brandList ?? [] as $brandData)<option value="{{ $brandData->id }}">{{ $brandData->name }}</option>@endforeach</select><button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#createBrand" aria-label="{{ __('purchases.brand') }}"><i class="las la-plus" aria-hidden="true"></i></button></div></div>
                            <div class="form-group"><label for="categoryName">{{ __('purchases.product_category') }} <span class="text-danger">*</span></label><div class="rn-product-select-action"><span class="rn-product-select-icon" aria-hidden="true"><i class="las la-tag"></i></span><select id="categoryName" class="form-control" name="category" required><option value="">{{ __('purchases.select') }}</option>@foreach($categoryList ?? [] as $categoryData)<option value="{{ $categoryData->id }}">{{ $categoryData->name }}</option>@endforeach</select><button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#categoryModal" aria-label="{{ __('purchases.category') }}"><i class="las la-plus" aria-hidden="true"></i></button></div></div>
                            <div class="form-group"><label for="unit">{{ __('purchases.product_unit') }} <span class="text-danger">*</span></label><div class="rn-product-select-action"><span class="rn-product-select-icon" aria-hidden="true"><i class="las la-layer-group"></i></span><select id="unit" class="form-control" name="unitName" required><option value="">{{ __('purchases.select') }}</option>@foreach($productUnitList ?? [] as $productUnitData)<option value="{{ $productUnitData->id }}">{{ $productUnitData->name }}</option>@endforeach</select><button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#productUnitModal" aria-label="{{ __('purchases.product_unit') }}"><i class="las la-plus" aria-hidden="true"></i></button></div></div>
                            @enddesktop
                        </div>
                    </section>
                    <section class="rn-product-modal-section" aria-labelledby="rn-product-modal-additional">
                        <div class="rn-product-modal-section-heading"><i class="las la-cube" aria-hidden="true"></i><h3 id="rn-product-modal-additional">{{ __('purchases.additional_product_information') }}</h3></div>
                        <div class="rn-product-modal-section-body">
                            <div class="form-group"><label for="quantityName">{{ __('purchases.alert_quantity') }}</label><div class="rn-product-modal-input"><span aria-hidden="true"><i class="las la-hashtag"></i></span><input type="number" min="0" step="1" class="form-control" placeholder="0" id="quantityName" name="quantity"></div></div>
                            <div class="form-group"><label for="detailsName">{{ __('inventory.details') }}</label><div class="rn-product-modal-input rn-modal-textarea"><span aria-hidden="true"><i class="las la-file-alt"></i></span><textarea class="form-control" id="detailsName" name="details" rows="2" placeholder="{{ __('common.optional') }}"></textarea></div></div>
                        </div>
                    </section>
                </form>
            </div>
            <div class="modal-footer">
                <button type="reset" form="productForm" class="btn btn-outline-secondary">{{ __('purchases.reset') }}</button>
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('purchases.cancel') }}</button>
                <button type="submit" form="productForm" class="btn btn-primary" id="add-product"><i class="las la-save mr-1" aria-hidden="true"></i>{{ __('purchases.save_product') }}</button>
            </div>
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
                <form action="{{ route('createBrand') }}" method="GET" id="brandForm" data-ajax="true" data-target="#brandName" data-modal-id="createBrand" data-rn-created-label="{{ __('inventory.brand_added') }}">
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
                <form action="{{ route('createCategory') }}" method="GET" id="categoryForm" data-ajax="true" data-target="#categoryName" data-modal-id="categoryModal" data-rn-created-label="{{ __('inventory.category_added') }}">
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
                <form action="{{ route('createProductUnit') }}" method="GET" id="productUnitForm" data-ajax="true" data-target="#unit" data-modal-id="productUnitModal" data-rn-created-label="{{ __('inventory.unit_added') }}">
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
