@extends('include')

@section('backTitle'){{ __('common.heading_expense_reports') }}@endsection

@section('container')
<div class="rn-report-page">
    @include('sweetalert::alert')
    <div class="row align-items-center mb-3">
        <div class="col rn-report-inner-heading"><h3 class="mb-1">{{ __('reports.expense_reports') }}</h3><p class="text-muted mb-0">{{ $startDate }} — {{ $endDate }}</p></div>
        <div class="col-auto"><button type="button" class="btn btn-outline-primary rn-report-print" onclick="window.print()"><i class="las la-print"></i> {{ __('reports.print') }}</button></div>
    </div>

    <div class="row"><div class="col-12 col-md-6 col-lg-4"><div class="card bg-primary text-white"><div class="card-body">
        <h6 class="mb-2">{{ __('reports.total_expenses') }}</h6><strong class="h3"><x-mobile.report-money :amount="$totalExpense" /></strong>
    </div></div></div></div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('expense.reports') }}" class="row align-items-end" data-rn-report-date-form>
            @include('components.mobile.report-period-tools')
            <div class="col-12 col-md-4 mb-2"><label>{{ __('reports.start_date') }}</label><input type="date" class="form-control" name="start_date" value="{{ $startDate }}"></div>
            <div class="col-12 col-md-4 mb-2"><label>{{ __('reports.end_date') }}</label><input type="date" class="form-control" name="end_date" value="{{ $endDate }}"></div>
            <div class="col-12 col-md-4 mb-2"><label>{{ __('reports.group_by') }}</label><select class="form-control" name="group_by"><option value="category" {{ $groupBy == 'category' ? 'selected' : '' }}>{{ __('reports.category') }}</option><option value="payment_method" {{ $groupBy == 'payment_method' ? 'selected' : '' }}>{{ __('reports.payment_method') }}</option><option value="date" {{ $groupBy == 'date' ? 'selected' : '' }}>{{ __('reports.date') }}</option></select></div>
            <div class="col-12"><button type="submit" class="btn btn-primary"><i class="las la-chart-bar"></i> {{ __('reports.generate') }}</button></div>
        </form>
    </div></div>

    <div class="card"><div class="card-header"><h5 class="mb-0">{{ __('reports.expense_reports') }}</h5></div><div class="card-body">
        <div class="table-responsive"><table class="table table-bordered rn-report-table">
            <thead><tr><th>{{ __('reports.' . ($groupBy === 'payment_method' ? 'payment_method' : ($groupBy === 'date' ? 'date' : 'category'))) }}</th><th>{{ __('reports.total_amount') }}</th><th>{{ __('reports.percentage') }}</th></tr></thead>
            <tbody>
                @forelse($data as $item)
                    <tr><td>@if($groupBy == 'category'){{ $item->category->name ?? '' }}@elseif($groupBy == 'payment_method'){{ __('reports.payment_'.$item->payment_method, [], app()->getLocale()) }}@else{{ app(\App\Support\MobileView::class)->isMobile() ? \App\Support\MobileDateTime::date($item->expense_date) : \Carbon\Carbon::parse($item->expense_date)->format('d/m/Y') }}@endif</td><td class="font-weight-bold"><x-mobile.report-money :amount="$item->total" /></td><td>{{ \App\Support\LocalizedNumber::digits($totalExpense > 0 ? number_format(($item->total / $totalExpense) * 100, 2) : 0) }}%</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted">{{ __('common.no_data_found') }}</td></tr>
                @endforelse
            </tbody><tfoot><tr><th>{{ __('common.total') }}</th><th><x-mobile.report-money :amount="$totalExpense" /></th><th>{{ app(\App\Support\MobileView::class)->isMobile() ? \App\Support\LocalizedNumber::digits('100%') : '100%' }}</th></tr></tfoot>
        </table></div>
        <div class="rn-report-chart"><canvas id="expenseChart" aria-label="{{ __('reports.expense_reports') }}"></canvas></div>
    </div></div>
</div>
@endsection

@section('scripts')
    <script src="{{ \App\Support\VendorAsset::url('chartjs/chart.umd.min.js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js') }}"></script>
    @include('components.mobile.report-card-script')
    <script>
        const chartCanvas = document.getElementById('expenseChart');
        if (chartCanvas && window.Chart) {
            new Chart(chartCanvas, {
                type: 'bar',
                data: { labels: @json($data->map(fn ($item) => $groupBy === 'category' ? ($item->category->name ?? '') : ($groupBy === 'payment_method' ? __('reports.payment_'.$item->payment_method, [], app()->getLocale()) : \Carbon\Carbon::parse($item->expense_date)->format('d/m')))->values()), datasets: [{ label: @json(__('reports.total_amount')), data: @json($data->pluck('total')->values()), backgroundColor: 'rgba(25, 145, 142, 0.35)', borderColor: '#19918e', borderWidth: 1 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        }
    </script>
@endsection
