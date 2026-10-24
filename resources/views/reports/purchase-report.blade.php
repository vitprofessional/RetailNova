@extends('include')
@section('backTitle'){{ __('common.heading_purchase_report') }}@endsection
@section('container')
<div class="row rn-report-page">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">{{ __('purchases.purchase_report') }}</h3>
                        <p class="mb-0">{{ __('purchases.detailed_purchase_transactions_and_supplier_analysis') }}</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line"></i> {{ __('purchases.back_to_reports') }}
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
                        <label class="form-label">{{ __('purchases.start_date') }}</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('purchases.end_date') }}</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('purchases.supplier') }}</label>
                        <select name="supplier_id" class="form-control">
                            <option value="">{{ __('purchases.all_suppliers') }}</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-filter-3-line"></i> {{ __('purchases.filter') }}
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
                <h6 class="mb-2">{{ __('purchases.total_purchases') }}</h6>
                <h3>@money($totalPurchases)</h3>
                <p class="mb-0">{{ $totals['transactions'] ?? $purchases->total() }} {{ __('common.transactions') }}</p>
            </div>
        </div>
    </div>

    <!-- Purchases Table -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>{{ __('purchases.purchase_transactions') }}</h5>
                <button onclick="window.print()" class="btn btn-sm btn-primary">
                    <i class="ri-printer-line"></i> {{ __('purchases.print') }}
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table rn-report-table table-bordered table-hover rn-table-pro">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('purchases.date') }}</th>
                                <th>{{ __('purchases.invoice_no') }}</th>
                                <th>{{ __('purchases.supplier') }}</th>
                                <th class="text-center">{{ __('purchases.items') }}</th>
                                <th class="text-right">{{ __('purchases.sub_total') }}</th>
                                <th class="text-right">{{ __('purchases.discount') }}</th>
                                <th class="text-right">{{ __('purchases.grand_total') }}</th>
                                <th class="text-right">{{ __('purchases.paid') }}</th>
                                <th class="text-right">{{ __('purchases.due') }}</th>
                                <th>{{ __('purchases.payment_status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                                @php
                                    $reportDate = $purchase->purchase_date ?: $purchase->sort_date;
                                    $due = floatval($purchase->due_total ?? 0);
                                    $paid = floatval($purchase->paid_total ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $reportDate ? \Carbon\Carbon::parse($reportDate)->format('d M Y') : '-' }}</td>
                                    <td>{{ $purchase->invoice_no ?? 'N/A' }}</td>
                                    <td>{{ $purchase->supplier_name ?? 'Unknown Supplier' }}</td>
                                    <td class="text-center">{{ intval($purchase->line_items ?? 0) }}</td>
                                    <td class="text-right">@money($purchase->sub_total ?? 0)</td>
                                    <td class="text-right">@money($purchase->discount_total ?? 0)</td>
                                    <td class="text-right"><strong>@money($purchase->grand_total ?? 0)</strong></td>
                                    <td class="text-right">@money($purchase->paid_total ?? 0)</td>
                                    <td class="text-right">@money($purchase->due_total ?? 0)</td>
                                    <td>
                                        @if($due <= 0)
                                            <span class="badge bg-success">{{ __('purchases.paid') }}</span>
                                        @elseif($paid > 0)
                                            <span class="badge bg-warning">{{ __('purchases.partial') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ __('purchases.unpaid') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">{{ __('purchases.no_purchases_found_for_the_selected_period') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($purchases->count() > 0)
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="4" class="text-right">{{ __('common.total') }}:</th>
                                    <th class="text-right">@money($totals['sub_total'] ?? 0)</th>
                                    <th class="text-right">@money($totals['discount'] ?? 0)</th>
                                    <th class="text-right">@money($totals['grand_total'] ?? 0)</th>
                                    <th class="text-right">@money($totals['paid_total'] ?? 0)</th>
                                    <th class="text-right">@money($totals['due_total'] ?? 0)</th>
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
@section('scripts')
    @include('components.mobile.report-card-script')
@endsection
@endsection
