@extends('include') @section('backTitle') damage product list @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="col-12 p-0 mt-0 mb-4">
                    <h4>Damage Product List</h4>
                </div>
                @include('partials.bulk-actions', ['deleteRoute' => 'damageProducts.bulkDelete', 'entity' => 'Damage Records'])
                <div class="rounded mb-2 table-responsive product-table">
                    <div class="rn-search-box rn-col-compact" style="min-width:240px;">
                        <span class="rn-search-icon"><i class="las la-search"></i></span>
                        <input type="text" class="rn-search-input rn-filter-input" placeholder="Search..." data-table-target="damageTable">
                        <button class="rn-search-clear">&times;</button>
                    </div>
                    <div class="d-flex mb-3 align-items-center flex-wrap" style="gap:.5rem;">
                        <div>
                            <label class="mb-0">Product</label>
                            <select class="form-control rn-filter-input" data-table-target="damageTable" data-filter-for="product" style="min-width:200px;">
                                <option value="">All products</option>
                                @foreach(($productList ?? []) as $p)
                                    <option value="{{ strtolower($p->name) }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-0">From</label>
                            <input type="date" class="form-control rn-filter-input" data-table-target="damageTable" data-filter-date="from">
                        </div>
                        <div>
                            <label class="mb-0">To</label>
                            <input type="date" class="form-control rn-filter-input" data-table-target="damageTable" data-filter-date="to">
                        </div>
                    </div>
                    <table id="damageTable" class="data-tables table mb-0 table-bordered ">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="selectAllDamage" />
                                        <label for="selectAllDamage" class="mb-0"></label>
                                    </div>
                                </th>
                                <th>Reference</th>
                                <th>Product Name</th>
                                <th>Total</th>
                                <th>Created By</th>
                                <th>Date</th>
                                <th>Print</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($damageList as $d)
                            <tr data-date="{{ optional($d->date)->format('Y-m-d') }}">
                                <td>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input bulk-select" value="{{ $d->id }}" />
                                        <label class="mb-0"></label>
                                    </div>
                                </td>
                                <td>{{ $d->reference ?? '-' }}</td>
                                <td>{{ $d->product ? $d->product->name : '-' }}</td>
                                <td>{{ number_format($d->total ?? 0, 2) }}</td>
                                <td>{{ $d->admin ? $d->admin->name : ($d->admin_id ? 'Admin #'.$d->admin_id : '-') }}</td>
                                <td>{{ \Carbon\Carbon::parse($d->date)->format('Y-m-d') }}</td>
                                <td class="list-action">
                                    <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="View" href="{{ route('damageProductView', $d->id) }}"><i class="ri-eye-line mr-0"></i></a>
                                    <a class="badge badge-primary mr-2" data-toggle="tooltip" data-placement="top" title="Print" href="{{ route('damageProductPrint', $d->id) }}" target="_blank"><i class="ri-printer-line mr-0"></i></a>
                                    <a class="badge badge-danger" data-toggle="tooltip" data-placement="top" title="Delete" href="{{ route('damageProductDelete', $d->id) }}"><i class="ri-delete-bin-line mr-0"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No damage records found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
    @parent
    <script>
    // Initialize DataTable for damage list if DataTables is available
    (function(){
        window.__jqOnReady(function(){
            try{
                if(window.jQuery && typeof jQuery.fn.DataTable === 'function'){
                    var $t = $('#damageTable');
                    if(!$t.length) return;
                    if(!$.fn.DataTable.isDataTable($t)){
                        var opts = { pageLength: 10, order: [], lengthChange: false };
                        // Enable buttons export if Buttons extension is present
                        if($.fn.dataTable && $.fn.dataTable.Buttons){
                            opts.dom = 'Bfrtip';
                            opts.buttons = [ 'copy', 'csv', 'excel', 'pdf', 'print' ];
                        }
                        $t.DataTable(opts);
                    }
                }
            }catch(e){ console.warn('DataTable init failed', e); }
        });
    })();
    </script>
    @include('partials.bulk-actions-script')
@endsection