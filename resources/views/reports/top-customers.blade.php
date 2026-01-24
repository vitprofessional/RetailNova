@extends('include')
@section('backTitle')
Top Customers
@endsection
@section('container')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">Top Customers Report</h3>
                        <p class="mb-0">Identify your best customers by sales volume and order frequency.</p>
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
                <form method="GET" action="{{ route('reports.topCustomers') }}" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Number of Customers</label>
                        <select name="limit" class="form-control">
                            <option value="10" {{ $limit == 10 ? 'selected' : '' }}>Top 10</option>
                            <option value="20" {{ $limit == 20 ? 'selected' : '' }}>Top 20</option>
                            <option value="50" {{ $limit == 50 ? 'selected' : '' }}>Top 50</option>
                            <option value="100" {{ $limit == 100 ? 'selected' : '' }}>Top 100</option>
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
    
    <!-- Top Customers Table -->
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>Top {{ $limit }} Customers by Sales</h5>
                <button onclick="window.print()" class="btn btn-sm btn-primary">
                    <i class="ri-printer-line"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover rn-table-pro">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th class="text-center">Total Orders</th>
                                <th class="text-right">Total Spent</th>
                                <th>Last Purchase</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topCustomers as $index => $customer)
                                <tr>
                                    <td>
                                        @if($index == 0)
                                            <span class="badge bg-warning">🥇</span>
                                        @elseif($index == 1)
                                            <span class="badge bg-secondary">🥈</span>
                                        @elseif($index == 2)
                                            <span class="badge bg-danger">🥉</span>
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </td>
                                    <td><strong>{{ $customer->name }}</strong></td>
                                    <td>{{ $customer->mail ?? 'N/A' }}</td>
                                    <td>{{ $customer->mobile ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $customer->total_orders }}</span>
                                    </td>
                                    <td class="text-right">
                                        <strong class="text-success">@money($customer->total_spent)</strong>
                                    </td>
                                    <td>
                                        {{ $customer->last_purchase_date ? \Carbon\Carbon::parse($customer->last_purchase_date)->format('d M Y') : 'N/A' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No customer data found for the selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($topCustomers->count() > 0)
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="4" class="text-right">Total:</th>
                                    <th class="text-center">{{ $topCustomers->sum('total_orders') }}</th>
                                    <th class="text-right">@money($topCustomers->sum('total_spent'))</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
