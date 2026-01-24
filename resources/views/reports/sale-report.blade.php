@extends('include')
@section('backTitle')
Sale Report
@endsection
@section('container')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">Sale Report</h3>
                        <p class="mb-0">Detailed sales transactions and analysis.</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line"></i> Back to Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="col-lg-12 mb-3">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.sales') }}" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-control">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-filter-3-line"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-6">
                <div class="card card-block card-stretch card-height bg-success">
                    <div class="card-body text-white">
                        <h6 class="mb-2">Total Sales</h6>
                        <h3>@money($totalSales)</h3>
                        <p class="mb-0">{{ $sales->total() }} Transactions</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card card-block card-stretch card-height bg-primary">
                    <div class="card-body text-white">
                        <h6 class="mb-2">Total Profit</h6>
                        <h3>@money($totalProfit)</h3>
                        <p class="mb-0">From Sales</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sales Table -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>Sales Transactions</h5>
                <button onclick="window.print()" class="btn btn-sm btn-primary">
                    <i class="ri-printer-line"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover rn-table-pro">
                        <thead class="bg-light">
                            <tr>
                                <th>Date</th>
                                <th>Invoice No</th>
                                <th>Customer</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Discount</th>
                                <th class="text-right">Grand Total</th>
                                <th class="text-right">Profit</th>
                                <th>Payment Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $sale)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($sale->date)->format('d M Y') }}</td>
                                    <td>{{ $sale->invoice ?? 'N/A' }}</td>
                                    <td>{{ $sale->customer->name ?? 'Walk-in Customer' }}</td>
                                    <td class="text-right">@money($sale->totalSale ?? 0)</td>
                                    <td class="text-right">@money($sale->discountAmount ?? 0)</td>
                                    <td class="text-right"><strong>@money($sale->grandTotal)</strong></td>
                                    <td class="text-right text-success">@money($sale->grandTotal - ($sale->totalSale ?? 0))</td>
                                    <td>
                                        @php
                                            $curDue = floatval($sale->curDue ?? $sale->invoiceDue ?? 0);
                                        @endphp
                                        @if($curDue <= 0)
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($sale->paidAmount > 0)
                                            <span class="badge bg-warning">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No sales found for the selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($sales->count() > 0)
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="5" class="text-right">Total:</th>
                                    <th class="text-right">@money($totalSales)</th>
                                    <th class="text-right text-success">@money($totalProfit)</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $sales->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
