@extends('include')
@section('backTitle') new products @endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="row">
    <div class="col-12">
        @if(session()->has('success'))
        <div class="alert alert-success w-100">{{ session()->get('success') }}</div>
        @endif
        @if(session()->has('error'))
        <div class="alert alert-danger w-100">{{ session()->get('error') }}</div>
        @endif
    </div>

    @php
        // Ensure view variables exist to avoid "Undefined variable" errors
        $profile = $profile ?? null;
        $brandList = $brandList ?? [];
        $categoryList = $categoryList ?? [];
        $productUnitList = $productUnitList ?? [];
        $listItem = isset($listItem) ? $listItem : collect();

        $name = $profile->name ?? '';
        $brandId = $profile->brand ?? null;
        $categoryId = $profile->category ?? null;
        $unitId = $profile->unitName ?? null;
        $quantity = $profile->quantity ?? '';
        $details = $profile->details ?? '';
        $barCode = $profile->barCode ?? '';
        $profileId = $profile->id ?? '';
    @endphp

    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="header-title">
                    <h4 class="card-title">@if(isset($profile)) Update @else Create @endif Product</h4>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('saveProduct') }}" method="POST">
                    @csrf
                    <input type="hidden" name="profileId" value="{{ $profileId }}" />

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="e.g., Nokia 1110" id="name" name="name" value="{{ $name }}" required />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Barcode</label>
                                <input type="text" class="form-control" id="barCode" placeholder="Optional" name="barCode" value="{{ $barCode }}" />
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="brand" class="form-label">Brand <span class="text-danger">*</span></label>
                                <div class="input-group input-append-equal">
                                    <select id="brand" class="form-control" name="brand" required style="min-width:0;">
                                      @php $updateBrand = \App\Models\Brand::find($brandId); @endphp
                                      @if(!empty($updateBrand))
                                      <option value="{{$updateBrand->id}}">{{$updateBrand->name}}</option>
                                      @endif
                                      @if(!empty($brandList) && count($brandList)>0)
                                      @foreach($brandList as $brandData)
                                        <option value="{{$brandData->id}}">{{$brandData->name}}</option>
                                      @endforeach
                                      @endif
                                    </select>
                                    <button type="button" class="btn btn-outline-primary rounded-0" data-toggle="modal" data-target="#createBrand" style="flex:0 0 auto;"><i class="las la-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="categoryList" class="form-label">Category <span class="text-danger">*</span></label>
                                <div class="input-group input-append-equal">
                                    <select id="categoryList" class="form-control" name="category" required style="min-width:0;">
                                      @php $updateCategory = \App\Models\Category::find($categoryId); @endphp
                                      @if(!empty($updateCategory))
                                      <option value="{{$updateCategory->id}}">{{$updateCategory->name}}</option>
                                      @endif
                                      @if(!empty($categoryList) && count($categoryList)>0)
                                      @foreach($categoryList as $categoryData)
                                        <option value="{{$categoryData->id}}">{{$categoryData->name}}</option>
                                      @endforeach
                                      @endif
                                    </select>
                                    <button type="button" class="btn btn-outline-primary rounded-0" data-toggle="modal" data-target="#categoryModal" style="flex:0 0 auto;"><i class="las la-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="unitName" class="form-label">Unit <span class="text-danger">*</span></label>
                                <div class="input-group input-append-equal">
                                    <select id="unitName" class="form-control" name="unitName" required style="min-width:0;">
                                      @php $updateProductUnit = \App\Models\ProductUnit::find($unitId); @endphp
                                      @if(!empty($updateProductUnit))
                                      <option value="{{$updateProductUnit->id}}">{{$updateProductUnit->name}}</option>
                                      @endif
                                      @if(!empty($productUnitList) && count($productUnitList)>0)
                                      @foreach($productUnitList as $productUnitData)
                                        <option value="{{$productUnitData->id}}">{{$productUnitData->name}}</option>
                                      @endforeach
                                      @endif
                                    </select>
                                    <button type="button" class="btn btn-outline-primary rounded-0" data-toggle="modal" data-target="#productUnitModal" style="flex:0 0 auto;"><i class="las la-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="quantity" class="form-label">Alert Quantity</label>
                                <input type="number" class="form-control" placeholder="0" id="quantity" name="quantity" value="{{ $quantity }}" min="0" step="1" />
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group mb-3">
                                <label for="details" class="form-label">Details</label>
                                <textarea class="form-control" id="details" placeholder="Optional notes about the product" name="details" rows="2">{{ $details }}</textarea>
                            </div>
                        </div>

                        <div class="col-12 d-flex justify-content-end mt-2">
                            <button type="reset" class="btn btn-light mr-2">Reset</button>
                            @if(isset($profile))
                            <a href="{{ route('addProduct') }}" class="btn btn-outline-secondary mr-2">Back</a>
                            @endif
                            <button type="submit" class="btn btn-primary">@if(isset($profile)) Update @else Save @endif</button>
                        </div>
                    </div>
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
                <h4 class="mb-0">Product List</h4>
            </div>
            <div class="card-body table-responsive rounded mb-3">
                <table class="data-tables table mb-0 tbl-server-info">
                    <thead class="bg-white text-uppercase">
                        <tr class="table">
                            <th scope="col">Product Name</th>
                            <th scope="col">Category</th>
                            <th scope="col">Brand</th>
                            <th scope="col">Unit</th>
                            <th scope="col">Created Date</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($listItem) && $listItem->count()>0)
                                                @foreach($listItem as $productList)
                        <tr>
                            <td>{{ $productList->name }}</td>
                            <td>{{ $productList->categoryModel->name ?? '-' }}</td>
                            <td>{{ $productList->brandModel->name ?? '-' }}</td>
                            <td>{{ $productList->unitModel->name ?? '-' }}</td>
                            <td>{{ $productList->created_at->format('d-m-Y') }}</td>
                            <td>
                                <div class="d-flex align-items-center list-action">
                                    <a href="{{ route('editProduct',['id'=>$productList->id]) }}" class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="Edit">
                                        <i class="ri-pencil-line mr-0"></i>
                                    </a>
                                    <form method="POST" action="{{ route('delProduct',['id'=>$productList->id]) }}" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="badge bg-warning mr-2" data-confirm="delete" data-toggle="tooltip" data-placement="top" title="Delete"><i class="ri-delete-bin-line mr-0"></i></button>
                                    </form>
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
<div class="modal fade" id="createBrand" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="createBrand" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">Create Brand</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('createBrand') }}" method="GET" id="brandForm" data-ajax="true" data-target="#brand" data-modal-id="createBrand">
                    @csrf
                    <div class="mb-3">
                        <label for="brandName" class="form-label">Brand Name</label>
                        <input type="text" class="form-control" id="brandName" name="name" placeholder="Enter brand name" />
                    </div>
                    <button type="submit" class="btn btn-primary" id="saveBrand">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
    </div>

