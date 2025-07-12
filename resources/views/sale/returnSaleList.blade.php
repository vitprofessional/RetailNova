@extends('include') @section('backTitle') sales return list @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="col-12 p-0 mt-0 mb-4">
                    <h4>Sales Return List</h4>
                </div>
                <div class="rounded mb-2 table-responsive product-table">
                    <table class="data-tables table mb-0 table-bordered ">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="checkbox1" />
                                        <label for="checkbox1" class="mb-0"></label>
                                    </div>
                                </th>
                                <th>Reference</th>
                                <th>Name</th>
                                <th>Grand Total</th>
                                <th>Paid Amount</th>
                                <th>Due</th>
                                <th>Created By</th>
                                <th>Date</th>
                                <th>Return</th>
                                <th>Delete</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="checkbox2" />
                                        <label for="checkbox2" class="mb-0"></label>
                                    </div>
                                </td>
                                <td>PUR-234</td>
                                <td>Hasnat Saimun</td>
                                <td>45000</td>
                                <td>400000</td>
                                <td>00</td>
                                <td>Sobuj</td>
                                <td>14.5.2025</td>
                                <td>
                                    <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="View"
                                        href="#"><i class="ri-eye-line mr-0"></i>
                                </td>
                                <td>
                                    <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="View"
                                        href="#"><i class="ri-eye-line mr-0"></i>
                                </td>
                                <td>
                                    <div class="list-action">
                                        <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete" href="#"><i class="ri-delete-bin-line mr-0"></i></a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection