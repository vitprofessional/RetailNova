@extends('include') 
    @section('backTitle') 
        new sale 
    @endsection 
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="{{ route('saveSale') }}" id="saveSaleForm" class="row" method="POST" data-action-template="/sale/save/data">
    @csrf
    @php
    $randomInvoiceNumber = 'INV-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    @endphp
    <div class="col-12">
        <!-- Route seed container for robust JS URL construction -->
           <div id="rn-route-seeds"
               data-newsale="{{ route('newsale') }}"
               data-products-template="{{ route('ajax.customer.products.public', ['id' => '__ID__']) }}"
               data-purchase-template="{{ route('ajax.sale.product.purchaseDetails.public', ['id' => '__ID__']) }}"
               data-purchase-by-id-template="{{ route('ajax.purchase.details.public', ['id' => '__ID__']) }}"
               data-product-details-template="{{ route('ajax.product.details.public', ['id' => '__ID__']) }}"></div>
        <div class="row">
            <div class="col-md-12 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" value="{{ date('Y-m-d') }}" name="date" placeholder="" required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Select Customer *</label>
                                    <label for="customerName" class="form-label"></label>
                                <select id="customerName" name="customerId" class="form-control" data-onchange="actSaleProduct()" required
                                    data-products-url="{{ route('ajax.customer.products.public', ['id' => '__ID__']) }}">
                                        <option value="">-</option>
                                    <!--  form option show proccessing -->
                                  @if(!empty($customerList) && count($customerList)>0)
                                  @foreach($customerList as $customerData)
                                    <option value="{{$customerData->id}}">{{$customerData->name}}</option>
                                    @endforeach
                                    @endif
                                </select>
                                </div>
                                <div class="small mt-1">
                                    <span id="prevDueDisplay" class="badge bg-warning text-dark">Previous Due: 0.00</span>
                                </div>
                                <div class="d-flex small mt-1 align-items-center">
                                    <span id="outOfStockNote" class="text-muted small me-2">&nbsp;</span>
                                    <button id="outOfStockBtn" type="button" class="btn btn-outline-secondary btn-sm" style="display:none;" data-toggle="modal" data-target="#outOfStockModal">View out-of-stock</button>
                                </div>
                            </div>
                            <div class="col-md-2 mt-3 p-0">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#customerModal"><i class="las la-plus mr-2"></i>add customer</button>
                            </div>
                            <div class="col-md-3 ">
                                <div class="form-group">
                                    <label for="invoice" class="form-label">Invoice *</label>
                                    <input type="text" class="form-control" id="invoice" name="invoice" value="{{ $randomInvoiceNumber }}" readonly />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Reference(if any)</label>
                                    <input type="text" name="reference" class="form-control" placeholder="Enter reference if any" />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                <label>Select Product*</label>
                                <select id="productName" name="productName" class="form-control js-sale-product-select" required disabled 
                                    data-purchase-url="{{ route('getSaleProductDetails', ['id' => '__ID__']) }}">
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
                            <div class="col-8">
                                <label for="note">Note(if applicable)</label>
                                <textarea class="form-control" id="note" name="note" placeholder="Enter some notes if you have"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
         <div class="card ">
            <div class="card-body  product-table">
                <div class="header-title">
                    <h6 class="card-title">Product Details</h6>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 table-bordered rounded-0">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>Remove</th>
                                <th>Product Name</th>
                                <th>Purchase Data</th>
                                <th>Qty</th>
                                <th>Sale Price</th>
                                <th>Total</th>
                                <th>Purchase</th>
                                <th>Purchase Total</th>
                                <th>Profit Margin</th>
                                <th>Profit Total</th>
                            </tr>
                        </thead>
                        <tbody id="productDetails">
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
                            <h6 class="card-title">Payment Details</h6>
                        </div>
                        <div class="mb-3 table-responsive product-table">
                            <table class="table mb-0 table-bordered rounded-0">
                                <thead class="bg-white text-uppercase">
                                    <tr>
                                        <th>Total</th>
                                        <th>Discount</th>
                                        <th>Grand Total</th>
                                        <th>Paid Amount</th>
                                        <th>Due Amount</th>
                                        <th>Previous Due</th>
                                        <th>Current Due</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentDetails">
                                    <tr>
                                        <td>
                                            <input type="number" class="form-control" id="totalSaleAmount" name="totalSaleAmount" value="0" readonly  />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="discountAmount" data-onkeyup="getDiscountAmount()"  name="discountAmount" value="0"  />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="grandTotal" name="grandTotal" value="0" readonly />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="paidAmount" data-onkeyup="dueSaleCalculate()" name="paidAmount" value="0"    />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="dueAmount" name="dueAmount" value="0" readonly  />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="prevDue" name="prevDue" value="0" readonly  />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="curDue" name="curDue" value="0" readonly  />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-md-flex  mt-2">
                            <div id="saleErrorSummary" class="me-3" style="flex:1"></div>
                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                        </div>
                        <div class="mt-2">
                            <span id="totalOutstandingDisplay" class="badge bg-danger">Total Outstanding: 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- start_model -->
 <div class="modal fade" id="customerModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="customerModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5" id="customerModal">Creat Customer</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST" id="customerForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Name *</label>
                                        <input type="text" class="form-control" placeholder="Enter Name" id="fullName" name="fullName" required />
                                        <div class="help-block with-errors"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Email *</label>
                                        <input type="email" class="form-control" placeholder="Enter Email" id="mail" name="mail" required />
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
                                        <label for="inputState" class="form-label">Area *</label>

                                        <input type="text" class="form-control" placeholder="Enter The area" id="area" name="area" required />
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary mr-2 "  id="add-customer">Add Customer</button>
                            <button type="reset" class="btn btn-danger">Reset</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- end_model -->
<!-- Out of Stock Modal -->
<div class="modal fade" id="outOfStockModal" tabindex="-1" aria-labelledby="outOfStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="outOfStockModalLabel">Out of Stock Items</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="outOfStockList">
                    <p class="text-muted">No out-of-stock items.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @include('customScript')
@endsection