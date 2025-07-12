@extends('include') @section('backTitle') purchase @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="{{ route('savePurchase') }}" class="row" method="POST">
    @csrf
    <div class="col-12">
        <div class="row">
            <div class="col-md-12 col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Create New Purchase</h4>
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="supplierName" class="form-label">Supplier *</label>
                                    <select id="supplierName" name="supplierName" onchange="actProductList()" class="form-control" required>
                                    <option value="">-</option>
                                    <!--  form option show proccessing -->
                                    @if(!empty($supplierList) && count($supplierList)>0)
                                    @foreach($supplierList as $supplierData)
                                        <option value="{{$supplierData->id}}">{{$supplierData->name}}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 mt-4 p-0">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#supplier"><i class="las la-plus mr-2"></i>New Supplier</button>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="invoice" class="form-label">Invoice *</label>
                                    <input type="text" class="form-control" id="invoice" name="invoiceData" />
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

                            <div class="col-md-2 mt-4 p-0">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#newProduct"><i class="las la-plus mr-2"></i>New Product</button>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="reference" class="form-label">Reference *</label>
                                    <input type="text" class="form-control" id="reference" name="refData" />
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
                                <th>Serial</th>
                                <th>Purchase Qty</th>
                                <th>Current Stock</th>
                                <th>Buy Price</th>
                                <th>Sale Price(Ex. Vat)</th>
                                <th>Vat Include</th>
                                <th>Sale Price(Inc. Vat)</th>
                                <th>Profit %</th>
                                <th>Total Price</th>
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
                                    <select name="vatStatus" id="vatStatus" class="form-control" readonly>
                                        <option value="">-</option>
                                    </select>
                                </td>
                                <td width="9%">
                                    <input type="number" class="form-control" id="salingPriceWithVat" name="salingPriceWithVat" readonly />
                                </td>
                                <td width="9%">
                                    <input type="number" class="form-control" id="profitMargin" name="profitMargin" readonly />
                                </td>
                                <td width="9%">
                                    <input type="number" class="form-control" id="totalPrice" name="totalPrice" readonly />
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="header-title">
                            <h6 class="card-title">Other Details</h6>
                        </div>
                        <div class="mb-3 table-responsive product-table">
                            <table class="table mb-0 table-bordered rounded-0">
                                <thead class="bg-white text-uppercase">
                                    <tr>
                                        <th>Discount Type</th>
                                        <th>Discount Amount</th>
                                        <th>Discount Parcent</th>
                                        <th>Grand Total</th>
                                        <th>Paid Amount</th>
                                        <th>Due Amount</th>
                                        <th>Special Note</th>
                                    </tr>
                                </thead>
                                <tbody id="discountDetails">
                                    <tr>
                                        <td>
                                            <select name="discountStatus" id="discountStatus" onchange="discountType()" class="form-control" disabled>
                                                <option value="">-</option>
                                                <option value="1">Amount</option>
                                                <option value="2">Parcent</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="discountAmount" onkeyup="discountAmountChange()" name="discountAmount" readonly />
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="discountPercent" onkeyup="discountPercentChange()" name="discountPercent" readonly />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="grandTotal" name="grandTotal" readonly />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="paidAmount" name="paidAmount" value="0" onkeyup="dueCalculate()" readonly />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="dueAmount" name="dueAmount" readonly />
                                        </td>
                                        <td>
                                            <textarea class="form-control" id="specialNote" name="specialNote" readonly></textarea>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="saveButton" class="d-none  mt-2">
                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!--  serial number model -->

    <div class="modal fade" id="serialModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="serialModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fs-5">New Serial</h6>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="resetSerial">
                    <div class="p-0">
                        <label for="serialNumber" class="form-label">Serial Number</label>
                    </div>
                    <div id="serialNumberBox">
                        <div class="row">
                            <div class="col-10 mb-3">
                                <input type="" class="form-control" name="serialNumber[]" placeholder="Enter serial number" />
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-success btn-sm rounded-0" id="add-serial">Add Serial</button>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="resetSerial()" class="btn btn-warning" data-dismiss="modal">Clear</button>
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Save</button>
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</form>


