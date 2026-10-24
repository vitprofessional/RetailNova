@extends('include')
@section('backTitle'){{ __('common.heading_business_report') }}@endsection
@section('container')
<div class="row rn-report-page">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">{{ __('settings.business_overview_report') }}</h3>
                        <p class="mb-0">{{ __('settings.complete_business_performance_and_analytics') }}</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line"></i> {{ __('settings.back_to_reports') }}
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
                        <label class="form-label">{{ __('settings.start_date') }}</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('settings.end_date') }}</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-filter-3-line"></i> {{ __('settings.filter') }}
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
                        <h6 class="mb-2">{{ __('settings.total_sales') }}</h6>
                        <h3>@money($totalSales)</h3>
                        <p class="mb-0">{{ $totalSalesCount }} {{ __('common.transactions') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-block card-stretch card-height bg-warning">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ __('settings.total_purchases') }}</h6>
                        <h3>@money($totalPurchases)</h3>
                        <p class="mb-0">{{ $totalPurchasesCount }} {{ __('common.transactions') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-block card-stretch card-height bg-success">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ __('settings.profit') }}</h6>
                        <h3>@money($profit)</h3>
                        <p class="mb-0">{{ __('settings.gross_profit') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-block card-stretch card-height bg-info">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ __('settings.total_products') }}</h6>
                        <h3>{{ $totalProducts }}</h3>
                        <p class="mb-0">{{ $lowStockProducts }} {{ __('common.low_stock') }}</p>
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
                        <h6 class="mb-3">{{ __('settings.customer_stats') }}</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('settings.total_customers') }}:</span>
                            <strong>{{ $totalCustomers }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>{{ __('settings.total_receivables') }}:</span>
                            <strong class="text-success">@money($totalReceivables)</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">{{ __('settings.supplier_stats') }}</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('settings.total_suppliers') }}:</span>
                            <strong>{{ $totalSuppliers }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>{{ __('settings.total_payables') }}:</span>
                            <strong class="text-danger">@money($totalPayables)</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">{{ __('settings.inventory_stats') }}</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('settings.total_products') }}:</span>
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
                <h5>{{ __('settings.monthly_sales_trend_last_12_months') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table rn-report-table table-bordered rn-table-pro">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('settings.month') }}</th>
                                <th class="text-right">{{ __('settings.total_sales') }}</th>
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
                                    <td colspan="2" class="text-center">{{ __('settings.no_sales_data_available') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@section('scripts')
    @include('components.mobile.report-card-script')
@endsection
@endsection
