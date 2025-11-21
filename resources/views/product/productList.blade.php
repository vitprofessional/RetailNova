@extends('include') @section('backTitle') Product List @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
 <div class="card">
            <div class="card-body">
               
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    @include('partials.table-filters', [
                        'tableId' => 'productTable',
                        'searchId' => 'globalSearchProduct',
                        'selects' => [
                            ['id' => 'filterBrand', 'label' => 'Brands', 'options' => \App\Models\Brand::orderBy('name')->get()],
                            ['id' => 'filterCategory', 'label' => 'Categories', 'options' => \App\Models\Category::orderBy('name')->get()],
                        ],
                        'date' => false,
                        'searchPlaceholder' => 'Search product, brand, category...'
                    ])
                </div>
                <div class="rounded mb-3 table-responsive product-table">
                    <table id="productTable" class="data-tables table mb-0 table-bordered">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="checkbox1" />
                                        <label for="checkbox1" class="mb-0"></label>
                                    </div>
                                </th>
                                <th>Product Name</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Alert Quantity</th>
                                <th>Current Stock</th>
                                <th>Barcode</th>
                                <th>Deatils</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                                                @if(!empty($listItem) && $listItem->count()>0 ) @foreach($listItem as $productList)
                            <tr>
                                <td>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="checkbox2" />
                                        <label for="checkbox2" class="mb-0"></label>
                                    </div>
                                </td>
                                <td>{{$productList->name}}</td>
                                <td>{{ $productList->brandModel->name ?? '-' }}</td>
                                <td>{{ $productList->categoryModel->name ?? '-' }}</td>
                                <td>{{ $productList->unitModel->name ?? '-' }}</td>
                                <td>{{$productList->quantity}}</td>
                                <td>
                                    <span class="badge 
                                        @if($productList->is_out_of_stock)
                                            bg-danger
                                        @elseif($productList->is_low_stock)
                                            bg-danger
                                        @else
                                            bg-success
                                        @endif
                                    ">
                                        {{ $productList->total_stock }}
                                        @if($productList->is_out_of_stock)
                                            (Out of Stock)
                                        @elseif($productList->is_low_stock)
                                            (Low Stock)
                                        @endif
                                    </span>
                                    @if($productList->is_low_stock && !$productList->is_out_of_stock)
                                        <br><small class="text-danger"><i class="ri-alert-line"></i> Low product in stock</small>
                                    @endif
                                </td>
                                <td>{{$productList->barCode}}</td>
                                <td>{{$productList->details}}</td>
                                <td><div class="d-flex align-items-center list-action">
                                    

                                    <a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit"
                                        href="{{route('editProduct',['id'=>$productList->id])}}"><i class="ri-pencil-line mr-0"></i></a>

                                    <form method="POST" action="{{ route('delProduct',['id'=>$productList->id]) }}" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="badge bg-warning mr-2" data-confirm="delete" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete"><i class="ri-delete-bin-line mr-0"></i></button>
                                    </form>
                                </div></td>
                            </tr>
                        @endforeach 
                        @endif
                        </tbody>
                    </table>
                </div>
            
            </div>
        </div>
@endsection

@section('scripts')
<script>
    (function(){
        function applyProductFilters(){
            var brand = document.getElementById('filterBrand') ? document.getElementById('filterBrand').value.toLowerCase() : '';
            var category = document.getElementById('filterCategory') ? document.getElementById('filterCategory').value.toLowerCase() : '';
            var search = document.getElementById('globalSearchProduct') ? document.getElementById('globalSearchProduct').value.toLowerCase() : '';
            var rows = document.querySelectorAll('#productTable tbody tr');
            rows.forEach(function(r){
                var text = r.innerText.toLowerCase();
                var ok = true;
                if(brand && text.indexOf(brand) === -1) ok = false;
                if(category && text.indexOf(category) === -1) ok = false;
                if(search && text.indexOf(search) === -1) ok = false;
                r.style.display = ok ? '' : 'none';
            });
        }
        ['filterBrand','filterCategory','globalSearchProduct'].forEach(function(id){
            var el = document.getElementById(id);
            if(!el) return;
            el.addEventListener('input', applyProductFilters);
            el.addEventListener('change', applyProductFilters);
        });
    })();
</script>
@endsection