@extends('include') @section('backTitle')Sales report @endsection @section('container')
<div class="row">
    <div class="col-12 mb-3">
        <h4>Sales Report</h4>
    </div>
</div>
<form action="" method="POST" >
    @csrf
    <div class="row ">
        <div class="col-md-3 ">
            <div class="form-group">
                <select id="categoryList" class="form-control" name="category"  >    
                    <option value="Select Period">Select Period</option>
                    <option value=""></option>
                </select>
            </div>
        </div>
        <div class="col-md-3 ">
            <div class="form-group ">
                <input type="text" class="form-control" placeholder="Select start date" id="accPayable" name="accPayable"   required />
            </div>
        </div>
        <div class="col-md-3 ">
            <div class="form-group ">
                <input type="text" class="form-control" placeholder="Select end date" id="accPayable" name="accPayable"   required />
            </div>
        </div>
    </div>
</form>


 <div class="card ">
    <div class="card-body">
        <div class="rounded mb-3 table-responsive product-table">
            <table class="data-tables table mb-0 table-bordered">
                <thead class="bg-white text-uppercase">
                    <tr>
                        <th>Reference</th>
                        <th>Created Date</th>
                        <th>Customer Name</th>
                        <th>Salse Person</th>
                        <th>Grand Total</th>
                        <th>Paid</th>
                        <th>Due Amount</th>
                        <th>Profit</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>
                            <div class="d-flex align-items-center list-action">
                                <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="View"
                                href="#"><i class="ri-eye-line mr-0"></i></a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">Total : </td>
                        <td>10</td>
                        <td>1000</td>
                        <td>0000</td>
                        <td>0000</td>
                        <td>000000</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


@endsection