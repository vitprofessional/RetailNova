 @extends('include')
 @section('backTitle') sale list @endsection
 @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<style>
    /* Small professional tweaks for sales table */
    .rn-ellipsis{max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
    table.data-tables tbody tr:hover{background-color:rgba(0,0,0,0.02)}
    .rn-col-compact{width:96px;}
    .rn-number{white-space:nowrap; text-align:right;}
    /* Grid: two action buttons per row */
    .rn-actions{display:grid; grid-template-columns: repeat(2, 36px); gap:8px; justify-content:center;}
    .rn-actions .btn{width:36px; height:36px; padding:0; font-size:1rem; display:flex; align-items:center; justify-content:center; border-radius:12px}
    .badge.bg-success, .badge.bg-warning, .badge.bg-danger{font-size:0.8rem}
</style>
<div class="row">
    <div class="col-md-12 col-sm-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <h4 class="mb-2 mb-sm-0">Sales</h4>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('newsale') }}" class="btn btn-sm btn-primary">New Sale</a>
                        <button id="exportCsvBtn" class="btn btn-sm btn-outline-secondary">Export CSV</button>
                    </div>
                    @include('partials.table-filters', [
                        'tableId' => 'salesTable',
                        'searchId' => 'globalSearch',
                        'selects' => [
                            ['id' => 'filterCustomer', 'label' => 'Customers', 'options' => \App\Models\Customer::orderBy('name')->get()],
                            ['id' => 'filterStatus', 'label' => 'Status', 'options' => ['Paid','Partial','Due']],
                        ],
                        'date' => true,
                        'searchPlaceholder' => 'Search invoice, customer, amount...'
                    ])
                </div>

                @include('partials.bulk-actions', ['deleteRoute' => 'sales.bulkDelete', 'entity' => 'Sales'])
                <div class="rounded mb-2 table-responsive product-table">
                    @php use Carbon\Carbon; @endphp
                    <table id="salesTable" class="data-tables table mb-0 table-striped table-bordered rn-table-pro">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th class="rn-col-compact">
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="selectAllSales" />
                                        <label for="selectAllSales" class="mb-0"></label>
                                    </div>
                                </th>
                                <th class="text-nowrap">Invoice</th>
                                <th class="text-left">Customer</th>
                                <th class="rn-number">Grand Total</th>
                                <th class="rn-number">Paid</th>
                                <th class="rn-number">Due</th>
                                <th>Status</th>
                                <th class="text-nowrap">Date</th>
                                <th class="rn-col-compact">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(!empty($saleList) && $saleList->count()>0)
                                @foreach($saleList as $sl)
                                    @php
                                        $customer = \App\Models\Customer::find($sl->customerId);
                                        $customerName = $customer ? $customer->name : '-';
                                        $grand = \App\Support\Currency::format($sl->grandTotal ?? 0);
                                        $paid  = \App\Support\Currency::format($sl->paidAmount ?? 0);
                                        $due   = \App\Support\Currency::format($sl->curDue ?? 0);
                                        $status = 'Due';
                                        if (isset($sl->curDue) && (float)$sl->curDue <= 0) {
                                            $status = 'Paid';
                                        } elseif (isset($sl->paidAmount) && (float)$sl->paidAmount > 0) {
                                            $status = 'Partial';
                                        }
                                        try { $dateFmt = Carbon::parse($sl->date)->format('d M Y'); } catch (\Exception $e) { $dateFmt = (string)($sl->date ?? '-'); }
                                    @endphp
                                    <tr data-customer="{{ $sl->customerId ?? '' }}" data-status="{{ $status }}" data-date="{{ $sl->date ?? '' }}">
                                        <td>
                                            <div class="checkbox d-inline-block">
                                                <input type="checkbox" class="checkbox-input bulk-select" value="{{ $sl->id }}" />
                                                <label class="mb-0"></label>
                                            </div>
                                        </td>
                                        <td class="rn-ellipsis text-nowrap">{{ $sl->invoice }}</td>
                                        <td class="text-left rn-ellipsis" title="{{ $customerName }}">{{ $customerName }}</td>
                                        <td class="rn-number">{{ $grand }}</td>
                                        <td class="rn-number">{{ $paid }}</td>
                                        <td class="rn-number">{{ $due }}</td>
                                        <td>
                                            @if($status === 'Paid')
                                                <span class="badge bg-success">Paid</span>
                                            @elseif($status === 'Partial')
                                                <span class="badge bg-warning">Partial</span>
                                            @else
                                                <span class="badge bg-danger">Due</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ $dateFmt }}</td>
                                        <td class="rn-actions text-center">
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('invoiceGenerate',['id'=>$sl->id]) }}" title="Print"><i class="las la-print"></i></a>
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('returnSale',['id'=>$sl->id]) }}" title="Return"><i class="las la-undo"></i></a>
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('sale.edit',['id'=>$sl->id]) }}" title="Edit"><i class="las la-edit"></i></a>
                                            <form method="POST" action="{{ route('delSale',['id'=>$sl->id]) }}" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="delete" title="Delete"><i class="las la-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9">No data found</td>
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
@include('partials.bulk-actions-script')
<script>
    (function(){
        // Try to initialize DataTable safely; if DataTables not present, fail silently
        try {
                if (window.jQuery && $.fn.DataTable) {
                var dt;
                try{
                    var tableEl = $('#salesTable');
                    // Avoid reinitialization: use isDataTable check
                    var already = (typeof $.fn.DataTable.isDataTable === 'function') ? $.fn.DataTable.isDataTable(tableEl[0]) : ($.fn.dataTable && $.fn.dataTable.isDataTable ? $.fn.dataTable.isDataTable('#salesTable') : false);
                    if(!already){
                        var dtOpts = {
                            responsive: true,
                            order: [[7, 'desc']],
                            columnDefs: [
                                { orderable: false, targets: [0,8] },
                                { className: 'text-right', targets: [3,4,5] }
                            ],
                        };
                        // If Buttons extension is available, enable some common export buttons
                        if ($.fn.dataTable && $.fn.dataTable.Buttons) {
                            dtOpts.dom = "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>t<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>";
                            dtOpts.buttons = [
                                { extend: 'copy', className: 'btn btn-sm btn-outline-secondary' },
                                { extend: 'csv', className: 'btn btn-sm btn-outline-secondary' },
                                { extend: 'excel', className: 'btn btn-sm btn-outline-secondary' },
                                { extend: 'print', className: 'btn btn-sm btn-outline-secondary' }
                            ];
                        } else {
                            dtOpts.dom = "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>t<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>";
                        }
                        dt = tableEl.DataTable(dtOpts);
                    } else {
                        dt = tableEl.DataTable(); // get existing instance
                    }

                    // Export CSV button: prefer Buttons API if present
                    $('#exportCsvBtn').off('click').on('click', function(){
                        try {
                            if ($.fn.dataTable && $.fn.dataTable.Buttons && dt && dt.buttons){
                                // try to trigger CSV action if available
                                var csvBtn = dt.buttons().container().find('.buttons-csv');
                                if(csvBtn && csvBtn.length){ csvBtn.click(); return; }
                            }
                        } catch(e) { /* fall through to fallback CSV */ }
                        // fallback: create CSV from table rows
                        var csv = [];
                        $('#salesTable thead tr').each(function(){
                            var row = [];
                            $(this).find('th').each(function(){ row.push($(this).text().trim()); });
                            csv.push(row.join(','));
                        });
                        $('#salesTable tbody tr').each(function(){
                            var row = [];
                            $(this).find('td').each(function(){ row.push('"'+($(this).text().trim().replace(/"/g,'""'))+'"'); });
                            csv.push(row.join(','));
                        });
                        var csvContent = csv.join('\n');
                        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                        var link = document.createElement('a');
                        var url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', 'sales-export-'+(new Date()).toISOString().slice(0,10)+'.csv');
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    });
                }catch(e){ console.warn('DataTable init failed', e); }
            }
        } catch (e) { console.warn('sales table init failed', e); }
    })();
</script>
@endsection