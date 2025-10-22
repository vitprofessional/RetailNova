@extends('include') @section('backTitle')Stock Product@endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
 <div class="card">
            <div class="card-body">
                <div class="rounded mb-3 table-responsive product-table">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <h4>Stock Product</h4>
                        </div>
                    </div>
                    <table class="data-tables table mb-0 table-bordered">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="checkbox1" />
                                        <label for="checkbox1" class="mb-0"></label>
                                    </div>
                                </th>
                                <th>Product Name</th>
                                <th>Supplier</th>
                                <th>Stock Quantity</th>
                                <th>Purchase Price</th>
                                <th>Sale Price</th>
                                <th>Dp</th>
                                <th>Action</th>
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
                                    <td colspan="8" class="text-center">No products available.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            
            </div>
        </div>
@endsection