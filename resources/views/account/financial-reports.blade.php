@extends('include')

@section('backTitle'){{ __('common.heading_financial_reports') }}@endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card rn-report-page">
    <div class="card-body">
        <h4 class="mb-4">{{ __('reports.financial_reports') }}</h4>

        <form method="GET" action="{{ route('account.reports') }}">
            <div class="row mb-4">
                                    <div class="col-md-3">
                                        <label>{{ __('reports.report_type') }}</label>
                                        <select class="form-control" name="report_type">
                                            <option value="balance_sheet" {{ $reportType == 'balance_sheet' ? 'selected' : '' }}>{{ __('reports.balance_sheet') }}</option>
                                            <option value="income_statement" {{ $reportType == 'income_statement' ? 'selected' : '' }}>{{ __('reports.income_statement') }}</option>
                                            <option value="trial_balance" {{ $reportType == 'trial_balance' ? 'selected' : '' }}>{{ __('reports.trial_balance') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>{{ __('reports.start_date') }}</label>
                                        <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>{{ __('reports.end_date') }}</label>
                                        <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>&nbsp;</label><br>
                                        <button type="submit" class="btn btn-primary"><i class="las la-chart-bar"></i> {{ __('reports.generate') }}</button>
                                        <button type="button" class="btn btn-print" onclick="window.print()"><i class="las la-print"></i> {{ __('reports.print') }}</button>
                                    </div>
                                </div>
                            </form>

                            <hr>

                            @if($reportType == 'balance_sheet')
                                {{-- Balance Sheet --}}
                                <h5 class="text-center mb-3">{{ __('reports.balance_sheet') }}</h5>
                                <p class="text-center">{{ __('common.as_of') }} {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}</p>

                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>{{ __('reports.assets') }}</h6>
<table class="table rn-report-table table-sm">
                                            @foreach($data['assets'] as $asset)
                                                <tr>
                                                <td>{{ $asset->account_name }}</td>
                                                <td class="text-right">{{ number_format($asset->current_balance, 2) }}</td>
                                            </tr>
                                            @endforeach
                                            <tr class="font-weight-bold">
                                                <td>{{ __('reports.total_assets') }}</td>
                                                <td class="text-right">{{ number_format($data['total_assets'], 2) }}</td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="col-md-6">
                                        <h6>{{ __('reports.liabilities') }}</h6>
                                        <table class="table rn-report-table table-sm">
                                            @foreach($data['liabilities'] as $liability)
                                                <tr>
                                                <td>{{ $liability->account_name }}</td>
                                                <td class="text-right">{{ number_format($liability->current_balance, 2) }}</td>
                                            </tr>
                                            @endforeach
                                            <tr class="font-weight-bold">
                                                <td>{{ __('reports.total_liabilities') }}</td>
                                                <td class="text-right">{{ number_format($data['total_liabilities'], 2) }}</td>
                                            </tr>
                                        </table>

                                        <h6 class="mt-3">{{ __('reports.equity') }}</h6>
                                        <table class="table rn-report-table table-sm">
                                            @foreach($data['equity'] as $eq)
                                                <tr>
                                                <td>{{ $eq->account_name }}</td>
                                                <td class="text-right">{{ number_format($eq->current_balance, 2) }}</td>
                                            </tr>
                                            @endforeach
                                            <tr class="font-weight-bold">
                                                <td>{{ __('reports.total_equity') }}</td>
                                                <td class="text-right">{{ number_format($data['total_equity'], 2) }}</td>
                                            </tr>
                                        </table>

                                        <table class="table rn-report-table table-sm">
                                            <tr class="font-weight-bold bg-light">
                                                <td>{{ __('reports.total_liabilities_equity') }}</td>
                                                <td class="text-right">{{ number_format($data['total_liabilities'] + $data['total_equity'], 2) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                            @elseif($reportType == 'income_statement')
                                {{-- Income Statement --}}
                                <h5 class="text-center mb-3">{{ __('reports.income_statement') }}</h5>
                                <p class="text-center">{{ __('common.for_the_period') }} {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} {{ __('common.to') }} {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>

                                <h6>{{ __('reports.revenue') }}</h6>
                                <table class="table rn-report-table table-sm">
                                    @foreach($data['revenue'] as $rev)
                                        <tr>
                                        <td>{{ $rev->account_name }}</td>
                                        <td class="text-right">{{ number_format($rev->current_balance, 2) }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="font-weight-bold">
                                        <td>{{ __('reports.total_revenue') }}</td>
                                        <td class="text-right">{{ number_format($data['total_revenue'], 2) }}</td>
                                    </tr>
                                </table>

                                <h6>{{ __('reports.expenses') }}</h6>
                                <table class="table rn-report-table table-sm">
                                    @foreach($data['expenses'] as $expense)
                                        <tr>
                                        <td>{{ $expense->account_name }}</td>
                                        <td class="text-right">{{ number_format($expense->current_balance, 2) }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="font-weight-bold">
                                        <td>{{ __('reports.total_expenses') }}</td>
                                        <td class="text-right">{{ number_format($data['total_expenses'], 2) }}</td>
                                    </tr>
                                </table>

                                <table class="table rn-report-table table-sm">
                                    <tr class="font-weight-bold bg-{{ $data['net_income'] >= 0 ? 'success' : 'danger' }} text-white">
                                        <td>{{ __('reports.net_income_loss') }}</td>
                                        <td class="text-right">{{ number_format($data['net_income'], 2) }}</td>
                                    </tr>
                                </table>

                            @elseif($reportType == 'trial_balance')
                                {{-- Trial Balance --}}
                                <h5 class="text-center mb-3">{{ __('reports.trial_balance') }}</h5>
                                <p class="text-center">{{ __('common.as_of') }} {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}</p>

<table class="table rn-report-table table-bordered rn-table-pro">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>{{ __('reports.account_code') }}</th>
                                            <th>{{ __('reports.account_name') }}</th>
                                            <th class="text-right">{{ __('reports.debit') }}</th>
                                            <th class="text-right">{{ __('reports.credit') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['accounts'] as $account)
                                        <tr>
                                            <td>{{ $account->account_code }}</td>
                                            <td>{{ $account->account_name }}</td>
                                            <td class="text-right">
                                                @if(in_array($account->account_type, ['asset', 'expense']))
                                                    {{ number_format($account->current_balance, 2) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                @if(in_array($account->account_type, ['liability', 'equity', 'revenue']))
                                                    {{ number_format($account->current_balance, 2) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="font-weight-bold">
                                        <tr>
                                            <td colspan="2" class="text-right">{{ __('common.total') }}:</td>
                                            <td class="text-right">{{ number_format($data['debit_total'], 2) }}</td>
                                            <td class="text-right">{{ number_format($data['credit_total'], 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @endif
                </div>
            </div>
        </div>
    </div>
</div>
@section('scripts')
    @include('components.mobile.report-card-script')
@endsection
@endsection
