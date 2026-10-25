@php
    $profile = $profile ?? null;
    $isEditing = $profile !== null;
    $businessSelectionAllowed = $businessSelectionAllowed ?? false;
    $brandList = $brandList ?? collect();
    $categoryList = $categoryList ?? collect();
    $productUnitList = $productUnitList ?? collect();
    $selectedBrand = old('brand', $profile->brand ?? '');
    $selectedCategory = old('category', $profile->category ?? '');
    $selectedUnit = old('unitName', $profile->unitName ?? '');
@endphp

<main class="rn-m-page rn-m-product-form-page">
    @include('sweetalert::alert')
    <form action="{{ route('saveProduct') }}" method="POST" enctype="multipart/form-data" class="rn-m-form" data-rn-product-form>
        @csrf
        <input type="hidden" name="profileId" value="{{ old('profileId', $profile->id ?? '') }}">

        @if($errors->any())
            <div class="alert alert-danger" role="alert" aria-live="polite">
                <strong>{{ __('inventory.check_product_form') }}</strong>
                <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <x-m.form-section :title="__('inventory.product_basic_information')">
            @include('partials.business_selector', [
                'businesses' => $businesses ?? [],
                'selectedBusinessId' => $profile->businessId ?? old('businessId'),
                'businessSelectionAllowed' => $businessSelectionAllowed,
                'businessSelectorClass' => 'rn-m-form-business-field',
            ])

            @include('partials.business-image-field', ['imageRecord' => $profile, 'imageType' => 'products', 'imageFieldId' => 'productImage'])

            <x-m.field name="name" id="name" :label="__('inventory.product_name')"
                       :value="$profile->name ?? ''" :required="true" autocomplete="off" maxlength="200" />
            <div class="rn-m-field-with-trailing-icon">
                <x-m.field name="barCode" id="barCode" :label="__('inventory.barcode')"
                           :value="$profile->barCode ?? ''" maxlength="190" autocomplete="off" />
                <i class="las la-barcode" aria-hidden="true"></i>
            </div>

            <div class="rn-m-form-grid rn-m-form-grid--2">
                <div class="rn-m-field-with-action">
                    <x-m.field name="category" id="categoryList" :label="__('inventory.category')"
                               :options="['' => __('inventory.select')] + collect($categoryList)->pluck('name', 'id')->all()"
                               :value="$selectedCategory" />
                    <button type="button" class="rn-m-form-quick-add" data-toggle="modal" data-target="#categoryModal"
                            aria-label="{{ __('inventory.create_category') }}"><i class="las la-plus" aria-hidden="true"></i></button>
                </div>
                <div class="rn-m-field-with-action">
                    <x-m.field name="unitName" id="unitName" :label="__('inventory.unit')"
                               :options="collect($productUnitList)->pluck('name', 'id')->all()"
                               :value="$selectedUnit" />
                    <button type="button" class="rn-m-form-quick-add" data-toggle="modal" data-target="#productUnitModal"
                            aria-label="{{ __('inventory.create_product_unit') }}"><i class="las la-plus" aria-hidden="true"></i></button>
                </div>
            </div>
        </x-m.form-section>

        <x-m.form-section :title="__('inventory.stock_alert_settings')">
            <x-m.field name="quantity" id="quantity" type="number" :label="__('inventory.alert_quantity')"
                       :value="$profile->quantity ?? 0" min="0" step="1" inputmode="numeric" />
        </x-m.form-section>

        <details class="rn-m-form-accordion" @if($errors->has('details') || $errors->has('brand')) open @endif>
            <summary>{{ __('inventory.more_information') }} <i class="las la-angle-down" aria-hidden="true"></i></summary>
            <div class="rn-m-form-accordion-body">
                <div class="rn-m-field-with-action">
                    <x-m.field name="brand" id="brand" :label="__('inventory.brand')"
                               :options="['' => __('inventory.select')] + collect($brandList)->pluck('name', 'id')->all()"
                               :value="$selectedBrand" />
                    <button type="button" class="rn-m-form-quick-add" data-toggle="modal" data-target="#createBrand"
                            aria-label="{{ __('inventory.create_brand') }}"><i class="las la-plus" aria-hidden="true"></i></button>
                </div>
                <x-m.field name="details" id="details" type="textarea" :label="__('inventory.details')"
                           :value="$profile->details ?? ''" maxlength="1000" />
                <button type="reset" class="rn-m-btn rn-m-btn--outline rn-m-form-reset">{{ __('inventory.reset') }}</button>
            </div>
        </details>

        <x-m.sticky-actions :cancel-url="route('productlist')"
                            :cancel-label="__('common.cancel')"
                            :submit-label="$isEditing ? __('inventory.update_product') : __('inventory.save_product')"
                            data-rn-product-actions />
    </form>
</main>
