@extends('include') @section('backTitle'){{ __('common.heading_stock_product') }}@endsection @section('container')
{{-- UI-2: the mobile version is cards (App\Services\MobileLists); the desktop markup below is unchanged. --}}
@mobile
@include('mobile.lists.page', ['list' => new \App\Services\MobileLists\ProductsList('stockProduct')])
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