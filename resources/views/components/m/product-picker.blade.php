@props([
    'mode' => 'sale',
    'label' => __('mobile.search_products'),
    'url' => route('m.product-picker'),
    'returnUrlTemplate' => '',
])
@php($searchId = $attributes->get('id', 'rnProductPickerSearch-'.\Illuminate\Support\Str::slug($mode)))
<section class="rn-m-product-picker" data-rn-product-picker data-mode="{{ $mode }}"
    data-url="{{ $url }}" data-return-url-template="{{ $returnUrlTemplate }}"
    data-cart-key="rn-cart-{{ $mode }}-{{ auth('admin')->id() ?? 'guest' }}-{{ auth('admin')->user()?->businessId ?? 'business' }}"
    data-currency="{{ \App\Support\Currency::symbol() }}" data-currency-position="{{ \App\Support\Currency::position() }}"
    data-label-category="{{ __('inventory.category') }}" data-label-brand="{{ __('inventory.brand') }}"
    data-label-all-category="{{ __('mobile.all_categories') }}" data-label-all-brand="{{ __('mobile.all_brands') }}"
    data-label-stock="{{ __('mobile.in_stock', ['count' => '__COUNT__']) }}"
    data-label-no-price="{{ __('mobile.no_sale_price_buy_first') }}" data-label-out-of-stock="{{ __('mobile.out_of_stock') }}" data-label-empty="{{ __('mobile.no_products') }}"
    data-label-more="{{ __('mobile.load_more') }}" data-label-loading="{{ __('common.loading') }}" data-label-error="{{ __('mobile.product_picker_error') }}">
    <label class="sr-only" for="{{ $searchId }}">{{ $label }}</label>
    <div class="rn-m-product-picker__search"><i class="ri-search-line" aria-hidden="true"></i>
        <input id="{{ $searchId }}" type="search" autocomplete="off" placeholder="{{ $label }}" data-picker-search>
    </div>
    <div class="rn-m-product-picker__filter-bar">
        <div class="rn-m-product-picker__switch" role="group" aria-label="{{ __('common.filter') }}">
            <button type="button" class="is-active" data-picker-filter-mode="category" aria-pressed="true">{{ __('inventory.category') }}</button>
            <button type="button" data-picker-filter-mode="brand" aria-pressed="false">{{ __('inventory.brand') }}</button>
        </div>
        <div class="rn-m-product-picker__chips" role="group" aria-label="{{ __('common.filter') }}" data-picker-chips></div>
    </div>
    <div class="rn-m-product-picker__grid" role="list" data-picker-results aria-live="polite"></div>
    <p class="rn-m-product-picker__empty" data-picker-empty hidden>{{ __('mobile.no_products') }}</p>
    <button type="button" class="rn-m-product-picker__more" data-picker-more hidden>{{ __('mobile.load_more') }}</button>
    <p class="rn-m-product-picker__error" data-picker-error role="status" hidden></p>
</section>
@once <script src="{{ asset('js/rn-product-picker.js') }}?v={{ filemtime(public_path('js/rn-product-picker.js')) }}" defer></script> @endonce
