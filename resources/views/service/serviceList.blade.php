@extends('include') @section('backTitle')Service Provider List @endsection @section('container')
<div class="card">
    <div class="card-body">
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="d-flex flex-wrap flex-wrap align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="">Provided Service List</h4>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="table-responsive rounded mb-3 p-2">
                    <div class="d-flex flex-wrap align-items-end mb-3" style="gap:.5rem;">
                        <div class="rn-search-box" style="min-width:240px;">
                            <span class="rn-search-icon"><i class="las la-search"></i></span>
                            <input type="text" class="rn-search-input rn-filter-input" placeholder="Search..." data-table-target="provideServiceTable">
                            <button class="rn-search-clear">&times;</button>
                        </div>
                        <div>
                            <label class="mb-0">Customer</label>
                            <select class="form-control rn-filter-input" data-table-target="provideServiceTable" data-filter-for="customer" style="min-width:180px;">
                                <option value="">All</option>
                                @foreach(($customerList ?? []) as $c)
                                    <option value="{{ strtolower($c->name) }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-0">Service</label>
                            <select class="form-control rn-filter-input" data-table-target="provideServiceTable" data-filter-for="service" style="min-width:160px;">
                                <option value="">All</option>
                                @foreach(($serviceNames ?? []) as $sn)
                                    <option value="{{ strtolower($sn) }}">{{ $sn }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-0">From</label>
                            <input type="date" class="form-control rn-filter-input" data-table-target="provideServiceTable" data-filter-date="from">
                        </div>
                        <div>
                            <label class="mb-0">To</label>
                            <input type="date" class="form-control rn-filter-input" data-table-target="provideServiceTable" data-filter-date="to">
                        </div>
                    </div>
                    @include('partials.bulk-actions', ['deleteRoute' => 'providedServices.bulkDelete', 'printRoute' => 'providedServices.bulkPrint', 'pdfRoute' => 'providedServices.bulkPrintPdf', 'entity' => 'Provided Services'])
                    <table class="data-tables table mb-0 tbl-server-info" id="provideServiceTable">
                        <thead class="bg-white text-uppercase">
                            <tr class="ligth ligth-data">
                                <th>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="selectAllProvidedServices" />
                                        <label for="selectAllProvidedServices" class="mb-0"></label>
                                    </div>
                                </th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="ligth-body text-center">
                            @forelse(($provideList ?? []) as $row)
                            <tr data-date="{{ optional($row->created_at)->format('Y-m-d') }}" data-customer="{{ $row->customer_name ?? 'Customer #'.$row->customerName }}">
                                <td>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input bulk-select" id="bulk-select-{{ $row->id }}" value="{{ $row->id }}" />
                                        <label for="bulk-select-{{ $row->id }}" class="mb-0"></label>
                                    </div>
                                </td>
                                <td class="text-left">{{ $row->customer_name ?? 'Customer #'.$row->customerName }}</td>
                                <td class="text-left">{{ $row->serviceName }}</td>
                                <td>{{ number_format($row->amount ?? (($row->rate ?? 0)*($row->qty ?? 1)),2) }}</td>
                                <td>{{ optional($row->created_at)->format('Y-m-d') }}</td>
                                <td class="list-action">
                                    <a href="{{ route('provideServiceView', $row->id) }}" class="badge badge-info" data-toggle="tooltip" title="View"><i class="ri-eye-line mr-0"></i></a>
                                    <a href="{{ route('provideServicePrint', $row->id) }}" target="_blank" class="badge badge-primary" data-toggle="tooltip" title="Print"><i class="ri-printer-line mr-0"></i></a>
                                    <a href="{{ route('delProvideService', $row->id) }}" class="badge badge-danger" data-toggle="tooltip" title="Delete"><i class="ri-delete-bin-line mr-0"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No provided services found.</td>
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
@include('partials.bulk-actions-script')
<script>
    // Initialize DataTables if available
    window.__jqOnReady(function(){
        try{
            if(window.jQuery && typeof jQuery.fn.DataTable === 'function'){
                var $t = jQuery('#provideServiceTable');
                if($t.length && !jQuery.fn.DataTable.isDataTable($t)){
                    $t.DataTable({ pageLength: 10, order: [], lengthChange:false });
                }
            }
        }catch(e){ console.warn('DataTable init failed', e); }
    });
</script>
@endsection
