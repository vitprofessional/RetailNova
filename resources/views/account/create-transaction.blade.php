@extends('include')

@section('backTitle')Create Transaction @endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Create New Transaction</h4>
            <a href="{{ route('account.transactions') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> Back
            </a>
        </div>

        <form action="{{ route('account.transactions.store') }}" method="POST">
            @csrf
            <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="transaction_date">Transaction Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                                   value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                                            @error('transaction_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="transaction_type">Transaction Type <span class="text-danger">*</span></label>
                                            <select class="form-control" id="transaction_type" name="transaction_type" required>
                                                <option value="">Select Type</option>
                                                @foreach($transactionTypes as $key => $type)
                                                    <option value="{{ $key }}" {{ old('transaction_type') == $key ? 'selected' : '' }}>
                                                        {{ $type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('transaction_type')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reference_no">Reference Number <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="reference_no" name="reference_no" 
                                                   placeholder="e.g., TRX-20260115-001" value="{{ old('reference_no', 'TRX-' . date('Ymd') . '-' . rand(100, 999)) }}" required>
                                            @error('reference_no')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="amount">Amount <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                                   placeholder="0.00" value="{{ old('amount') }}" required>
                                            @error('amount')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="debit_account_id">Debit Account <span class="text-danger">*</span></label>
                                            <select class="form-control select2" id="debit_account_id" name="debit_account_id" required>
                                                <option value="">Select Debit Account</option>
                                                @foreach($accounts as $account)
                                                    <option value="{{ $account->id }}" {{ old('debit_account_id') == $account->id ? 'selected' : '' }}>
                                                        {{ $account->account_code }} - {{ $account->account_name }} ({{ ucfirst($account->account_type) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">Account to debit (increase assets/expenses or decrease liabilities/equity/revenue)</small>
                                            @error('debit_account_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="credit_account_id">Credit Account <span class="text-danger">*</span></label>
                                            <select class="form-control select2" id="credit_account_id" name="credit_account_id" required>
                                                <option value="">Select Credit Account</option>
                                                @foreach($accounts as $account)
                                                    <option value="{{ $account->id }}" {{ old('credit_account_id') == $account->id ? 'selected' : '' }}>
                                                        {{ $account->account_code }} - {{ $account->account_name }} ({{ ucfirst($account->account_type) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">Account to credit (increase liabilities/equity/revenue or decrease assets/expenses)</small>
                                            @error('credit_account_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="business_location_id">Business Location</label>
                                            <select class="form-control" id="business_location_id" name="business_location_id">
                                                <option value="">All Locations</option>
                                                @foreach($businessLocations as $location)
                                                    <option value="{{ $location->id }}" {{ old('business_location_id') == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name ?? $location->id }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3" 
                                                      placeholder="Transaction description...">{{ old('description') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <strong>Double-Entry Accounting:</strong> Every transaction must have equal debit and credit amounts. 
                                    The debit account increases and the credit account decreases (or vice versa depending on account type).
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="las la-save"></i> Record Transaction
                                    </button>
                                    <a href="{{ route('account.transactions') }}" class="btn btn-secondary">
                                        <i class="las la-times"></i> Cancel
                                    </a>
                                </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
