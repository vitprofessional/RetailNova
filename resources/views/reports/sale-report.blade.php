@extends('include')
@section('backTitle'){{ __('common.heading_sale_report') }}@endsection
@section('container')
<div class="row rn-report-page">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">{{ __('sales.sale_report') }}</h3>
                        <p class="mb-0">{{ __('sales.detailed_sales_transactions_and_analysis') }}</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line"></i> {{ __('sales.back_to_reports') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="col-lg-12 mb-3">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.sales') }}" class="row align-items-end" data-rn-report-date-form>
                    @include('components.mobile.report-period-tools')
                    <div class="col-md-3">
                        <label class="form-label">{{ __('sales.start_date') }}</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('sales.end_date') }}</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('sales.customer') }}</label>
                        <select name="customer_id" class="form-control">
                            <option value="">{{ __('sales.all_customers') }}</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-filter-3-line"></i> {{ __('sales.filter') }}
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
                        <h6 class="mb-2">{{ __('sales.total_sales') }}</h6>
                        <h3><x-mobile.report-money :amount="$totalSales" /></h3>
                        <p class="mb-0"><x-mobile.report-count :value="$sales->total()" /> {{ __('common.transactions') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card card-block card-stretch card-height bg-primary">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ __('sales.total_profit') }}</h6>
                        <h3><x-mobile.report-money :amount="$totalProfit" /></h3>
                        <p class="mb-0">{{ __('sales.from_sales') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>{{ __('sales.sales_transactions') }}</h5>
                <button onclick="window.print()" class="btn btn-sm btn-primary">
                    <i class="ri-printer-line"></i> {{ __('sales.print') }}
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table rn-report-table table-bordered table-hover rn-table-pro">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('sales.date') }}</th>
                                <th>{{ __('sales.invoice_no') }}</th>
                                <th>{{ __('sales.customer') }}</th>
                                <th class="text-right">{{ __('sales.total') }}</th>
                                <th class="text-right">{{ __('sales.discount') }}</th>
                                <th class="text-right">{{ __('sales.grand_total') }}</th>
                                <th class="text-right">{{ __('sales.profit') }}</th>
                                <th>{{ __('sales.payment_status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $sale)
                                <tr>
                                    <td>{{ app(\App\Support\MobileView::class)->isMobile() ? \App\Support\MobileDateTime::date($sale->date) : \Carbon\Carbon::parse($sale->date)->format('d M Y') }}</td>
                                    <td>{{ $sale->invoice ?: '—' }}</td>
                                    <td>{{ \App\Models\Customer::displayLabel($sale->customer, $sale->customerId, $sale->businessId) }}</td>
                                    <td class="text-right"><x-mobile.report-money :amount="$sale->totalSale ?? 0" /></td>
                                    <td class="text-right"><x-mobile.report-money :amount="$sale->discountAmount ?? 0" /></td>
                                    <td class="text-right"><strong><x-mobile.report-money :amount="$sale->grandTotal" /></strong></td>
                                    <td class="text-right text-success"><x-mobile.report-money :amount="$sale->grandTotal - ($sale->totalSale ?? 0)" /></td>
                                    <td>
                                        @php
                                            $curDue = floatval($sale->curDue ?? $sale->invoiceDue ?? 0);
                                        @endphp
                                        @if($curDue <= 0)
                                            <span class="badge bg-success">{{ __('sales.paid') }}</span>
                                        @elseif($sale->paidAmount > 0)
                                            <span class="badge bg-warning">{{ __('sales.partial') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ __('sales.unpaid') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">{{ __('sales.no_sales_found_for_the_selected_period') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($sales->count() > 0)
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="5" class="text-right">{{ __('common.total') }}:</th>
                                    <th class="text-right"><x-mobile.report-money :amount="$totalSales" /></th>
                                    <th class="text-right text-success"><x-mobile.report-money :amount="$totalProfit" /></th>
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
@section('scripts')
    @include('components.mobile.report-card-script')
@endsection
@endsection
