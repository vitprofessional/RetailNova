@extends('include')
@section('backTitle'){{ __('common.heading_reports_dashboard') }}@endsection
@section('container')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <h3 class="mb-3">{{ __('reports.reports_dashboard') }}</h3>
                <p class="mb-0">{{ __('reports.access_comprehensive_reports_for_your_business_analytics') }}</p>
            </div>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="row">
            <!-- Business Report -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">{{ __('reports.business_report') }}</h5>
                                <p class="mb-0">{{ __('reports.view_overall_business_performance_sales_purchases_and_profit_analysis') }}</p>
                            </div>
                            <div class="rounded bg-primary p-3">
                                <i class="ri-bar-chart-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.business') }}" class="btn btn-primary mt-3 w-100">{{ __('reports.view_report') }}</a>
                    </div>
                </div>
            </div>

            <!-- Sale Report -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">{{ __('reports.sale_report') }}</h5>
                                <p class="mb-0">{{ __('reports.detailed_sales_transactions_with_date_filtering_and_customer_analysis') }}</p>
                            </div>
                            <div class="rounded bg-success p-3">
                                <i class="ri-shopping-cart-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.sales') }}" class="btn btn-success mt-3 w-100">{{ __('reports.view_report') }}</a>
                    </div>
                </div>
            </div>

            <!-- Purchase Report -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">{{ __('reports.purchase_report') }}</h5>
                                <p class="mb-0">{{ __('reports.track_all_purchase_transactions_and_supplier_wise_analysis') }}</p>
                            </div>
                            <div class="rounded bg-info p-3">
                                <i class="ri-file-list-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.purchases') }}" class="btn btn-info mt-3 w-100">{{ __('reports.view_report') }}</a>
                    </div>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">{{ __('reports.top_customers') }}</h5>
                                <p class="mb-0">{{ __('reports.identify_your_best_customers_by_sales_volume_and_order_frequency') }}</p>
                            </div>
                            <div class="rounded bg-warning p-3">
                                <i class="ri-user-star-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.topCustomers') }}" class="btn btn-warning mt-3 w-100">{{ __('reports.view_report') }}</a>
                    </div>
                </div>
            </div>

            <!-- Payable/Receivable -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">{{ __('reports.payable_receivable') }}</h5>
                                <p class="mb-0">{{ __('reports.monitor_outstanding_payments_from_customers_and_to_suppliers') }}</p>
                            </div>
                            <div class="rounded bg-danger p-3">
                                <i class="ri-money-dollar-circle-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.payableReceivable') }}" class="btn btn-danger mt-3 w-100">{{ __('reports.view_report') }}</a>
                    </div>
                </div>
            </div>

            <!-- Stock Report -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">{{ __('reports.stock_low_stock') }}</h5>
                                <p class="mb-0">{{ __('reports.view_current_inventory_levels_and_identify_low_stock_products') }}</p>
                            </div>
                            <div class="rounded bg-secondary p-3">
                                <i class="ri-stack-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.stock') }}" class="btn btn-secondary mt-3 w-100">{{ __('reports.view_report') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
