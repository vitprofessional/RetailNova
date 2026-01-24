@extends('include') @section('backTitle') Purchase Return List @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="rounded mb-3 table-responsive product-table">
            <div class="row">
                <div class="col-12 mb-3">
                    <h4>Purchase Return List</h4>
                </div>
            </div>
            <table class="data-tables table mb-0 table-bordered rn-table-pro">
                <thead class="bg-white text-uppercase">
                    <tr>
                        <th>
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="checkbox1" />
                                <label for="checkbox1" class="mb-0"></label>
                            </div>
                        </th>
                        <th>Return ID</th>
                        <th>Purchase Invoice</th>
                        <th>Supplier Name</th>
                        <th>Product Name</th>
                        <th>Return Amount</th>
                        <th>Adjust Amount</th>
                        <th>Return Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @if(!empty($returnList) && $returnList->count()>0 ) 
                    @foreach($returnList as $return) 
                        <tr>
                            <td>
                                <div class="checkbox d-inline-block">
                                    <input type="checkbox" class="checkbox-input" id="checkbox2" />
                                    <label for="checkbox2" class="mb-0"></label>
                                </div>
                            </td>
                            <td>{{ $return->id }}</td>
                            <td>{{ $return->invoice }}</td>
                            <td>{{ $return->supplierName }}</td>
                            <td>{{ $return->productName }}</td>
                            <td>{{ number_format($return->totalReturnAmount, 2) }}</td>
                            <td>{{ number_format($return->adjustAmount, 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($return->created_at)->format('d-m-Y') }}</td>
                            <td>
                                <div class="d-flex align-items-center list-action">
                                    <a class="badge bg-info mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="View"
                                        href="#"><i class="ri-eye-line mr-0"></i></a>
                                </div>
                            </td>
                        </tr>
                    @endforeach 
                @else
                    <tr>
                        <td colspan="9" class="text-center">No purchase returns found</td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection