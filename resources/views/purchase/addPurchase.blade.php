@extends('include') @section('backTitle') purchase @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="{{ route('savePurchase') }}" class="row" method="POST" id="savePurchase" data-onsubmit="handleFormSubmit">
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
                                    <input type="date" class="form-control" id="date" name="purchaseDate" required />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="supplierName" class="form-label">Supplier *</label>
                                    <select id="supplierName" name="supplierName" data-onchange="actProductList()" class="form-control" required>
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
                                    <input type="text" class="form-control" id="invoice" name="invoiceData" value="{{ $generatedInvoice ?? '' }}" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="productName" class="form-label">Product *</label>
                                    <!-- productAdd is used to pick a product to append as a purchase row -->
                                    <select id="productName" name="productAdd" class="form-control js-product-select">
                                    <!--  form option show proccessing -->
                                        <option value="">Select</option>
                                    @if(!empty($productList) && count($productList)>0)
                                    @foreach($productList as $productData)
                                        @php
                                            $brand = \App\Models\Brand::find($productData->brand);
                                            $brandName = $brand ? ' - ' . $brand->name : '';
                                        @endphp
                                        <option value="{{$productData->id}}">{{$productData->name}}{{$brandName}}</option>
                                    @endforeach
                                    @endif
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2 mt-4 p-0">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#newProduct"><i class="las la-plus mr-2"></i>New Product</button>
                            </div>
                            <div class="col-md-2 mt-4 p-0">
                                <button type="button" id="addProductRow" class="btn btn-primary btn-sm">Add To List</button>
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
                            <!-- purchase rows will be appended here -->
                        </tbody>
                    </table>
                    <!-- Row template (used by JS) -->
                    <template id="purchase-row-template">
                        <tr class="product-row" data-idx="__IDX__">
                            <td width="20%">
                                <input type="hidden" name="productName[]" value="__PRODUCT_ID__" />
                                <input type="text" class="form-control" name="selectProductName[]" value="__PRODUCT_NAME__" readonly />
                            </td>
                            <td width="8%">
                                <button type="button" class="btn btn-sm btn-outline-secondary open-serials" data-idx="__IDX__">Serials</button>
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control quantity" id="quantity__IDX__" name="quantity[]" min="1" step="1" value="1" />
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control current-stock" id="currentStock__IDX__" name="currentStock[]" readonly />
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control" id="buyPrice__IDX__" name="buyPrice[]" />
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control sale-price" id="salePriceExVat__IDX__" name="salePriceExVat[]" />
                            </td>
                            <td width="9%">
                                <div class="d-flex align-items-center">
                                    <div class="form-check mr-2">
                                        <input type="checkbox" class="form-check-input include-vat" id="includeVat__IDX__" />
                                        <label class="form-check-label" for="includeVat__IDX__">Yes</label>
                                    </div>
                                    <!-- Removed dropdown; use numeric vatPercent input instead -->
                                    <input type="number" name="vatPercent[]" class="form-control vat-percent" value="10" step="0.01" style="margin-top:0; width:100px;" disabled />
                                </div>
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control sale-price-inc" id="salePriceInVat__IDX__" name="salePriceInVat[]" readonly />
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control" id="profitMargin__IDX__" name="profitMargin[]" readonly />
                            </td>
                            <td width="9%">
                                <input type="number" class="form-control" id="totalAmount__IDX__" name="totalAmount[]" readonly />
                            </td>
                            <td width="4%">
                                <button type="button" class="btn btn-danger btn-sm remove-row">Remove</button>
                            </td>
                        </tr>
                    </template>
                </div>
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
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <div class="form-group form-check">
                                    <input type="checkbox" id="includeVat" class="form-check-input" />
                                    <label for="includeVat" class="form-check-label">Include VAT?</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="vatPercent" class="form-label">VAT (%)</label>
                                    <input type="number" id="vatPercent" class="form-control" value="10" min="0" max="100" step="0.01" />
                                </div>
                            </div>
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
                                            <select name="discountStatus" id="discountStatus" data-onchange="discountType()" class="form-control">
                                                <option value="">-</option>
                                                <option value="1">Amount</option>
                                                <option value="2">Parcent</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="discountAmount" data-onkeyup="discountAmountChange()" name="discountAmount" />
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="discountPercent" data-onkeyup="discountPercentChange()" name="discountPercent" />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="grandTotal" name="grandTotal" readonly />
                                            <small class="text-muted">Grand total is calculated from Buy Price × Qty. Sale Price affects profit only.</small>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="paidAmount" name="paidAmount" value="0" data-onkeyup="dueCalculate()" />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="dueAmount" name="dueAmount" readonly />
                                        </td>
                                        <td>
                                            <textarea class="form-control" id="specialNote" name="specialNote"></textarea>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="saveButton" class="mt-2">
                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                            <button class="btn btn-warning btn-sm" type="button" data-onclick="debugForm()">Debug</button>
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
                    <button type="button" data-onclick="resetSerial()" class="btn btn-warning" data-dismiss="modal">Clear</button>
                    <button type="button" id="saveSerials" class="btn btn-primary" data-dismiss="modal">Save</button>
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
                    <form action="{{ route('createSupplier') }}" method="GET" id="supplierForm" data-ajax="true" data-target="#supplierName" data-modal-id="supplier">
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
                                    <input type="email" id="userMail" class="form-control" placeholder="Enter Email" name="email"    required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Phone Number *</label>
                                    <input type="text" class="form-control" placeholder="Enter Phone Number" id="mobile" name="phoneNumber" required />
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
                        <input type="hidden" name="openingBalance" value="0" />
                        <button type="submit" class="btn btn-primary mr-2" id="add-supplier">Add Supplier</button>
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
                    <form action="{{ route('createProduct') }}" method="GET" id="productForm" data-ajax="true" data-target="#productName" data-modal-id="newProduct">
                    @csrf
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Product Name *</label>
                                <input type="text" class="form-control" placeholder="Enter Name" id="productNameModal" name="fullName" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Brand Name *</label>
                                <label for="brandName" class="form-label"></label>
                                <select id="brandName" class="form-control" name="brand">
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
                                <select id="categoryName" class="form-control" name="category">
                                 
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
                                <select id="unit" class="form-control" name="unitName">
                                 
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
                                <input type="text" class="form-control" placeholder="Optional" id="quantityName" name="quantity" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="detailsName" class="form-label">Deatils</label>
                                <input type="text" class="form-control" id="detailsName" name="details" placeholder="Optional"  />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="barCodeNum" class="form-label">Barcode</label>
                                <input type="text" class="form-control" id="barCodeNum" name="barCode" placeholder="Optional"/>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-4 mr-2" id="add-product"> Add Product</button>
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
                <button type="button" class="btn-close"  data-onclick="closeModel('createBrand','brandForm')" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('createBrand') }}" method="GET" id="brandForm" data-ajax="true" data-target="#brandName" data-modal-id="createBrand">
                    @csrf
                    <div class="mb-3">
                        <label for="NewBrand" class="form-label">Brand Name</label>
                        <input type="text" class="form-control" id="NewBrand" name="name" placeholder="Enter brand name" />
                    </div>
                <button type="submit" class="btn btn-primary" id="saveBrand">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-onclick="closeModel('createBrand','brandForm')">Cancel</button>
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
                <button type="button" class="btn-close"  data-onclick="closeModel('categoryModal','categoryForm')" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('createCategory') }}" method="GET" id="categoryForm" data-ajax="true" data-target="#categoryName" data-modal-id="categoryModal">
                    @csrf
                <div class="mb-3">
                    <label for="NewCategory" class="form-label">Category</label>
                    <input type="text" class="form-control" id="NewCategory" name="name" placeholder="Enter Category name" />
                </div>
                <button type="submit" class="btn btn-primary" id="add-category">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-onclick="closeModel('categoryModal','categoryForm')">Cancel</button>
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
                <button type="button" class="btn-close" data-onclick="closeModel('productUnitModal','productUnitForm')" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('createProductUnit') }}" method="GET" id="productUnitForm" data-ajax="true" data-target="#unit" data-modal-id="productUnitModal">
                    @csrf
                <div class="mb-3">
                    <label for="productUnitName" class="form-label">Product Unit</label>
                    <input type="text" class="form-control" id="productUnitName" name="name" placeholder="Enter Product Unit name" />
                </div>
                <button type="submit" class="btn btn-primary" id="add-productUnit">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-onclick="closeModel('productUnitModal','productUnitForm')">Cancel</button>
            </div>
        </div>
    </div>
</div>






@endsection

@section('scripts')
    @include('customScript')
@endsection
