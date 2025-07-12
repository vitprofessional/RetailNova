@extends('include') @section('backTitle')Stock report @endsection @section('container')
<div class="row">
    <div class="col-lg-3 col-md-3 ">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center  card-total-sale">
                    <div>
                        <p class="mb-2">Total Stock Value</p>
                        <h4>31.50</h4>
                    </div>
                </div> 
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-3 ">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center  card-total-sale">
                    <div>
                        <p class="mb-2"><i class="fa-solid fa-arrow-trend-up"></i> Total Product</p>
                        <h4>31.50</h4>
                    </div>
                </div> 
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-3 ">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center  card-total-sale">
                    <div>
                        <p class="mb-2">Total Brands</p>
                        <h4>31.50</h4>
                    </div>
                </div> 
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-3 ">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center  card-total-sale">
                    <div>
                        <p class="mb-2">Total Categoris</p>
                        <h4>31.50</h4>
                    </div>
                </div> 
            </div>
        </div>
    </div>
</div>
<form action="" method="POST" >
    @csrf
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label>By Product Brands *</label>
                <select id="categoryList" class="form-control" name="category" >    
                    <option value="">
                    <option value=""></option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>By Product Category *</label>
                <select id="categoryList" class="form-control" name="category" >    
                    <option value="">
                    <option value=""></option>
                </select>
            </div>
        </div>
    </div>
</form>


 <div class="card">
    <div class="card-body">
        <div class="rounded mb-3 table-responsive product-table">
            <div class="row">
                <div class="col-12 mb-3">
                    <h4>Product List</h4>
                </div>
            </div>
            <table class="data-tables table mb-0 table-bordered">
                <thead class="bg-white text-uppercase">
                    <tr>
                        <th>Product Name</th>
                        <th>Brand Name</th>
                        <th>Category Name</th>
                        <th>Total Purchase</th>
                        <th>Total Sold</th>
                        <th>Return</th>
                        <th>Alart Quantity</th>
                        <th>Rate</th>
                        <th>Stock</th>
                        <th>Stock Value</th>
                        <th>Purchase Date</th>
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
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="8" class="">Total Stock Value</td>
                        <td>10</td>
                        <td>1000</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


@endsection