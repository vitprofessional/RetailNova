@extends('include') @section('backTitle')Expense report @endsection @section('container')
<div class="row">
    <div class="col-12 mb-3">
        <h4>Expense Report</h4>
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
                        <th>Date</th>
                        <th>Expense Type</th>
                        <th>Amount</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="2">Total : </td>
                        <td>10</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
 <div class="card ">
    <div class="card-body">
        <div class="row">
            <div class="col-12 mb-3">
                <h6>Total Amount By Expense Type</h6>
            </div>
        </div>
        <div class="rounded mb-3 table-responsive product-table">
            <table class="data-tables table mb-0 table-bordered">
                <thead class="bg-white text-uppercase">
                    <tr>
                        <th>Expense Type</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection