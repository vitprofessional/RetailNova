@extends('include') @section('backTitle')new products @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="row">
    <div class="col-12">
        @if(session()->has('success'))
        <div class="alert alert-success w-100">
            {{ session()->get('success') }}
        </div>
        @endif @if(session()->has('error'))
        <div class="alert alert-danger w-100">
            {{ session()->get('error') }}
        </div>
        @endif
    </div>
    @php if(isset($profile)): $name = $profile->name; $brand = $profile->brand; $category = $profile->category; $unitName = $profile->unitName; $quantity = $profile->quantity; $details = $profile->details; $barCode = $profile->barCode;
     $profileId = $profile->id; else: $name = ''; $brand = ''; $category = '';
    $unitName = ''; $quantity = ''; $details = ''; $barCode = '';  $profileId = ''; endif; @endphp
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="header-title">
                    <h4 class="card-title">@if(isset($profile)) Update @else Creat @endif Product</h4>
                </div>
            </div>
            <div class="card-body">
                <form action="{{route('saveProduct')}}" method="POST">
                    @csrf
                    <div class="row align-items-center">
                        <input type="hidden" name="profileId" value="{{ $profileId }}" />
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Prodct Name *</label>
                                <input type="text" class="form-control" placeholder="Enter Name" id="name" name="name" value="{{ $name }}" required />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Brand Name *</label>
                                <label for="brand" class="form-label"></label>
                                <select id="brand" class="form-control" name="brand">
                                  <!-- update from single data  show proccess -->
                                  @php
                                  $updateBrand = \App\Models\Brand::find($brand);
                                  @endphp
                                  @if(!empty($updateBrand))
                                  <option value="{{$updateBrand->id}}">{{$updateBrand->name}}</option>
                                  @endif
                                  <!--  form option show proccessing -->
                                  @if(!empty($brandList) && count($brandList)>0)
                                  @foreach($brandList as $brandData)
                                    <option value="{{$brandData->id}}">{{$brandData->name}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1 mt-4 p-0">
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#createBrand" ><i class="las la-plus mr-2"></i>Brand</button>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Product Category</label>
                                <label for="category" class="form-label"></label>
                                <select id="categoryList" class="form-control" name="category" >
                                    <!-- update from single data  show proccess -->
                                  @php
                                  $updateCategory = \App\Models\Category::find($category);
                                  @endphp
                                  @if(!empty($updateCategory))
                                  <option value="{{$updateCategory->id}}">{{$updateCategory->name}}</option>
                                  @endif
                                  <!--  form option show proccessing -->
                                  @if(!empty($categoryList) && count($categoryList)>0)
                                  @foreach($categoryList as $categoryData)
                                    <option value="{{$categoryData->id}}">{{$categoryData->name}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 mt-4 p-0">
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#categoryModal" ><i class="las la-plus mr-2"></i>Category</button>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Product Unit</label>
                                <label for="unitName" class="form-label"></label>
                                <select id="unitName" class="form-control" name="unitName" >
                                    <!-- update from single data  show proccess -->
                                  @php
                                  $updateProductUnit = \App\Models\ProductUnit::find($unitName);
                                  @endphp
                                  @if(!empty($updateProductUnit))
                                  <option value="{{$updateProductUnit->id}}">{{$updateProductUnit->name}}</option>
                                  @endif
                                  <!--  form option show proccessing -->
                                  @if(!empty($productUnitList) && count($productUnitList)>0)
                                  @foreach($productUnitList as $productUnitData)
                                    <option value="{{$productUnitData->id}}">{{$productUnitData->name}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 mt-4 p-0">
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#productUnitModal" ><i class="las la-plus mr-2"></i>Product unit</button>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="quantity" class="form-label">Alert Quantity</label>
                                <input type="text" class="form-control" placeholder="Optional" id="quantity" name="quantity" value="{{ $quantity }}" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="details" class="form-label">Deatils</label>
                                <input type="text" class="form-control" id="details" placeholder="Optional" name="details" value="{{ $details }}" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="barCode" class="form-label">Barcode</label>
                                <input type="text" class="form-control" id="barCode" placeholder="Optional" name="barCode" value="{{ $barCode }}" />
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-4 mr-2">@if(isset($profile)) Update @else Add @endif Product</button>
                    <button type="reset" class="btn btn-danger mt-4 mr-2">Reset</button>
                    @if(isset($profile))
                    <a href="{{route('addProduct')}}" class="btn btn-light mt-4 mr-2">
                        Back</a>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
@if(!isset($profile))
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="">Product List</h4>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive rounded mb-3">
                <table class="data-tables table mb-0 tbl-server-info">
                    <thead class="bg-white text-uppercase">
                        <tr class="table">
                            <th scope="col">Product Name</th>
                            <th scope="col">Category</th>
                            <th scope="col">Brand</th>
                            <th scope="col">Unit</th>
                            <th scope="col">Creat-date</th>
                            <th scope="col">Action</th>
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
                            <td>{{$productList->name}}</td>
                            @if(!empty($categorySingleData))
                                  <td>{{$categorySingleData->name}}</td>
                            @else
                                  <td>-</td>
                            @endif
                            @if(!empty($brandSingleData))
                                  <td>{{$brandSingleData->name}}</td>
                            @else
                                  <td>-</td>
                            @endif
                            @if(!empty($productUnitSingleData))
                                  <td>{{$productUnitSingleData->name}}</td>
                            @else
                                  <td>-</td>
                            @endif
                            <td>{{$productList->created_at->format('d-m-Y')}}</td>
                            <td>
                                <div class="d-flex align-items-center list-action">
                                    <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="View" href="#"><i class="ri-eye-line mr-0"></i></a>

                                    <a href="{{route('editProduct',['id'=>$productList->id])}}" class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit">
                                        <i class="ri-pencil-line mr-0"></i>
                                    </a>

                                    <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete" href="{{route('delProduct',['id'=>$productList->id])}}">
                                        <i class="ri-delete-bin-line mr-0"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach 
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Page end  -->

<!-- brand modal -->
<!-- Modal -->
<div class="modal fade" id="createBrand" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="createBrand" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">Creat Brand</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="POST" id="brandForm">
                    @csrf
                    <div class="mb-3">
                        <label for="brandName" class="form-label">Brand Name</label>
                        <input type="text" class="form-control" id="brandName" name="brandName" placeholder="Enter brand name" />
                    </div>
                  
                <button type="button" class="btn btn-primary" id="saveBrand">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancle</button>
            </div>
        </div>
    </div>
</div>

<!-- category modal -->
<div class="modal fade" id="categoryModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="categoryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">Creat Category</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="categoryForm">
                    @csrf
                <div class="mb-3">
                    <label for="categoryName" class="form-label">Category</label>
                    <input type="text" class="form-control" id="categoryName" name="categoryName" placeholder="Enter Category name" />
                </div>
                <button type="button" class="btn btn-primary" id="add-category">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancle</button>
            </div>
        </div>
    </div>
</div>

<!-- product unit -->

<div class="modal fade" id="productUnitModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="productUnitModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5" >Preoduct Unit</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="productUnitForm">
                    @csrf
                <div class="mb-3">
                    <label for="productUnitName" class="form-label">Product Unit</label>
                    <input type="text" class="form-control" id="productUnitName" name="productUnitName" placeholder="Enter Product Unit name" />
                </div>
                <button type="button" class="btn btn-primary" id="add-productUnit">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancle</button>
            </div>
        </div>
    </div>
</div>

@endsection

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
$(document).on('click','#saveBrand', function(){
    
    var name = $('#brandName').val();
    $.ajax({
        method: 'get',

        url: '{{ route('createBrand') }}',

        data: { name: name, },

        contentType: 'html',

        success: function(result) {
            console.log("message: ", result.message);
            // console.log("data: ", result.data);
            $('#createBrand').modal('hide');
            document.getElementById("brandForm").reset();
            $('#brand').html(result.data); 
        },

    });
})


$(document).on('click','#add-category', function(){
    
    var name = $('#categoryName').val();
    $.ajax({
        method: 'get',

        url: '{{ route('createCategory') }}',

        data: { name: name, },

        contentType: 'html',

        success: function(result) {
            console.log("message: ", result.message);
            // console.log("data: ", result.data);
            $('#categoryModal').modal('hide');
            document.getElementById("categoryForm").reset();
            $('#categoryList').html(result.data); 
        },

    });
})


$(document).on('click','#add-productUnit', function(){
    
    var name = $('#productUnitName').val();
    $.ajax({
        method: 'get',

        url: '{{ route('createProductUnit') }}',

        data: { name: name, },

        contentType: 'html',

        success: function(result) {
            console.log("message: ", result.message);
            // console.log("data: ", result.data);
            $('#productUnitModal').modal('hide');
            document.getElementById("productUnitForm").reset();
            $('#unitName').html(result.data); 
        },

    });
})
</script>
