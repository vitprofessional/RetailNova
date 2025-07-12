@extends('include') @section('backTitle') damage product @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="" class="row" method="POST">
    @csrf
    <div class="col-12">
        <div class="row">
            <div class="col-md-12 col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Damage product</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" id="date" name="purchaseDate" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="reference" class="form-label">Reference *</label>
                                    <input type="text" class="form-control" id="reference" name="refData" />
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label for="productName" class="form-label">Product *</label>
                                    <select id="productName" name="productName" onchange="productSelect()" class="form-control" required disabled>
                                    <!--  form option show proccessing -->
                                        <option value="">Select</option>
                                    @if(!empty($productList) && count($productList)>0)
                                    @foreach($productList as $productData)
                                        <option value="{{$productData->id}}">{{$productData->name}}</option>
                                    @endforeach
                                    @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="mb-3 table-responsive product-table">
                    <table class="table mb-0 table-bordered rounded-0">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>Product Name</th>
                                <th>In Date</th>
                                <th>Product Qty</th>
                                <th>Current Stock</th>
                                <th>Price</th>
                                <th>Total Price</th>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody id="productDetails">
                            <tr>
                                <td width="20%">
                                    <input type="text" class="form-control" name="selectProductName" value="" id="selectProductName" readonly />
                                </td>
                                <td width="8%">
                                    -
                                </td>
                                <td width="9%">
                                    <input type="number" class="form-control" id="qty" name="qty" readonly />
                                </td>
                                <td width="9%">
                                    <input type="number" class="form-control" id="currentStock" name="currentStock" readonly />
                                </td>
                                <td width="9%">
                                    <input type="number" class="form-control" id="buyingPrice" name="buyingPrice" readonly />
                                </td>
                                <td width="9%">
                                    <input type="number" class="form-control" id="salingPriceWithoutVat" name="salingPriceWithoutVat" readonly />
                                </td>
                                <td width="9%">
                                   
                                    <div class="list-action">
                                        <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete" href="#"><i class="ri-delete-bin-line mr-0"></i></a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class=" d-md-flex  mt-2">
                    <button class="btn btn-primary btn-sm" type="submit">Save</button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection