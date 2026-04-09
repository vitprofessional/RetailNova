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
                    <table class="data-tables table mb-0 table-bordered rn-table-pro">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th style="width: 50px;">
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="selectAllReturns" />
                                        <label for="selectAllReturns" class="mb-0"></label>
                                    </div>
                                </th>
                                <th>Sale ID</th>
                                <th>Customer</th>
                                <th>Return Amount</th>
                                <th>Adjust Amount</th>
                                <th>Items Returned</th>
                                <th>Created By</th>
                                <th>Date</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($returns && count($returns) > 0)
                                @foreach($returns as $return)
                                    @php
                                        $sale = $return->sale;
                                        $customer = $sale ? \App\Models\Customer::find($sale->customerId) : null;
                                        $customerName = $customer ? $customer->name : '-';
                                        $itemCount = $return->items ? $return->items->count() : 0;
                                        $createdBy = $sale && $sale->salesperson ? $sale->salesperson->fullName : '-';
                                        $returnAmount = \App\Support\Currency::format($return->totalReturnAmount ?? 0);
                                        $adjustAmount = \App\Support\Currency::format($return->adjustAmount ?? 0);
                                        try { 
                                            $dateFmt = \Carbon\Carbon::parse($return->created_at)->format('d M Y');
                                        } catch (\Exception $e) { 
                                            $dateFmt = (string)($return->created_at ?? '-');
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="checkbox d-inline-block">
                                                <input type="checkbox" class="checkbox-input bulk-select" value="{{ $return->id }}" />
                                                <label class="mb-0"></label>
                                            </div>
                                        </td>
                                        <td>{{ $sale ? $sale->invoice : '-' }}</td>
                                        <td>{{ $customerName }}</td>
                                        <td>{{ $returnAmount }}</td>
                                        <td>{{ $adjustAmount }}</td>
                                        <td><span class="badge bg-info">{{ $itemCount }}</span></td>
                                        <td>{{ $createdBy }}</td>
                                        <td>{{ $dateFmt }}</td>
                                        <td>
                                            <div style="display: flex; gap: 4px; justify-content: center;">
                                                <a href="{{ route('returnSale', ['id' => $return->saleId]) }}" class="btn btn-sm btn-outline-secondary" title="View" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <form method="POST" action="{{ route('delSale', ['id' => $return->saleId]) }}" style="display: inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="delete" title="Delete" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="text-center">No sale returns found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @include('customScript')
    <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize select all checkbox
                const selectAllCheckbox = document.getElementById('selectAllReturns');
                const bulkSelects = document.querySelectorAll('.bulk-select');
                
                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', function() {
                        bulkSelects.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                    });
                }

                // Initialize DataTable if available
                try {
                    if (window.jQuery && $.fn.DataTable) {
                        $('#returnSalesTable').DataTable({
                            responsive: true,
                            order: [[7, 'desc']],
                            columnDefs: [
                                { orderable: false, targets: [0, 8] }
                            ]
                        });
                    }
                } catch(e) {}
            });
        })();
    </script>
@endsection