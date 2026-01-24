@extends('include')

@section('backTitle')Chart of Accounts @endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Chart of Accounts</h4>
            <a href="{{ route('account.create') }}" class="btn btn-primary btn-sm">
                <i class="las la-plus"></i> Add New Account
            </a>
        </div>

        {{-- Filters --}}
        <div class="row mb-4">
            <div class="col-md-4">
                <label>Account Type</label>
                <select class="form-control" id="filter-type">
                    <option value="">All Types</option>
                    <option value="asset">Asset</option>
                    <option value="liability">Liability</option>
                    <option value="equity">Equity</option>
                    <option value="revenue">Revenue</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div class="col-md-4">
                <label>Status</label>
                <select class="form-control" id="filter-status">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>

        {{-- Accounts Table --}}
        <div class="table-responsive">
                        <table class="data-tables table mb-0 table-bordered rn-table-pro" id="accounts-table">
                            <thead class="bg-white text-uppercase">
                                <tr>
                                    <th>Code</th>
                                    <th>Account Name</th>
                                    <th>Type</th>
                                    <th>Parent Account</th>
                                    <th>Opening Balance</th>
                                    <th>Current Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accounts as $account)
                                <tr>
                                    <td>{{ $account->account_code }}</td>
                                    <td>
                                        @if($account->parent_account_id)
                                            <span class="ml-3">└─</span>
                                        @endif
                                        {{ $account->account_name }}
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $account->account_type == 'asset' ? 'success' : ($account->account_type == 'liability' ? 'warning' : ($account->account_type == 'equity' ? 'info' : ($account->account_type == 'revenue' ? 'primary' : 'danger'))) }}">
                                            {{ ucfirst($account->account_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $account->parentAccount->account_name ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($account->opening_balance, 2) }}</td>
                                    <td class="text-right">{{ number_format($account->current_balance, 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $account->is_active ? 'success' : 'secondary' }}">
                                            {{ $account->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('account.ledger', $account->id) }}" 
                                               class="btn btn-sm btn-info" 
                                               title="View Ledger">
                                                <i class="las la-book"></i>
                                            </a>
                                            <a href="{{ route('account.edit', $account->id) }}" 
                                               class="btn btn-sm btn-primary" 
                                               title="Edit">
                                                <i class="las la-edit"></i>
                                            </a>
                                            <a href="{{ route('account.delete', $account->id) }}" 
                                               class="btn btn-sm btn-danger" 
                                               title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this account?')">
                                                <i class="las la-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No accounts found. <a href="{{ route('account.create') }}">Create your first account</a></td>
                                </tr>
                                @endforelse
                            </tbody>
                </table>
            </div>

            {{-- Summary Statistics --}}
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5>Account Summary by Type</h5>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6>Total Assets</h6>
                            <h3>{{ number_format($accounts->where('account_type', 'asset')->sum('current_balance'), 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6>Total Liabilities</h6>
                            <h3>{{ number_format($accounts->where('account_type', 'liability')->sum('current_balance'), 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6>Total Equity</h6>
                            <h3>{{ number_format($accounts->where('account_type', 'equity')->sum('current_balance'), 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
