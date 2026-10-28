@extends('include') @section('backTitle'){{ app(\App\Support\MobileView::class)->isMobile() ? __('mobile.stock') : __('common.heading_stock_product') }}@endsection @section('container')
{{-- UI-2: the mobile version is cards (App\Services\MobileLists); the desktop markup below is unchanged. --}}
@mobile
<section class="rn-m-list-page" data-rn-list="stock-lots">
    <header class="rn-m-list-header"><div class="rn-m-list-toolbar"><form method="GET" class="rn-m-search" role="search"><i class="ri-search-line" aria-hidden="true"></i><label class="sr-only" for="stockLotSearch">{{ __('mobile.search_products_list') }}</label><input id="stockLotSearch" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('mobile.search_products_list') }}"></form></div></header>
    <nav class="rn-m-segment rn-m-stock-tabs" aria-label="{{ __('mobile.stock') }}" data-rn-product-stock-tabs>
        <a href="{{ route('productlist') }}" class="rn-m-segment-btn">{{ __('mobile.products') }}</a>
        <a href="{{ route('stockProduct') }}" class="rn-m-segment-btn is-active" aria-current="page">{{ __('mobile.stock_by_lot') }}</a>
    </nav>
    @php
        $stockLotsByProduct = collect($productStockList ?? [])->filter(fn ($lot) => !request('q') || str_contains(mb_strtolower($lot->productName.' '.$lot->invoiceNo), mb_strtolower(request('q'))))->groupBy('productId');
    @endphp
    <ul class="rn-m-cards" role="list" data-rn-cards>
        @forelse($stockLotsByProduct as $productId => $lots)
            @php $firstLot = $lots->first(); $totalStock = $lots->sum('currentStock'); @endphp
            <li class="rn-m-card rn-m-stock-product" data-product-id="{{ $productId }}">
                <div class="rn-m-stock-product-head">
                    <span class="rn-m-card-avatar" aria-hidden="true">{{ preg_match('/\p{L}/u', (string) $firstLot->productName, $initial) ? mb_strtoupper($initial[0]) : '◈' }}</span>
                    <strong class="rn-m-card-title">{{ $firstLot->productName }}</strong>
                    <span class="rn-m-stock-total">{{ __('mobile.stock') }} {{ \App\Support\LocalizedNumber::count($totalStock) }}</span>
                </div>
                <ul class="rn-m-stock-lots" role="list" aria-label="{{ __('mobile.stock_by_lot') }}">
                    @foreach($lots as $lot)
                        <li class="rn-m-stock-lot">
                            <div class="rn-m-stock-lot-title">
                                @if($lot->invoiceNo)<span>{{ __('purchases.invoice') }} · <span data-latin-ok>{{ $lot->invoiceNo }}</span></span>@else<span>{{ __('mobile.stock_by_lot') }}</span>@endif
                                <strong>{{ __('mobile.stock') }} {{ \App\Support\LocalizedNumber::count($lot->currentStock) }}</strong>
                            </div>
                            <div class="rn-m-stock-lot-meta">
                                @if($lot->supplierName)<span>{{ $lot->supplierName }}</span>@endif
                                @if($lot->buyPrice !== null)<span>{{ __('inventory.purchase_price') }}: {{ \App\Support\LocalizedNumber::money($lot->buyPrice) }}</span>@endif
                                @if($lot->salePriceExVat !== null)<span>{{ __('inventory.sale_price') }}: {{ \App\Support\LocalizedNumber::money($lot->salePriceExVat) }}</span>@endif
                                @if($lot->discount !== null)<span>{{ __('purchases.discount') }}: {{ \App\Support\LocalizedNumber::money($lot->discount) }}</span>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </li>
        @empty
            <li class="rn-m-empty-state">{{ __('inventory.no_products_available') }}</li>
        @endforelse
    </ul>
</section>
@else
<div class="col-12">
    @include('sweetalert::alert')
</div>
 <div class="card">
            <div class="card-body">
                <div class="rounded mb-3 table-responsive product-table">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <h4>{{ __('inventory.stock_product') }}</h4>
                        </div>
                    </div>
                    <table class="data-tables table mb-0 table-bordered rn-table-pro">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="checkbox1" />
                                        <label for="checkbox1" class="mb-0"></label>
                                    </div>
                                </th>
                                <th>{{ __('inventory.product_name') }}</th>
                                <th>{{ __('inventory.supplier') }}</th>
                                <th>{{ __('inventory.stock_quantity') }}</th>
                                <th>{{ __('inventory.purchase_price') }}</th>
                                <th>{{ __('inventory.sale_price') }}</th>
                                <th>{{ __('common.dp') }}</th>
                                <th>{{ __('inventory.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($productStockList)
                                @foreach($productStockList as $product)
                                    <tr>
                                        <td>
                                            <div class="checkbox d-inline-block">
                                                <input type="checkbox" class="checkbox-input" id="checkbox{{ $product->id }}" />
                                                <label for="checkbox{{ $product->id }}" class="mb-0"></label>
                                            </div>
                                        </td>
                                        <td>{{ $product->productName }}</td>
                                        <td>{{ $product->supplierName }}</td>
                                        <td>{{ $product->currentStock }}</td>
                                        <td>{{ $product->buyPrice }}</td>
                                        <td>{{ $product->salePriceExVat }}</td>
                                        <td>{{ $product->discount }}</td>
                                        <td>
                                            <!-- Action buttons can be added here -->
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="8" class="text-center">{{ __('inventory.no_products_available') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
@endmobile
@endsection
