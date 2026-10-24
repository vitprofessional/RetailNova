@extends('include')
@section('backTitle'){{ app(\App\Support\MobileView::class)->isMobile() ? __('reports.stock_low_stock_report') : __('common.heading_stock_report') }}@endsection
@section('container')
<div class="row rn-report-page">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">{{ __('reports.stock_low_stock_report') }}</h3>
                        <p class="mb-0">{{ __('reports.view_current_inventory_levels_and_identify_low_stock_products') }}</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line"></i> {{ __('reports.back_to_reports') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-4">
                <div class="card card-block card-stretch card-height bg-primary">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ __('reports.total_products') }}</h6>
                        <h3><x-mobile.report-count :value="$totalProducts" /></h3>
                        <p class="mb-0">{{ __('reports.in_inventory') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-block card-stretch card-height bg-warning">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ __('reports.low_stock_products') }}</h6>
                        <h3><x-mobile.report-count :value="$lowStockCount" /></h3>
                        <p class="mb-0">{{ __('reports.ten_or_fewer_items') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-block card-stretch card-height bg-danger">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ __('reports.out_of_stock') }}</h6>
                        <h3><x-mobile.report-count :value="$outOfStockCount" /></h3>
                        <p class="mb-0">{{ __('reports.zero_quantity') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.stock') }}" class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('reports.filter_by_status') }}</label>
                        <select name="filter" class="form-control">
                            <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>{{ __('reports.all_products') }}</option>
                            <option value="low" {{ $filter == 'low' ? 'selected' : '' }}>{{ __('reports.low_stock_only') }}</option>
                            <option value="out" {{ $filter == 'out' ? 'selected' : '' }}>{{ __('reports.out_of_stock_only') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('reports.search_product') }}</label>
                        <input type="text" name="search" class="form-control" placeholder="{{ __('common.product_name_or_barcode') }}" value="{{ $search ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-filter-3-line"></i> {{ __('reports.filter') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stock Table -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>
                    @if($filter == 'low')
                        {{ __('reports.low_stock_products') }}
                    @elseif($filter == 'out')
                        {{ __('reports.out_of_stock') }}
                    @else
                        {{ __('reports.all_products') }}
                    @endif
                </h5>
                <button onclick="window.print()" class="btn btn-sm btn-primary rn-report-print">
                    <i class="ri-printer-line"></i> {{ __('reports.print') }}
                </button>
                @mobile<button type="button" class="rn-report-download" data-rn-report-download aria-label="{{ __('reports.download') }}"><i class="ri-download-line" aria-hidden="true"></i></button>@endmobile
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table rn-report-table table-bordered table-hover rn-table-pro">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('reports.product_name') }}</th>
                                <th>{{ __('reports.barcode') }}</th>
                                <th>{{ __('reports.brand') }}</th>
                                <th>{{ __('reports.category') }}</th>
                                <th>{{ __('reports.unit') }}</th>
                                <th class="text-center">{{ __('reports.current_stock') }}</th>
                                <th class="text-center">{{ __('reports.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                @php
                                    $quantity = intval($product->quantity ?? 0);
                                    $status = __('inventory.in_stock');
                                    $statusClass = 'success';

                                    if($quantity <= 0) {
                                        $status = __('reports.out_of_stock');
                                        $statusClass = 'danger';
                                    } elseif($quantity <= 10) {
                                        $status = __('reports.low_stock_products');
                                        $statusClass = 'warning';
                                    } else {
                                        $status = __('inventory.in_stock');
                                        $statusClass = 'success';
                                    }
                                @endphp
                                <tr class="{{ $quantity <= 0 ? 'table-danger' : ($quantity <= 10 ? 'table-warning' : '') }}">
                                    <td><strong>{{ $product->name }}</strong></td>
                                    <td>{{ $product->barCode ?: '—' }}</td>
                                    <td>{{ $product->brandModel->name ?? '—' }}</td>
                                    <td>{{ $product->categoryModel->name ?? '—' }}</td>
                                    <td>{{ $product->unitModel->name ?? '—' }}</td>
                                    <td class="text-center">
                                        <strong class="text-{{ $statusClass }}"><x-mobile.report-count :value="$quantity" /></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $statusClass }}">{{ $status }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">{{ __('reports.no_products_found_matching_the_selected_criteria') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $products->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    @if($lowStockCount > 0 || $outOfStockCount > 0)
        <div class="col-lg-12 mt-3">
            <div class="alert alert-warning">
                <h6><i class="ri-alert-line"></i> {{ __('reports.stock_alert') }}</h6>
                <p class="mb-0">
                    {{ __('reports.you_have') }} <strong>{{ \App\Support\LocalizedNumber::count($lowStockCount) }}</strong>{{ app()->getLocale() === 'bn' ? '' : ' ' }}{{ __('reports.low_stock_products_and') }}
                    <strong>{{ \App\Support\LocalizedNumber::count($outOfStockCount) }}</strong>{{ app()->getLocale() === 'bn' ? '' : ' ' }}{{ __('reports.out_of_stock_products') }}
                    {{ __('reports.restock_advice') }}
                </p>
            </div>
        </div>
    @endif
</div>
@section('scripts')
    @include('components.mobile.report-card-script')
@endsection
@endsection
