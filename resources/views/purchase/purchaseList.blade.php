@extends('include') @section('backTitle') purchase list @endsection @section('container')
<div class="row">
    
    <div class="col-md-12 col-sm-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="col-12 p-0 mt-0 mb-4 d-flex justify-content-between align-items-center">
                    <h4>Purchase List</h4>
                    <a href="{{ route('returnPurchaseList') }}" class="btn btn-info">
                        <i class="ri-arrow-go-back-line"></i> View Returns
                    </a>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    @include('partials.table-filters', [
                        'tableId' => 'purchaseTable',
                        'searchId' => 'globalSearchPurchase',
                        'selects' => [
                            ['id' => 'filterSupplier', 'label' => 'Supplier', 'options' => \App\Models\Supplier::orderBy('name')->get()],
                        ],
                        'date' => true,
                        'searchPlaceholder' => 'Search invoice, product, supplier...'
                    ])
                </div>
                @include('partials.bulk-actions', ['deleteRoute' => 'purchases.bulkDelete', 'entity' => 'Purchases'])
                <div class="rounded mb-2 table-responsive product-table">
                    @php
                        $totalGrand = 0.0; $totalPaid = 0.0; $totalDue = 0.0; $totalStock = 0;
                    @endphp
                    <table id="purchaseTable" class="data-tables table table-hover table-bordered mb-0">
                        <thead class="bg-white text-uppercase small">
                            <tr>
                                <th style="width:34px"> <input type="checkbox" id="selectAllPurchases" /></th>
                                <th>Invoice</th>
                                <th>Product</th>
                                <th class="text-right">Grand Total</th>
                                <th class="text-right">Paid</th>
                                <th class="text-right">Due</th>
                                <th class="text-right">Current Stock</th>
                                <th>Supplier</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @if(!empty($purchaseList) && $purchaseList->count()>0)
                                @foreach($purchaseList as $purchase)
                                    @php
                                        $pid = $purchase->purchaseId ?? $purchase->id ?? null;
                                        $g = floatval($purchase->grandTotal ?? 0);
                                        $p = floatval($purchase->paidAmount ?? 0);
                                        $d = floatval($purchase->dueAmount ?? 0);
                                        $s = intval($purchase->currentStock ?? 0);
                                        // Get total returned quantity and amount for this purchase
                                        $returnedQty = \App\Models\ReturnPurchaseItem::where('purchaseId', $pid)->sum('qty');
                                        $returnedAmount = \App\Models\PurchaseReturn::where('purchaseId', $pid)->sum('totalReturnAmount');
                                        // Adjusted values
                                        $adjustedQty = max(0, ($purchase->qty ?? 0) - $returnedQty);
                                        $adjustedStock = max(0, $s - $returnedQty);
                                        $adjustedGrand = max(0, $g - $returnedAmount);
                                        $totalGrand += $adjustedGrand; $totalPaid += $p; $totalDue += $d; $totalStock += $adjustedStock;
                                    @endphp
                                    <tr>
                                        <td><input type="checkbox" class="row-select bulk-select" value="{{ $pid }}" data-id="{{ $pid }}" /></td>
                                        <td>{{ $purchase->invoice ?? '-' }}</td>
                                        <td>{{ $purchase->productName ?? '-' }}</td>
                                        <td class="text-right">{{ number_format($adjustedGrand, 2) }}
                                            @if($returnedAmount > 0)
                                                <span class="badge bg-warning" title="Returned">-{{ number_format($returnedAmount, 2) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($p, 2) }}</td>
                                        <td class="text-right">{{ number_format($d, 2) }}</td>
                                        <td class="text-right">{{ number_format($adjustedStock) }}
                                            @if($returnedQty > 0)
                                                <span class="badge bg-warning" title="Returned">-{{ $returnedQty }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $purchase->supplierName ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                                                <a href="{{ route('purchaseView',['id'=>$pid]) }}" class="btn btn-outline-primary" title="View"><i class="fa-solid fa-eye"></i></a>
                                                <a href="{{ route('returnPurchase',['id'=>$pid]) }}" class="btn btn-outline-warning" title="Return"><i class="fa-regular fa-turn-down-left"></i></a>
                                                <a href="{{ route('editPurchase',['id'=>$pid]) }}" class="btn btn-outline-success" title="Edit"><i class="ri-pencil-line"></i></a>
                                                <form method="POST" action="{{ route('delPurchase',['id'=>$pid]) }}" style="display:inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="Delete" data-confirm="Are you sure to delete this purchase?"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="text-center">No purchase records found</td>
                                </tr>
                            @endif
                        </tbody>
                        @if(!empty($purchaseList) && $purchaseList->count()>0)
                        <tfoot class="bg-light small">
                            <tr>
                                <th colspan="3" class="text-right">Totals</th>
                                <th class="text-right">{{ number_format($totalGrand,2) }}</th>
                                <th class="text-right">{{ number_format($totalPaid,2) }}</th>
                                <th class="text-right">{{ number_format($totalDue,2) }}</th>
                                <th class="text-right">{{ number_format($totalStock) }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                {{-- DataTables: prefer Vite bundle when available, otherwise load CDN fallback and initialize inline --}}
                @if(file_exists(public_path('build/manifest.json')))
                    @vite(['resources/js/purchase-datatables.js'])
                @else
                    <!-- Fallback styles -->
                    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
                    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />

                    <!-- Fallback scripts -->
                    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

                    @section('scripts')
                        @parent
                        <script>
                            // Initialize DataTable after jQuery is available
                            window.__jqOnReady(function(){
                                try{
                                    var $table = $('#purchaseTable');
                                    if ($table.length && !$.fn.dataTable.isDataTable($table[0])){
                                        $table.DataTable({
                                            dom: 'Bfrtip',
                                            buttons: [
                                                { extend: 'copy', className: 'btn btn-outline-secondary btn-sm' },
                                                { extend: 'csv', className: 'btn btn-outline-secondary btn-sm' },
                                                { extend: 'excel', className: 'btn btn-outline-secondary btn-sm' },
                                                { extend: 'pdf', className: 'btn btn-outline-secondary btn-sm' },
                                                { extend: 'print', className: 'btn btn-outline-secondary btn-sm' }
                                            ],
                                            order: [[3, 'desc']],
                                            responsive: true,
                                            columnDefs: [
                                                { targets: [3,4,5,6], className: 'dt-body-right' },
                                                { orderable: false, targets: [0,8] }
                                            ],
                                            pageLength: 25
                                        });

                                        $('#selectAllPurchases').on('change', function(){
                                            var checked = $(this).is(':checked');
                                            $('input.bulk-select').prop('checked', checked);
                                            if(typeof updateBulkPurchaseUI === 'function') updateBulkPurchaseUI();
                                        });
                                    }
                                }catch(e){ console.warn('purchase table init failed', e); }
                            });
                        </script>
                    @endsection
                @endif
                @include('partials.bulk-actions-script')
            </div>
        </div>
    </div>
</div>
@endsection