@extends('include') @section('backTitle') purchase list @endsection @section('container')
<div class="row">
    
    <div class="col-md-12 col-sm-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="col-12 p-0 mt-0 mb-4">
                    <h4>Purchase List</h4>
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
                                <th>Current Stock</th>
                                <th>Supplier</th>
                                <th>Details</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(!empty($purchaseList) && $purchaseList->count()>0)
                            @forelse($purchaseList as $purchase)
                            <tr>
                                <td>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="saleBox{{ $purchase->id }}" />
                                        <label for="saleBox{{ $purchase->id }}" class="mb-0"></label>
                                    </div>
                                </td>
                                <td>{{ $purchase->invoice }}</td>
                                <td>{{ $purchase->productName }}</td>
                                <td>{{ $purchase->grandTotal }}</td>
                                <td>{{ $purchase->paidAmount }}</td>
                                <td>{{ $purchase->dueAmount }}</td>
                                <td>{{ $purchase->currentStock }}</td>
                                <td>{{ $purchase->supplierName }}</td>
                                <td>
                                    <a href="{{ route('purchaseView',['id'=>$purchase->id]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i></a>
                                </td>
                                <td>-</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8">No data found</td>
                            </tr>
                            @endforelse
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection