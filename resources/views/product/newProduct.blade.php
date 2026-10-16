@extends('include')
@section('backTitle'){{ __('common.heading_new_products') }}@endsection
@section('container')
<link rel="stylesheet" href="{{ asset('css/inventory-products.css') }}?v={{ filemtime(public_path('css/inventory-products.css')) }}">
<div class="col-12">
    @include('sweetalert::alert')
</div>
<main class="rn-product-create">
    <div class="col-12">
        @if(session()->has('success'))
        <div class="alert alert-success w-100">{{ session()->get('success') }}</div>
        @endif
        @if(session()->has('error'))
        <div class="alert alert-danger w-100">{{ session()->get('error') }}</div>
        @endif
    </div>

    @php
        // Ensure view variables exist to avoid "Undefined variable" errors
        $profile = $profile ?? null;
        $isEditing = $profile !== null;
        $brandList = $brandList ?? [];
        $categoryList = $categoryList ?? [];
        $productUnitList = $productUnitList ?? [];
        $listItem = isset($listItem) ? $listItem : collect();

        $name = $profile->name ?? '';
        $brandId = $profile->brand ?? null;
        $categoryId = $profile->category ?? null;
        $unitId = $profile->unitName ?? null;
        $quantity = $profile->quantity ?? '';
        $details = $profile->details ?? '';
        $barCode = $profile->barCode ?? '';
        $profileId = $profile->id ?? '';
    @endphp

    <div class="rn-product-card">
            <header class="rn-product-page-heading">
                <div class="rn-product-heading-mark" aria-hidden="true"><i class="las la-cube"></i></div>
                <div class="rn-product-heading-copy">
                    <div class="rn-product-breadcrumb">{{ __('inventory.product_page_breadcrumb') }} <span aria-hidden="true">/</span> {{ $isEditing ? __('inventory.edit_product') : __('inventory.create_product') }}</div>
                    <h1>{{ $isEditing ? __('inventory.edit_product') : __('inventory.create_product') }}</h1>
                    <p>{{ __('inventory.product_form_intro') }}</p>
                </div>
                <a href="{{ route('productlist') }}" class="btn btn-outline-primary rn-product-page-action"><i class="las la-list mr-1" aria-hidden="true"></i>{{ __('inventory.product_list') }}</a>
            </header>
            <div class="rn-product-card-body">
                @if($errors->any())
                    <div class="alert alert-danger" role="alert" aria-live="polite">
                        <strong>{{ __('inventory.check_product_form') }}</strong>
                        <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <form action="{{ route('saveProduct') }}" method="POST">
                    @csrf
                    <input type="hidden" name="profileId" value="{{ $profileId }}" />

                    <section class="rn-product-section" aria-labelledby="rn-product-basic-heading">
                        <div class="rn-product-section-heading"><i class="las la-store" aria-hidden="true"></i><h2 id="rn-product-basic-heading">{{ __('inventory.product_basic_information') }}</h2></div>
                        <div class="rn-product-section-body rn-product-form-grid">
                        @include('partials.business_selector', ['businesses' => $businesses ?? [], 'selectedBusinessId' => $profile->businessId ?? old('businessId'), 'businessSelectionAllowed' => $businessSelectionAllowed ?? false, 'businessSelectorClass' => 'rn-product-business-field', 'businessSelectorIcon' => true])
                        <div class="rn-product-field rn-product-field-name">
                            <div class="form-group">
                                <label for="name" class="form-label">{{ __('inventory.product_name') }} <span class="text-danger">*</span></label>
                                <div class="rn-product-control"><span class="rn-product-control-icon" aria-hidden="true"><i class="las la-cube"></i></span><input type="text" class="form-control" placeholder="{{ __('common.e_g_nokia_1110') }}" id="name" name="name" value="{{ old('name', $name) }}" required /></div>
                                @error('name')<small class="text-danger" role="alert">{{ $message }}</small>@enderror
                            </div>
                        </div>
                        <div class="rn-product-field rn-product-field-barcode">
                            <div class="form-group">
                                <label for="barCode" class="form-label">{{ __('inventory.barcode') }}</label>
                                <div class="rn-product-control"><span class="rn-product-control-icon" aria-hidden="true"><i class="las la-barcode"></i></span><input type="text" class="form-control" id="barCode" placeholder="{{ __('common.optional') }}" name="barCode" value="{{ old('barCode', $barCode) }}" /></div>
                                @error('barCode')<small class="text-danger" role="alert">{{ $message }}</small>@enderror
                            </div>
                        </div>

                        <div class="rn-product-field rn-product-field-brand">
                            <div class="form-group">
                                <label for="brand" class="form-label">{{ __('inventory.brand') }}</label>
                                <div class="input-group input-append-equal rn-product-select-control">
                                    <span class="rn-product-control-icon" aria-hidden="true"><i class="las la-tag"></i></span>
                                    <select id="brand" class="form-control" name="brand">
                                      <option value="">{{ __('inventory.select') }}</option>
                                      @php $updateBrand = \App\Models\Brand::find($brandId); @endphp
                                      @if(!empty($updateBrand))
                                      <option value="{{$updateBrand->id}}" selected>{{ $updateBrand->name }}</option>
                                      @endif
                                      @if(!empty($brandList) && count($brandList)>0)
                                      @foreach($brandList as $brandData)
                                        <option value="{{$brandData->id}}" {{ (string)old('brand', $brandId) === (string)$brandData->id ? 'selected' : '' }}>{{$brandData->name}}</option>
                                      @endforeach
                                      @endif
                                    </select>
                                    <button type="button" class="btn btn-outline-primary rounded-0" data-toggle="modal" data-target="#createBrand" aria-label="{{ __('inventory.create_brand') }}"><i class="las la-plus" aria-hidden="true"></i></button>
                                </div>
                                @error('brand')<small class="text-danger" role="alert">{{ $message }}</small>@enderror
                            </div>
                        </div>

                        <div class="rn-product-field rn-product-field-category">
                            <div class="form-group">
                                <label for="categoryList" class="form-label">{{ __('inventory.category') }}</label>
                                <div class="input-group input-append-equal rn-product-select-control">
                                    <span class="rn-product-control-icon" aria-hidden="true"><i class="las la-th-large"></i></span>
                                    <select id="categoryList" class="form-control" name="category">
                                      <option value="">{{ __('inventory.select') }}</option>
                                      @php $updateCategory = \App\Models\Category::find($categoryId); @endphp
                                      @if(!empty($updateCategory))
                                      <option value="{{$updateCategory->id}}">{{$updateCategory->name}}</option>
                                      @endif
                                      @if(!empty($categoryList) && count($categoryList)>0)
                                      @foreach($categoryList as $categoryData)
                                        <option value="{{$categoryData->id}}" {{ (string)old('category', $categoryId) === (string)$categoryData->id ? 'selected' : '' }}>{{$categoryData->name}}</option>
                                      @endforeach
                                      @endif
                                    </select>
                                    <button type="button" class="btn btn-outline-primary rounded-0" data-toggle="modal" data-target="#categoryModal" aria-label="{{ __('inventory.create_category') }}"><i class="las la-plus" aria-hidden="true"></i></button>
                                </div>
                                @error('category')<small class="text-danger" role="alert">{{ $message }}</small>@enderror
                            </div>
                        </div>

                        <div class="rn-product-field rn-product-field-unit">
                            <div class="form-group">
                                <label for="unitName" class="form-label">{{ __('inventory.unit') }}</label>
                                <div class="input-group input-append-equal rn-product-select-control">
                                    <span class="rn-product-control-icon" aria-hidden="true"><i class="las la-layer-group"></i></span>
                                    <select id="unitName" class="form-control" name="unitName">
                                      @php $updateProductUnit = \App\Models\ProductUnit::find($unitId); @endphp
                                      @if(!empty($updateProductUnit))
                                      <option value="{{$updateProductUnit->id}}">{{$updateProductUnit->name}}</option>
                                      @endif
                                      @if(!empty($productUnitList) && count($productUnitList)>0)
                                      @foreach($productUnitList as $productUnitData)
                                        <option value="{{$productUnitData->id}}" {{ (string)old('unitName', $unitId) === (string)$productUnitData->id ? 'selected' : '' }}>{{$productUnitData->name}}</option>
                                      @endforeach
                                      @endif
                                    </select>
                                    <button type="button" class="btn btn-outline-primary rounded-0" data-toggle="modal" data-target="#productUnitModal" aria-label="{{ __('inventory.create_product_unit') }}"><i class="las la-plus" aria-hidden="true"></i></button>
                                </div>
                                @error('unitName')<small class="text-danger" role="alert">{{ $message }}</small>@enderror
                            </div>
                        </div>

                        <div class="rn-product-field rn-product-field-quantity">
                            <div class="form-group">
                                <label for="quantity" class="form-label">{{ __('inventory.alert_quantity') }}</label>
                                <div class="rn-product-control"><span class="rn-product-control-icon" aria-hidden="true"><i class="las la-hashtag"></i></span><input type="number" class="form-control" placeholder="0" id="quantity" name="quantity" value="{{ old('quantity', $quantity) }}" min="0" step="1" /></div>
                                @error('quantity')<small class="text-danger" role="alert">{{ $message }}</small>@enderror
                            </div>
                        </div>

                        <div class="rn-product-field rn-product-field-wide">
                            <div class="form-group">
                                <label for="details" class="form-label">{{ __('inventory.details') }}</label>
                                <div class="rn-product-control rn-product-textarea"><span class="rn-product-control-icon" aria-hidden="true"><i class="las la-file-alt"></i></span><textarea class="form-control" id="details" placeholder="{{ __('common.optional_notes_about_the_product') }}" name="details" rows="2">{{ old('details', $details) }}</textarea></div>
                                @error('details')<small class="text-danger" role="alert">{{ $message }}</small>@enderror
                            </div>
                        </div>

                        </div>
                    </section>

                    <div class="rn-product-form-actions">
                            <button type="reset" class="btn btn-light">{{ __('inventory.reset') }}</button>
                            @if($isEditing)
                            <a href="{{ route('addProduct') }}" class="btn btn-outline-secondary">{{ __('inventory.back') }}</a>
                            @endif
                            <button type="submit" class="btn btn-primary">{{ $isEditing ? __('inventory.update_product') : __('inventory.save_product') }}</button>
                    </div>
                </form>
            </div>
    </div>
</main>

<!-- Page end  -->

<!-- brand modal -->
<div class="modal fade" id="createBrand" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="createBrand" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">{{ __('inventory.create_brand') }}</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="{{ __('inventory.close') }}"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('createBrand') }}" method="GET" id="brandForm" data-ajax="true" data-target="#brand" data-modal-id="createBrand">
                    <div class="mb-3">
                        <label for="brandName" class="form-label">{{ __('inventory.brand_name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="brandName" name="name" placeholder="{{ __('common.enter_brand_name') }}" required />
                    </div>
                    <button type="submit" class="btn btn-primary" id="saveBrand">{{ __('inventory.save') }}</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('inventory.close') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- category modal -->
<div class="modal fade" id="categoryModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="categoryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">{{ __('inventory.create_category') }}</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="{{ __('inventory.close') }}"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('createCategory') }}" method="GET" id="categoryForm" data-ajax="true" data-target="#categoryList" data-modal-id="categoryModal">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">{{ __('inventory.category_name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="categoryName" name="name" placeholder="{{ __('common.enter_category_name') }}" required />
                    </div>
                    <button type="submit" class="btn btn-primary" id="add-category">{{ __('inventory.save') }}</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('inventory.close') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- product unit modal -->
<div class="modal fade" id="productUnitModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="productUnitModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">{{ __('inventory.create_product_unit') }}</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="{{ __('inventory.close') }}"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('createProductUnit') }}" method="GET" id="productUnitForm" data-ajax="true" data-target="#unitName" data-modal-id="productUnitModal">
                    <div class="mb-3">
                        <label for="productUnitName" class="form-label">{{ __('inventory.unit_name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="productUnitName" name="name" placeholder="{{ __('common.enter_product_unit_name') }}" required />
                    </div>
                    <button type="submit" class="btn btn-primary" id="add-productUnit">{{ __('inventory.save') }}</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('inventory.close') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    @parent
    @include('customScript')
    <script>
    // Legacy click handlers removed. Forms are submitted via data-ajax handler in `customScript`.
    </script>
@endsection