<!-- category modal -->
<div class="modal fade" id="categoryModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="categoryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">Create Category</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('createCategory') }}" method="GET" id="categoryForm" data-ajax="true" data-target="#categoryList" data-modal-id="categoryModal">
                    @csrf
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category</label>
                        <input type="text" class="form-control" id="categoryName" name="name" placeholder="Enter Category name" />
                    </div>
                    <button type="submit" class="btn btn-primary" id="add-category">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- product unit modal -->
<div class="modal fade" id="productUnitModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="productUnitModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">Product Unit</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('createProductUnit') }}" method="GET" id="productUnitForm" data-ajax="true" data-target="#unitName" data-modal-id="productUnitModal">
                    @csrf
                    <div class="mb-3">
                        <label for="productUnitName" class="form-label">Product Unit</label>
                        <input type="text" class="form-control" id="productUnitName" name="name" placeholder="Enter Product Unit name" />
                    </div>
                    <button type="submit" class="btn btn-primary" id="add-productUnit">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    @parent
    <script>
    // Legacy click handlers removed. Forms are submitted via data-ajax handler in `customScript`.
    </script>
    <style>
    /* Make appended buttons match select height across Bootstrap 4/5 */
    .input-group.input-append-equal > .btn {
        align-self: stretch;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    </style>
@endsection
                                
