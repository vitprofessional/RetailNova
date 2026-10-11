@extends('include')
@section('backTitle'){{ __('common.heading_top_customers') }}@endsection
@section('container')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">{{ __('customers.top_customers_report') }}</h3>
                        <p class="mb-0">{{ __('customers.identify_your_best_customers_by_sales_volume_and_order_frequency') }}</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line"></i> {{ __('customers.back_to_reports') }}
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
                        <label class="form-label">{{ __('customers.start_date') }}</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('customers.end_date') }}</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('customers.number_of_customers') }}</label>
                        <select name="limit" class="form-control">
                            <option value="10" {{ $limit == 10 ? 'selected' : '' }}>{{ __('customers.top_10') }}</option>
                            <option value="20" {{ $limit == 20 ? 'selected' : '' }}>{{ __('customers.top_20') }}</option>
                            <option value="50" {{ $limit == 50 ? 'selected' : '' }}>{{ __('customers.top_50') }}</option>
                            <option value="100" {{ $limit == 100 ? 'selected' : '' }}>{{ __('customers.top_100') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-filter-3-line"></i> {{ __('customers.filter') }}
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
                <h5>{{ __('reports.top_customers_count', ['count'=>$limit]) }}</h5>
                <button onclick="window.print()" class="btn btn-sm btn-primary">
                    <i class="ri-printer-line"></i> {{ __('customers.print') }}
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover rn-table-pro">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('customers.customer_name') }}</th>
                                <th>{{ __('customers.email') }}</th>
                                <th>{{ __('customers.mobile') }}</th>
                                <th class="text-center">{{ __('customers.total_orders') }}</th>
                                <th class="text-right">{{ __('customers.total_spent') }}</th>
                                <th>{{ __('customers.last_purchase') }}</th>
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
                                    <td colspan="7" class="text-center">{{ __('customers.no_customer_data_found_for_the_selected_period') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($topCustomers->count() > 0)
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="4" class="text-right">{{ __('common.total') }}:</th>
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
