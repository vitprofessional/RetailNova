@extends('include') @section('backTitle')Low product list report @endsection @section('container')
<div class="row">
    <div class="col-12 mb-3">
        <h4>Low Stock Product </h4>
    </div>
</div>
 <div class="card ">
    <div class="card-body">
        <div class="rounded mb-3 table-responsive product-table">
            <table class=" data-tables  table mb-0 table-bordered">
                <thead class="bg-white text-uppercase">
                    <tr>
                        <th>#</th>
                        <th>product Name</th>
                        <th>Brand Name</th>
                        <th>Category Name</th>
                        <th>Total Purchase</th>
                        <th>Total Sold</th>
                        <th>Total Return</th>
                        <th>Stock</th>
                        <th>Alart Quantity</h>
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
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


@endsection