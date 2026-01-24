@extends('include')

@section('backTitle')Financial Reports @endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4">Financial Reports</h4>

        <form method="GET" action="{{ route('account.reports') }}">
            <div class="row mb-4">
                                    <div class="col-md-3">
                                        <label>Report Type</label>
                                        <select class="form-control" name="report_type">
                                            <option value="balance_sheet" {{ $reportType == 'balance_sheet' ? 'selected' : '' }}>Balance Sheet</option>
                                            <option value="income_statement" {{ $reportType == 'income_statement' ? 'selected' : '' }}>Income Statement</option>
                                            <option value="trial_balance" {{ $reportType == 'trial_balance' ? 'selected' : '' }}>Trial Balance</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>&nbsp;</label><br>
                                        <button type="submit" class="btn btn-primary"><i class="las la-chart-bar"></i> Generate</button>
                                        <button type="button" class="btn btn-print" onclick="window.print()"><i class="las la-print"></i> Print</button>
                                    </div>
                                </div>
                            </form>

                            <hr>

                            @if($reportType == 'balance_sheet')
                                {{-- Balance Sheet --}}
                                <h5 class="text-center mb-3">Balance Sheet</h5>
                                <p class="text-center">As of {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}</p>

                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Assets</h6>
                                        <table class="table table-sm">
                                            @foreach($data['assets'] as $asset)
                                                <tr>
                                                <td>{{ $asset->account_name }}</td>
                                                <td class="text-right">{{ number_format($asset->current_balance, 2) }}</td>
                                            </tr>
                                            @endforeach
                                            <tr class="font-weight-bold">
                                                <td>Total Assets</td>
                                                <td class="text-right">{{ number_format($data['total_assets'], 2) }}</td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="col-md-6">
                                        <h6>Liabilities</h6>
                                        <table class="table table-sm">
                                            @foreach($data['liabilities'] as $liability)
                                                <tr>
                                                <td>{{ $liability->account_name }}</td>
                                                <td class="text-right">{{ number_format($liability->current_balance, 2) }}</td>
                                            </tr>
                                            @endforeach
                                            <tr class="font-weight-bold">
                                                <td>Total Liabilities</td>
                                                <td class="text-right">{{ number_format($data['total_liabilities'], 2) }}</td>
                                            </tr>
                                        </table>

                                        <h6 class="mt-3">Equity</h6>
                                        <table class="table table-sm">
                                            @foreach($data['equity'] as $eq)
                                                <tr>
                                                <td>{{ $eq->account_name }}</td>
                                                <td class="text-right">{{ number_format($eq->current_balance, 2) }}</td>
                                            </tr>
                                            @endforeach
                                            <tr class="font-weight-bold">
                                                <td>Total Equity</td>
                                                <td class="text-right">{{ number_format($data['total_equity'], 2) }}</td>
                                            </tr>
                                        </table>

                                        <table class="table table-sm">
                                            <tr class="font-weight-bold bg-light">
                                                <td>Total Liabilities & Equity</td>
                                                <td class="text-right">{{ number_format($data['total_liabilities'] + $data['total_equity'], 2) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                            @elseif($reportType == 'income_statement')
                                {{-- Income Statement --}}
                                <h5 class="text-center mb-3">Income Statement</h5>
                                <p class="text-center">For the period {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>

                                <h6>Revenue</h6>
                                <table class="table table-sm">
                                    @foreach($data['revenue'] as $rev)
                                        <tr>
                                        <td>{{ $rev->account_name }}</td>
                                        <td class="text-right">{{ number_format($rev->current_balance, 2) }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="font-weight-bold">
                                        <td>Total Revenue</td>
                                        <td class="text-right">{{ number_format($data['total_revenue'], 2) }}</td>
                                    </tr>
                                </table>

                                <h6>Expenses</h6>
                                <table class="table table-sm">
                                    @foreach($data['expenses'] as $expense)
                                        <tr>
                                        <td>{{ $expense->account_name }}</td>
                                        <td class="text-right">{{ number_format($expense->current_balance, 2) }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="font-weight-bold">
                                        <td>Total Expenses</td>
                                        <td class="text-right">{{ number_format($data['total_expenses'], 2) }}</td>
                                    </tr>
                                </table>

                                <table class="table table-sm">
                                    <tr class="font-weight-bold bg-{{ $data['net_income'] >= 0 ? 'success' : 'danger' }} text-white">
                                        <td>Net Income (Loss)</td>
                                        <td class="text-right">{{ number_format($data['net_income'], 2) }}</td>
                                    </tr>
                                </table>

                            @elseif($reportType == 'trial_balance')
                                {{-- Trial Balance --}}
                                <h5 class="text-center mb-3">Trial Balance</h5>
                                <p class="text-center">As of {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}</p>

                                    <table class="table table-bordered rn-table-pro">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Account Code</th>
                                            <th>Account Name</th>
                                            <th class="text-right">Debit</th>
                                            <th class="text-right">Credit</th>
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
                                            <td colspan="2" class="text-right">Total:</td>
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
@endsection
