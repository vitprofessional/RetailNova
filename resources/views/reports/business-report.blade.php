@extends('include')
@section('backTitle'){{ app(\App\Support\MobileView::class)->isMobile() ? __('reports.mobile_business_overview') : __('common.heading_business_report') }}@endsection
@section('container')
@mobile
@php $weeklyPeak=max(1,(float)$weeklySales->max('total')); @endphp
<main class="rn-report-mobile-overview" data-rn-report-overview>
    <header class="rn-report-mobile-header">
        <div class="rn-report-mobile-title">
            <button type="button" class="rn-report-download" data-rn-report-download aria-label="{{ __('reports.download') }}"><i class="ri-download-line" aria-hidden="true"></i></button>
        </div>
        <nav class="rn-report-periods" aria-label="{{ __('reports.period') }}">
            @foreach($periodRanges as $key=>$range)
                <a href="{{ route('reports.business',$range) }}" @if($period===$key) aria-current="page" @endif>{{ __('reports.period_'.$key) }}</a>
            @endforeach
            <button type="button" data-rn-custom-period aria-expanded="{{ $period==='custom'?'true':'false' }}">{{ __('reports.period_custom') }}</button>
        </nav>
        <form method="GET" action="{{ route('reports.business') }}" class="rn-report-custom-range" role="dialog" aria-modal="true" aria-label="{{ __('reports.filter') }}" @if($period!=='custom') hidden @endif>
            <input type="hidden" name="period" value="custom">
            <label><span>{{ __('reports.start_date') }}</span><input type="date" name="start_date" value="{{ $startDate }}"></label>
            <label><span>{{ __('reports.end_date') }}</span><input type="date" name="end_date" value="{{ $endDate }}"></label>
            <button type="submit">{{ __('reports.apply_period') }}</button>
        </form>
    </header>

    <section class="rn-report-mobile-kpis" aria-label="{{ __('reports.summary') }}">
        <article class="rn-report-mobile-kpi" data-rn-export-row data-label="{{ __('mobile.sale') }}" data-value="{{ \App\Support\LocalizedNumber::money($totalSales) }}"><span><i class="is-teal"></i>{{ __('mobile.sale') }}</span><strong>{{ \App\Support\LocalizedNumber::money($totalSales) }}</strong></article>
        <article class="rn-report-mobile-kpi" data-rn-export-row data-label="{{ __('settings.profit') }}" data-value="{{ \App\Support\LocalizedNumber::money($profit) }}"><span><i class="is-green"></i>{{ __('settings.profit') }}</span><strong>{{ \App\Support\LocalizedNumber::money($profit) }}</strong></article>
        <article class="rn-report-mobile-kpi" data-rn-export-row data-label="{{ __('reports.expenses') }}" data-value="{{ \App\Support\LocalizedNumber::money($totalExpenses) }}"><span><i class="is-amber"></i>{{ __('reports.expenses') }}</span><strong>{{ \App\Support\LocalizedNumber::money($totalExpenses) }}</strong></article>
        <article class="rn-report-mobile-kpi" data-rn-export-row data-label="{{ __('reports.new_due') }}" data-value="{{ \App\Support\LocalizedNumber::money($newDue) }}"><span><i class="is-red"></i>{{ __('reports.new_due') }}</span><strong>{{ \App\Support\LocalizedNumber::money($newDue) }}</strong></article>
    </section>

    <section class="rn-report-mobile-card rn-report-week-chart">
        <div class="rn-report-mobile-card-heading"><h2>{{ __('reports.seven_day_sales') }}</h2><span>{{ __('reports.daily_sales') }}</span></div>
        <div class="rn-report-bars" role="img" aria-label="{{ __('reports.seven_day_sales') }}">
            @foreach($weeklySales as $day)
                <div class="rn-report-bar-column" data-rn-export-row data-label="{{ $day['date'] }}" data-value="{{ \App\Support\LocalizedNumber::money($day['total']) }}">
                    <small>{{ \App\Support\LocalizedNumber::money($day['total']) }}</small>
                    <span class="rn-report-bar" style="height: {{ $day['total'] > 0 ? max(8,round(($day['total']/$weeklyPeak)*100)) : 4 }}%"></span>
                    <span class="rn-report-bar-label">{{ $day['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rn-report-mobile-card rn-report-top-products">
        <h2>{{ __('reports.top_five_products') }}</h2>
        @forelse($topProducts as $index=>$product)
            <div class="rn-report-top-product" data-rn-export-row data-label="{{ $product['name'] }}" data-value="{{ \App\Support\LocalizedNumber::count($product['quantity']) }}">
                <span class="rn-report-rank">{{ \App\Support\LocalizedNumber::count($index+1) }}</span><strong>{{ $product['name'] }}</strong><span>{{ \App\Support\LocalizedNumber::count($product['quantity']) }}</span>
            </div>
        @empty
            <p class="rn-report-empty">{{ __('reports.no_top_products') }}</p>
        @endforelse
    </section>
</main>
@else
<div class="row rn-report-page">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center rn-report-header">
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
                <form method="GET" action="{{ route('reports.business') }}" class="row align-items-end rn-report-date-filter">
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
        <div class="row rn-report-kpi-grid">
            <div class="col-lg-3 col-md-6">
                <div class="card card-block card-stretch card-height bg-primary rn-report-kpi-card rn-report-kpi-card--sales">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ __('settings.total_sales') }}</h6>
                        <h3><x-mobile.report-money :amount="$totalSales" /></h3>
                        <p class="mb-0"><x-mobile.report-count :value="$totalSalesCount" /> {{ __('common.transactions') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-block card-stretch card-height bg-warning rn-report-kpi-card rn-report-kpi-card--purchases">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ __('settings.total_purchases') }}</h6>
                        <h3><x-mobile.report-money :amount="$totalPurchases" /></h3>
                        <p class="mb-0"><x-mobile.report-count :value="$totalPurchasesCount" /> {{ __('common.transactions') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-block card-stretch card-height bg-success rn-report-kpi-card rn-report-kpi-card--profit">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ $profit < 0 ? __('common.total_loss') : __('settings.profit') }}</h6>
                        <h3><x-mobile.report-money :amount="$profit" /></h3>
                        <p class="mb-0">{{ __('settings.gross_profit') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-block card-stretch card-height bg-info rn-report-kpi-card rn-report-kpi-card--products">
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
        <div class="row rn-report-summary-grid">
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
                            <strong class="text-success"><x-mobile.report-money :amount="$totalReceivables" /></strong>
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
                            <strong class="text-danger"><x-mobile.report-money :amount="$totalPayables" /></strong>
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
                            <span>{{ __('reports.low_stock_products') }} ({{ __('reports.ten_or_fewer_items') }}):</span>
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
                                    <td class="text-right"><x-mobile.report-money :amount="$sale->total" /></td>
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
@endmobile
@section('scripts')
    @include('components.mobile.report-card-script')
    @include('components.mobile.report-overview-script')
@endsection
@endsection
