@extends('include') @section('backTitle')Service Provider List @endsection @section('container')

<div class="row mt-4">
    <div class="col-lg-12">
        <div class="d-flex flex-wrap flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h4 class="">Service Provide List</h4>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="table-responsive rounded mb-3">
            <table class="data-tables table mb-0 tbl-server-info">
                <thead class="bg-white text-uppercase">
                    <tr class="ligth ligth-data">
                        <th>
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="checkbox1" />
                                <label for="checkbox1" class="mb-0"></label>
                            </div>
                        </th>
                        <th>Customer Name</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Print</th>
                    </tr>
                </thead>
                <tbody class="ligth-body text-center">
                    <tr>
                        <td>
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="checkbox2" />
                                <label for="checkbox2" class="mb-0"></label>
                            </div>
                        </td>
                        <td>demon</td>
                        <td>10.0</td>
                        <td>10.10.2025</td>
                        <td>
                            <div class="d-flex align-items-center list-action">
                                <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete" href="#"><i class="ri-delete-bin-line mr-0"></i></a>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center list-action">
                                <a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit" href="#"><i class="ri-pencil-line mr-0"></i></a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
