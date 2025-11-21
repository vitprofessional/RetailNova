@extends('include') @section('backTitle') purchase @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="{{ route('updatePurchase') }}" class="row" method="POST" id="savePurchase">
    @csrf
    <input type="hidden" name="purchaseId" value="{{ $purchaseData->id ?? '' }}" />
    <div class="col-12">
        <div class="row">
            <div class="col-md-12 col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-1 bg-transparent p-0">
                                    <li class="breadcrumb-item"><a href="{{ route('purchaseList') }}">Purchases</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                                </ol>
                            </nav>
                            <h4 class="card-title mb-0">Edit Purchase</h4>
                            <small class="text-muted">Update purchase details, serials and stock</small>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary" title="Save changes"> <i class="ri-save-line mr-1"></i> Update Purchase</button>
                            <a href="{{ route('purchaseList') }}" class="btn btn-outline-secondary ml-2">Back to list</a>
                        </div>
                    </div>
                    <div class="card-body py-3">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" id="date" name="purchaseDate" value="{{ !empty($purchaseData->purchase_date) ? \Carbon\Carbon::parse($purchaseData->purchase_date)->format('Y-m-d') : '' }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group d-flex align-items-center">
                                    <label for="supplierName" class="form-label mr-2">Supplier *</label>
                                    <select id="supplierName" name="supplierName" onchange="actProductList()" class="form-control" required>
                                    <option value="">-</option>
                                    <!--  form option show proccessing -->
                                    @if(!empty($supplierList) && count($supplierList)>0)
                                    @foreach($supplierList as $supplierData)
                                        <option value="{{$supplierData->id}}" {{ (!empty($purchaseData) && $purchaseData->supplier == $supplierData->id) ? 'selected' : '' }}>{{$supplierData->name}}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                    <span id="supplierBadge" class="badge badge-info ml-2" style="font-size:.9rem;">{{ $supplierList->firstWhere('id', $purchaseData->supplier)->name ?? '' }}</span>
                                </div>
                                </div>
                            
                            <div class="col-md-2 mt-4 p-0">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#supplier"><i class="las la-plus mr-2"></i>New Supplier</button>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="invoice" class="form-label">Invoice *</label>
                                    <input type="text" class="form-control" id="invoice" name="invoiceData" value="{{ $purchaseData->invoice ?? $generatedInvoice ?? '' }}" />
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label for="productName" class="form-label">Product *</label>
                                    <select id="productName" name="productName" class="form-control js-product-select" >
                                    <!--  form option show proccessing -->
                                        <option value="">Select</option>
                                    @if(!empty($productList) && count($productList)>0)
                                    @foreach($productList as $productData)
                                        @php
                                            $brand = \App\Models\Brand::find($productData->brand);
                                            $brandName = $brand ? ' - ' . $brand->name : '';
                                        @endphp
                                        <option value="{{$productData->id}}" {{ (!empty($purchaseData) && $purchaseData->productName == $productData->id) ? 'selected' : '' }}>{{$productData->name}}{{$brandName}}</option>
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
                                    <input type="text" class="form-control" id="reference" name="refData" value="{{ $purchaseData->reference ?? '' }}" />
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
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="bg-light text-uppercase small">
                            <tr>
                                <th style="min-width:220px">Product</th>
                                <th style="width:140px">Serials</th>
                                <th style="width:110px" class="text-center">Qty</th>
                                <th style="width:120px" class="text-center">Stock</th>
                                <th style="width:120px" class="text-right">Buy Price</th>
                                <th style="width:130px" class="text-right">Sale (Ex VAT)</th>
                                <th style="width:100px" class="text-center">VAT</th>
                                <th style="width:130px" class="text-right">Sale (Inc)</th>
                                <th style="width:120px" class="text-right">Profit %</th>
                                <th style="width:140px" class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody id="productDetails">
                            <tr>
                                <td>
                                    @php
                                        $selectedProduct = $productList->firstWhere('id', $purchaseData->productName);
                                        $productDisplay = $selectedProduct ? $selectedProduct->name : '';
                                        if($selectedProduct) {
                                            $brand = \App\Models\Brand::find($selectedProduct->brand);
                                            if($brand) {
                                                $productDisplay .= ' - ' . $brand->name;
                                            }
                                        }
                                    @endphp
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3" style="width:64px;height:64px;flex:0 0 64px;border-radius:6px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#f6f6f6;">
                                            @php
                                                $img = '';
                                                if(!empty($selectedProduct) && isset($selectedProduct->image) && $selectedProduct->image){
                                                    $img = asset('storage/'.$selectedProduct->image);
                                                }
                                            @endphp
                                            @if($img)
                                                <img src="{{ $img }}" alt="{{ $productDisplay }}" style="max-width:100%;max-height:100%;object-fit:cover;" />
                                            @else
                                                {{-- inline SVG placeholder --}}
                                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="4" fill="#e9ecef"/><path d="M7 9h10v6H7z" fill="#ced4da"/><path d="M9 11h2v2H9zM13 11h2v2h-2z" fill="#adb5bd"/></svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">{{ $productDisplay }}</div>
                                            <div class="small text-muted">Invoice: {{ $purchaseData->invoice ?? $generatedInvoice ?? '-' }} | Ref: {{ $purchaseData->reference ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-2">
                                        <button type="button" class="btn btn-outline-success btn-sm" data-toggle="modal" data-target="#serialModal">Add / View</button>
                                    </div>
                                    <div>
                                        @if(!empty($serials) && $serials->count() > 0)
                                            <ul class="list-unstyled mb-0 small">
                                                @foreach($serials as $s)
                                                    @php $label = $s->serialNumber ?? $s->serial ?? $s->serial_number ?? $s->number ?? $s->id; @endphp
                                                    <li class="d-flex align-items-center justify-content-between mb-1">
                                                        <span class="text-truncate">{{ $label }}</span>
                                                        <button type="button" class="text-danger ml-2 delete-serial btn btn-link p-0" data-id="{{ $s->id }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="text-muted small">No serials</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <input type="number" class="form-control form-control-sm text-center" id="quantity" name="quantity" min="1" step="1" value="{{ $purchaseData->qty ?? '' }}" onkeyup="totalPriceCalculate()" />
                                </td>
                                <td class="text-center align-middle">
                                    <input type="number" class="form-control form-control-sm text-center" id="currentStock" value="{{ $totalStock ?? ($stock->currentStock ?? 0) }}" readonly />
                                    <input type="hidden" name="currentStock" value="{{ $totalStock ?? ($stock->currentStock ?? 0) }}" />
                                </td>
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-sm text-right" id="buyPrice" name="buyPrice" value="{{ $purchaseData->buyPrice ?? '' }}" onkeyup="totalPriceCalculate()" step="0.01" />
                                </td>
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-sm text-right" id="salePriceExVat" name="salePriceExVat" value="{{ $purchaseData->salePriceExVat ?? '' }}" onkeyup="priceCalculation()" step="0.01" />
                                </td>
                                <td class="align-middle text-center">
                                    <select name="vatStatus" id="vatStatus" class="form-control form-control-sm" onchange="priceCalculation()">
                                        <option value="">-</option>
                                        <option value="1" {{ (!empty($purchaseData) && $purchaseData->vatStatus == 1) ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ (!empty($purchaseData) && $purchaseData->vatStatus == 0) ? 'selected' : '' }}>No</option>
                                    </select>
                                </td>
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-sm text-right" id="salePriceInVat" name="salePriceInVat" value="{{ $purchaseData->salePriceInVat ?? '' }}" readonly />
                                </td>
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-sm text-right" id="profitMargin" name="profitMargin" value="{{ $purchaseData->profit ?? '' }}" onkeyup="profitCalculation()" step="0.01" />
                                </td>
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-sm text-right font-weight-bold" id="totalAmount" name="totalAmount" value="{{ $purchaseData->totalAmount ?? '' }}" readonly />
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
                                            <select name="discountStatus" id="discountStatus" onchange="discountType()" class="form-control">
                                                <option value="">-</option>
                                                <option value="1" {{ (!empty($purchaseData) && $purchaseData->disType == 1) ? 'selected' : '' }}>Amount</option>
                                                <option value="2" {{ (!empty($purchaseData) && $purchaseData->disType == 2) ? 'selected' : '' }}>Parcent</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="discountAmount" onkeyup="discountAmountChange()" name="discountAmount" value="{{ $purchaseData->disAmount ?? '' }}"  />
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="discountPercent" onkeyup="discountPercentChange()" name="discountPercent" value="{{ $purchaseData->disParcent ?? '' }}"  />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="grandTotal" name="grandTotal" value="{{ $purchaseData->grandTotal ?? '' }}"  />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="paidAmount" name="paidAmount" value="{{ $purchaseData->paidAmount ?? 0 }}" onkeyup="dueCalculate()"  />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="dueAmount" name="dueAmount" value="{{ $purchaseData->dueAmount ?? '' }}"  />
                                        </td>
                                        <td>
                                            <textarea class="form-control" id="specialNote" name="specialNote" >{{ $purchaseData->specialNote ?? '' }}</textarea>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
                    {{-- Existing serials for this purchase (read-only list) --}}
                    <div class="mb-3">
                        <label class="form-label">Existing Serials</label>
                        <div>
                            @if(!empty($serials) && $serials->count() > 0)
                                @foreach($serials as $s)
                                    @php $label = $s->serialNumber ?? $s->serial ?? $s->serial_number ?? $s->number ?? $s->id; @endphp
                                    <div id="serial-row-{{ $s->id }}" class="d-flex align-items-center mb-1">
                                        <span class="mr-2">{{ $label }}</span>
                                        <button type="button" class="text-danger delete-serial btn btn-link p-0" data-id="{{ $s->id }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-muted">No serials for this purchase.</div>
                            @endif
                        </div>
                    </div>

                    <hr />

                    <div class="p-0">
                        <label for="serialNumber" class="form-label">Add Serial Number(s)</label>
                    </div>
                    <div id="serialNumberBox">
                        <div class="row">
                            <div class="col-10 mb-3">
                                <input type="text" class="form-control" name="serialNumber[]" placeholder="Enter serial number" />
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-success btn-sm rounded-0" id="add-serial">Add Serial</button>
                </div>
                    <div class="modal-footer">
                    <button type="button" onclick="resetSerial()" class="btn btn-warning">Clear</button>
                    <button type="button" class="btn btn-primary" id="save-serials">Save</button>
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
        <!-- actions moved to header toolbar -->
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
