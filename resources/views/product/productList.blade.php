@extends('include') @section('backTitle') Product List @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
 <div class="card">
            <div class="card-body">
               
                <div class="rounded mb-3 table-responsive product-table">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <h4>Product List</h4>

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
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Alert Quantity</th>
                                <th>Barcode</th>
                                <th>Deatils</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @if(!empty($listItem) && $listItem->count()>0 ) @foreach($listItem as $productList) 
                        @php                         
                          $categorySingleData  = \App\Models\Category::find($productList->category);           
                          $productUnitSingleData  = \App\Models\ProductUnit::find($productList->unitName);
                          $brandSingleData  = \App\Models\Brand::find($productList->brand);
                        @endphp
                            <tr>
                                <td>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="checkbox2" />
                                        <label for="checkbox2" class="mb-0"></label>
                                    </div>
                                </td>
                                <td>{{$productList->name}}</td>
                                @if(!empty($brandSingleData))
                                    <td>{{$brandSingleData->name}}</td>
                                @else
                                    <td>-</td>
                                @endif
                                @if(!empty($categorySingleData))
                                    <td>{{$categorySingleData->name}}</td>
                                @else
                                    <td>-</td>
                                @endif
                                @if(!empty($productUnitSingleData))
                                    <td>{{$productUnitSingleData->name}}</td>
                                @else
                                    <td>-</td>
                                @endif
                                <td>{{$productList->quantity}}</td>
                                <td>{{$productList->barCode}}</td>
                                <td>{{$productList->details}}</td>
                                <td><div class="d-flex align-items-center list-action">
                                    <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="View"
                                    href="#"><i class="ri-eye-line mr-0"></i></a>

                                    <a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit"
                                        href="{{route('editProduct',['id'=>$productList->id])}}"><i class="ri-pencil-line mr-0"></i></a>

                                    <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete"
                                        href="{{route('delProduct',['id'=>$productList->id])}}"><i class="ri-delete-bin-line mr-0"></i></a>
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