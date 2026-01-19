@extends('include')
@section('backTitle')
Business Report
@endsection
@section('container')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">Business Overview Report</h3>
                        <p class="mb-0">Complete business performance and analytics.</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line"></i> Back to Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Date Filter -->
    <div class="col-lg-12 mb-3">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.business') }}" class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-filter-3-line"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Key Metrics -->
    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="card card-block card-stretch card-height bg-primary">
                    <div class="card-body text-white">
                        <h6 class="mb-2">Total Sales</h6>
                        <h3>@money($totalSales)</h3>
                        <p class="mb-0">{{ $totalSalesCount }} Transactions</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card card-block card-stretch card-height bg-warning">
                    <div class="card-body text-white">
                        <h6 class="mb-2">Total Purchases</h6>
                        <h3>@money($totalPurchases)</h3>
                        <p class="mb-0">{{ $totalPurchasesCount }} Transactions</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card card-block card-stretch card-height bg-success">
                    <div class="card-body text-white">
                        <h6 class="mb-2">Profit</h6>
                        <h3>@money($profit)</h3>
                        <p class="mb-0">Gross Profit</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card card-block card-stretch card-height bg-info">
                    <div class="card-body text-white">
                        <h6 class="mb-2">Total Products</h6>
                        <h3>{{ $totalProducts }}</h3>
                        <p class="mb-0">{{ $lowStockProducts }} Low Stock</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Additional Stats -->
    <div class="col-lg-12 mt-3">
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Customer Stats</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Customers:</span>
                            <strong>{{ $totalCustomers }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total Receivables:</span>
                            <strong class="text-success">@money($totalReceivables)</strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Supplier Stats</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Suppliers:</span>
                            <strong>{{ $totalSuppliers }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total Payables:</span>
                            <strong class="text-danger">@money($totalPayables)</strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Inventory Stats</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Products:</span>
                            <strong>{{ $totalProducts }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Low Stock (≤10):</span>
                            <strong class="text-warning">{{ $lowStockProducts }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Sales Trend -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header">
                <h5>Monthly Sales Trend (Last 12 Months)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Month</th>
                                <th class="text-right">Total Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthlySales as $sale)
                                <tr>
                                    <td>{{ \Carbon\Carbon::create($sale->year, $sale->month)->format('F Y') }}</td>
                                    <td class="text-right">@money($sale->total)</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center">No sales data available</td>
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
