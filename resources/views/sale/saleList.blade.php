 @extends('include')
 @section('backTitle') sale list @endsection
 @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <h4 class="mb-2 mb-sm-0">Sales</h4>
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
                    <table id="salesTable" class="data-tables table mb-0 table-bordered">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th class="rn-col-compact">
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="selectAllSales" />
                                        <label for="selectAllSales" class="mb-0"></label>
                                    </div>
                                </th>
                                <th>Invoice</th>
                                <th class="text-left">Customer</th>
                                <th>Grand Total</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="rn-col-compact">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(!empty($saleList) && $saleList->count()>0)
                                @foreach($saleList as $sl)
                                    @php
                                        $customer = \App\Models\Customer::find($sl->customerId);
                                        $customerName = $customer ? $customer->name : '-';
                                        $grand = is_numeric($sl->grandTotal ?? null) ? number_format($sl->grandTotal, 2) : ($sl->grandTotal ?? '0.00');
                                        $paid  = is_numeric($sl->paidAmount ?? null) ? number_format($sl->paidAmount, 2) : ($sl->paidAmount ?? '0.00');
                                        $due   = is_numeric($sl->curDue ?? null) ? number_format($sl->curDue, 2) : ($sl->curDue ?? '0.00');
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
                                        <td class="rn-ellipsis">{{ $sl->invoice }}</td>
                                        <td class="text-left rn-ellipsis" title="{{ $customerName }}">{{ $customerName }}</td>
                                        <td>{{ $grand }}</td>
                                        <td>{{ $paid }}</td>
                                        <td>{{ $due }}</td>
                                        <td>
                                            @if($status === 'Paid')
                                                <span class="badge bg-success">Paid</span>
                                            @elseif($status === 'Partial')
                                                <span class="badge bg-warning">Partial</span>
                                            @else
                                                <span class="badge bg-danger">Due</span>
                                            @endif
                                        </td>
                                        <td>{{ $dateFmt }}</td>
                                        <td>
                                            <div class="dropdown position-static">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right" style="min-width:140px; z-index:1200;">
                                                    <a class="dropdown-item" href="{{ route('invoiceGenerate',['id'=>$sl->id]) }}"><i class="las la-print mr-2"></i>Print</a>
                                                    <a class="dropdown-item" href="{{ route('returnSale',['id'=>$sl->id]) }}"><i class="las la-undo mr-2"></i>Return</a>
                                                    <div class="dropdown-divider"></div>
                                                    <form method="POST" action="{{ route('delSale',['id'=>$sl->id]) }}" style="display:block; margin:0;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger" data-confirm="delete"><i class="las la-trash-alt mr-2"></i>Delete</button>
                                                    </form>
                                                </div>
                                            </div>
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
@endsection