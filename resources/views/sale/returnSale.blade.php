@extends('include') @section('backTitle') sale list @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-header text-center"><h4>Sale Return</h4></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h5>Customer Details</h5>
                <hr />
                <p><strong>Name:</strong> Hasnat Saimun</p>
                <p><strong>Mobile:</strong> 01755048017</p>
                <p><strong>Address:</strong> cumilla</p>
            </div>
            <div class="col-md-4">
                <p><strong>Date:</strong> 03-07-2025</p>
                <p><strong>Reference:</strong> lk</p>
                <p><strong>Note:</strong></p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="card">
                    <div class="card-body">
                        <h6>Sale Summary</h6>
                        <div class="row">
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Total:</label><input disabled="" class="form-control form-control-sm" type="text" value="1,400" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Paid Amount:</label><input disabled="" class="form-control form-control-sm" type="text" value="0" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">Due Amount:</label><input disabled="" class="form-control form-control-sm" type="text" value="1,400" style="width: 50%;" />
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
                            <th>Sale Quantity</th>
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
                            <td>1</td>
                            <td>Roci Chanacur</td>
                            <td>10</td>
                            <td>120</td>
                            <td>1,200</td>
                            <td><input type="checkbox" /></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm" value="" /></td>
                            <td><input type="number" class="form-control form-control-sm" value="0" /></td>
                            <td></td>
                        </tr>
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
        <div class="mt-3 text-end mx-5 text-danger"><h5>Total Return Amount: 0</h5></div>
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
