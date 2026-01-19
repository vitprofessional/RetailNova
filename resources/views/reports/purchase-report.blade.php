@extends('include')
@section('backTitle')
Purchase Report
@endsection
@section('container')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">Purchase Report</h3>
                        <p class="mb-0">Detailed purchase transactions and supplier analysis.</p>
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
                <form method="GET" action="{{ route('reports.purchases') }}" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-control">
                            <option value="">All Suppliers</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
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
    
    <!-- Summary Card -->
    <div class="col-lg-12">
        <div class="card card-block card-stretch card-height bg-info">
            <div class="card-body text-white">
                <h6 class="mb-2">Total Purchases</h6>
                <h3>@money($totalPurchases)</h3>
                <p class="mb-0">{{ $purchases->total() }} Transactions</p>
            </div>
        </div>
    </div>
    
    <!-- Purchases Table -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>Purchase Transactions</h5>
                <button onclick="window.print()" class="btn btn-sm btn-primary">
                    <i class="ri-printer-line"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>Date</th>
                                <th>Invoice No</th>
                                <th>Supplier</th>
                                <th class="text-right">Sub Total</th>
                                <th class="text-right">Discount</th>
                                <th class="text-right">Grand Total</th>
                                <th>Payment Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}</td>
                                    <td>{{ $purchase->invoice ?? 'N/A' }}</td>
                                    <td>{{ $purchase->supplier->name ?? 'Unknown Supplier' }}</td>
                                    <td class="text-right">@money($purchase->totalAmount ?? 0)</td>
                                    <td class="text-right">@money($purchase->disAmount ?? 0)</td>
                                    <td class="text-right"><strong>@money($purchase->grandTotal)</strong></td>
                                    <td>
                                        @php
                                            $due = floatval($purchase->dueAmount ?? 0);
                                        @endphp
                                        @if($due <= 0)
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($purchase->paidAmount > 0)
                                            <span class="badge bg-warning">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No purchases found for the selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($purchases->count() > 0)
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="5" class="text-right">Total:</th>
                                    <th class="text-right">@money($totalPurchases)</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $purchases->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
