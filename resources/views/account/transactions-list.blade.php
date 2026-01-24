@extends('include')

@section('backTitle')Account Transactions @endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Account Transactions</h4>
            <a href="{{ route('account.transactions.create') }}" class="btn btn-primary btn-sm">
                <i class="las la-plus"></i> New Transaction
            </a>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('account.transactions') }}">
            <div class="row mb-4">
                                    <div class="col-md-3">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Transaction Type</label>
                                        <select class="form-control" name="transaction_type">
                                            <option value="">All Types</option>
                                            @foreach($transactionTypes as $key => $type)
                                            <option value="{{ $key }}" {{ request('transaction_type') == $key ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Account</label>
                                        <select class="form-control" name="account_id">
                                            <option value="">All Accounts</option>
                                            @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="las la-filter"></i> Filter</button>
                                <a href="{{ route('account.transactions') }}" class="btn btn-secondary"><i class="las la-redo"></i> Reset</a>
                            </form>

                            <hr>

                            {{-- Transactions Table --}}
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-hover rn-table-pro">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference</th>
                                            <th>Type</th>
                                            <th>Debit Account</th>
                                            <th>Credit Account</th>
                                            <th class="text-right">Amount</th>
                                            <th>Description</th>
                                            <th>Created By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($transactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                                            <td>{{ $transaction->reference_no }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ ucfirst($transaction->transaction_type) }}</span>
                                            </td>
                                            <td>{{ $transaction->debitAccount->account_code }} - {{ $transaction->debitAccount->account_name }}</td>
                                            <td>{{ $transaction->creditAccount->account_code }} - {{ $transaction->creditAccount->account_name }}</td>
                                            <td class="text-right font-weight-bold">{{ number_format($transaction->amount, 2) }}</td>
                                            <td>{{ \Str::limit($transaction->description, 50) }}</td>
                                            <td>{{ $transaction->creator->name ?? 'System' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No transactions found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination --}}
                            <div class="mt-3">
                                {{ $transactions->appends(request()->query())->links() }}
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
