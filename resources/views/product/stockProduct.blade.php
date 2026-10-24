@extends('include') @section('backTitle'){{ app(\App\Support\MobileView::class)->isMobile() ? __('mobile.stock') : __('common.heading_stock_product') }}@endsection @section('container')
{{-- UI-2: the mobile version is cards (App\Services\MobileLists); the desktop markup below is unchanged. --}}
@mobile
<section class="rn-m-list-page" data-rn-list="stock-lots">
    <header class="rn-m-list-header"><div class="rn-m-list-toolbar"><form method="GET" class="rn-m-search" role="search"><i class="ri-search-line" aria-hidden="true"></i><label class="sr-only" for="stockLotSearch">{{ __('mobile.search_products_list') }}</label><input id="stockLotSearch" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('mobile.search_products_list') }}"></form></div></header>
    <ul class="rn-m-cards" role="list" data-rn-cards>
        @forelse(($productStockList ?? collect())->filter(fn($lot) => !request('q') || str_contains(mb_strtolower($lot->productName.' '.$lot->invoiceNo), mb_strtolower(request('q')))) as $lot)
            <x-m.list-card :card="['id' => $lot->stockId, 'url' => null, 'avatar' => mb_substr($lot->productName, 0, 1), 'title' => $lot->productName, 'subtitle' => $lot->invoiceNo ? __('purchases.invoice').' '.$lot->invoiceNo : null, 'amount' => null, 'amount_tone' => 'ink', 'note' => null, 'note_tone' => 'muted', 'badge' => ['label' => __('mobile.stock').' '.\App\Support\LocalizedNumber::count($lot->currentStock), 'tone' => 'success'], 'meta' => array_values(array_filter([$lot->supplierName ?: null, $lot->buyPrice !== null ? __('inventory.purchase_price').': '.\App\Support\LocalizedNumber::money($lot->buyPrice) : null, $lot->salePriceExVat !== null ? __('inventory.sale_price').': '.\App\Support\LocalizedNumber::money($lot->salePriceExVat) : null, $lot->discount !== null ? __('common.dp').': '.\App\Support\LocalizedNumber::money($lot->discount) : null])), 'actions' => []]" />
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
