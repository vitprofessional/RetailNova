@extends('include')

@section('backTitle'){{ __('purchases.purchase_return_list') }}@endsection

@section('container')
    <main class="rn-m-page" aria-label="{{ __('purchases.purchase_return_list') }}">
        <x-m.product-picker mode="purchase-return" :label="__('mobile.search_products')"
            :return-url-template="route('returnPurchase', ['id' => '__ID__'])" />
    </main>
@endsection
