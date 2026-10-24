@extends('include')
@section('backTitle')
{{ __('navigation.payable_receivable') }}
@endsection
@section('container')
<div class="row rn-report-page">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">{{ __('reports.payable_receivable_report') }}</h3>
                        <p class="mb-0">{{ __('reports.monitor_outstanding_payments_from_customers_and_to_suppliers') }}</p>
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
            <div class="col-lg-6">
                <div class="card card-block card-stretch card-height bg-success">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ __('reports.total_receivables') }}</h6>
                        <h3>@money($totalReceivable)</h3>
                        <p class="mb-0">{{ __('common.from') }} {{ $receivables->count() }} {{ __('common.customers') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card card-block card-stretch card-height bg-danger">
                    <div class="card-body text-white">
                        <h6 class="mb-2">{{ __('reports.total_payables') }}</h6>
                        <h3>@money($totalPayable)</h3>
                        <p class="mb-0">{{ __('common.to') }} {{ $payables->count() }} {{ __('common.suppliers') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receivables Section -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between">
                <h5 class="mb-0">{{ __('reports.accounts_receivable_from_customers') }}</h5>
                <button onclick="window.print()" class="btn btn-sm btn-light">
                    <i class="ri-printer-line"></i> {{ __('reports.print') }}
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table rn-report-table table-bordered table-hover rn-table-pro">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('reports.customer_name') }}</th>
                                <th>{{ __('reports.email') }}</th>
                                <th>{{ __('reports.mobile') }}</th>
                                <th class="text-right">{{ __('reports.outstanding_balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receivables as $index => $customer)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $customer->name }}</strong></td>
                                    <td>{{ $customer->mail ?? 'N/A' }}</td>
                                    <td>{{ $customer->mobile ?? 'N/A' }}</td>
                                    <td class="text-right">
                                        <strong class="text-success">@money($customer->openingBalance)</strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">{{ __('reports.no_outstanding_receivables_from_customers') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($receivables->count() > 0)
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="4" class="text-right">{{ __('reports.total_receivable') }}:</th>
                                    <th class="text-right text-success">@money($totalReceivable)</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payables Section -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header bg-danger text-white d-flex justify-content-between">
                <h5 class="mb-0">{{ __('reports.accounts_payable_to_suppliers') }}</h5>
                <button onclick="window.print()" class="btn btn-sm btn-light">
                    <i class="ri-printer-line"></i> {{ __('reports.print') }}
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table rn-report-table table-bordered table-hover rn-table-pro">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('reports.supplier_name') }}</th>
                                <th>{{ __('reports.email') }}</th>
                                <th>{{ __('reports.mobile') }}</th>
                                <th class="text-right">{{ __('reports.outstanding_balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payables as $index => $supplier)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $supplier->name }}</strong></td>
                                    <td>{{ $supplier->mail ?? 'N/A' }}</td>
                                    <td>{{ $supplier->mobile ?? 'N/A' }}</td>
                                    <td class="text-right">
                                        <strong class="text-danger">@money($supplier->openingBalance)</strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">{{ __('reports.no_outstanding_payables_to_suppliers') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($payables->count() > 0)
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="4" class="text-right">{{ __('reports.total_payable') }}:</th>
                                    <th class="text-right text-danger">@money($totalPayable)</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Position -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6>{{ __('reports.total_receivable') }}:</h6>
                        <h4 class="text-success">@money($totalReceivable)</h4>
                    </div>
                    <div class="col-md-4">
                        <h6>{{ __('reports.total_payable') }}:</h6>
                        <h4 class="text-danger">@money($totalPayable)</h4>
                    </div>
                    <div class="col-md-4">
                        <h6>{{ __('reports.net_position') }}:</h6>
                        @php
                            $netPosition = $totalReceivable - $totalPayable;
                        @endphp
                        <h4 class="{{ $netPosition >= 0 ? 'text-success' : 'text-danger' }}">
                            @money(abs($netPosition))
                            @if($netPosition >= 0)
                                (In Favor)
                            @else
                                (Against)
                            @endif
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@section('scripts')
    @include('components.mobile.report-card-script')
@endsection
@endsection
