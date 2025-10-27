@extends('include') @section('backTitle') purchase return @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-header text-center" style="color: #c20c0cff;"><h4>Purchase Return</h4></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h5>Supplier Details</h5>
                <hr />
                <p><strong>Name:</strong></p>
                <p><strong>Mobile:</strong> </p>
                <p><strong>Address:</strong> </p>
            </div>
            <div class="col-md-4 mt-5">
                <p class="mt-1"><strong>Date:</strong> </p>
                <p><strong>Reference:</strong></p>
                <p><strong>Note:</strong></p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="card">
                    <div class="card-body">
                        <h6>Purchase Summary</h6>
                        <div class="row">
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Total:</label><input disabled="" class="form-control form-control-sm" type="text"  style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Paid Amount:</label><input disabled="" class="form-control form-control-sm" type="text"  style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Due Amount:</label><input disabled="" class="form-control form-control-sm" type="text"  style="width: 50%;" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row  product-table">
            <div class="col-md-12">
                <h4>Products for Return</h4>
                <table class="table mb-0 table-bordered rounded-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Purchase Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Select</th>
                            <th>Return Qty</th>
                            <th>Return Amount</th>
                            <th>Serial Nos (if any)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2</td>
                            <td>Crime Chake</td>
                            <td>20</td>
                            <td>10</td>
                            <td>200</td>
                            <td><input type="checkbox" /></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm" value="" /></td>
                            <td><input type="number" class="form-control form-control-sm" value="0" /></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3 text-end mx-5 text-danger"><h5>Total Return Amount: <span id="grandTotal">0</span></h5></div>
        <div class="row shadow p-3">
            <div class="col-12">
                <h5 class="card-title">Return Details</h5>
                <form>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Return Note:</label><textarea class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="d-flex gap-4 w-100">
                            <button type="submit" class="btn btn-success w-100">Submit Return</button>
                        </div>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="d-flex gap-4 w-100">
                            <button type="button" class="btn btn-success w-100">Return And Refund</button>
                        </div>
                    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@include('customScript')