<!-- Page end  -->
<!-- new supplier Modal -->
<div class="modal fade" id="supplier" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="supplier" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="supplier">Create Supplier</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                    <div class="card-body">
                <div class="row">
                    <form action="#" method="POST" id="supplierForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Name *</label>
                                    <input type="text" class="form-control" placeholder="Enter Name"  id="fullName" name="fullName"  required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" id="userMail" class="form-control" placeholder="Enter Email" name="mail"    required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Phone Number *</label>
                                    <input type="text" class="form-control" placeholder="Enter Phone Number" id="mobile" name="mobile" required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="inputState" class="form-label">Country *</label>
                                    
                                    <input type="text" class="form-control" placeholder="Enter The Country" id="country" name="country" required />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="inputState" class="form-label">State *</label>
                                    
                                    <input type="text" class="form-control" placeholder="Enter The State" id="state" name="state" required />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="inputState" class="form-label">City *</label>
                                
                                    <input type="text" class="form-control" placeholder="Enter The City" id="city" name="city" required />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="area" class="form-label">Area *</label>
                                
                                    <input type="text" class="form-control" placeholder="Enter The Area" id="area" name="area" required />
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary mr-2" id="add-supplier">Add Supplier</button>
                        <button type="reset" class="btn btn-danger">Reset</button>
                    </form>
                </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancle</button>
            </div>
        </div>
    </div>
</div>
<!-- new product Modal -->
<div class="modal fade" id="newProduct" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="newProduct" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5">Create Product</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                    <div class="card-body">

                <div class="row">
                    <form action="#" method="POST" id="productForm">
                    @csrf
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Product Name *</label>
                                <input type="text" class="form-control" placeholder="Enter Name" id="productNameModal" name="productName" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Brand Name *</label>
                                <label for="brandName" class="form-label"></label>
                                <select id="brandName" class="form-control" >
                                    <!--  form option show proccessing -->
                                    <option value="">Select</option>
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Product Category</label>
                                <label for="categoryName" class="form-label"></label>
                                <select id="categoryName" class="form-control" >
                                 
                                  <!--  form option show proccessing -->
                                    <option value="">Select</option>
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Product Unit</label>
                                <label for="unit" class="form-label"></label>
                                <select id="unit" class="form-control" >
                                 
                                  <!--  form option show proccessing -->
                                    <option value="">Select</option>
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
                                <label for="quantityName" class="form-label">Alert Quantity</label>
                                <input type="text" class="form-control" placeholder="Optional" id="quantityName"  />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="detailsName" class="form-label">Deatils</label>
                                <input type="text" class="form-control" id="detailsName" placeholder="Optional"  />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="barCodeNum" class="form-label">Barcode</label>
                                <input type="text" class="form-control" id="barCodeNum" placeholder="Optional"/>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary mt-4 mr-2" id="add-product"> Add Product</button>
                    <button type="reset" class="btn btn-danger mt-4 mr-2">Reset</button>
                </form>
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
        </div>
    </div>
</div>

<!-- brand modal -->
<div class="modal fade" id="createBrand" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="createBrand" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">Creat Brand</h6>
                <button type="button" class="btn-close"  onclick="closeModel('createBrand','brandForm')" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="POST" id="brandForm">
                    @csrf
                    <div class="mb-3">
                        <label for="NewBrand" class="form-label">Brand Name</label>
                        <input type="text" class="form-control" id="NewBrand" name="NewBrand" placeholder="Enter brand name" />
                    </div>
                  
                <button type="button" class="btn btn-primary" id="saveBrand">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModel('createBrand','brandForm')">Cancel</button>
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
                <button type="button" class="btn-close"  onclick="closeModel('categoryModal','categoryForm')" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="categoryForm">
                    @csrf
                <div class="mb-3">
                    <label for="NewCategory" class="form-label">Category</label>
                    <input type="text" class="form-control" id="NewCategory" name="NewCategory" placeholder="Enter Category name" />
                </div>
                <button type="button" class="btn btn-primary" id="add-category">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModel('categoryModal','categoryForm')">Cancel</button>
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
                <button type="button" class="btn-close" onclick="closeModel('productUnitModal','productUnitForm')" aria-label="Close"></button>
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
                <button type="button" class="btn btn-light" onclick="closeModel('productUnitModal','productUnitForm')">Cancel</button>
            </div>
        </div>
    </div>
</div>






@endsection

@include('customScript')
