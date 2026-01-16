@extends('include')

@section('backTitle')Create New Account @endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Create New Account</h4>
            <a href="{{ route('account.chart') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> Back
            </a>
        </div>

        <form action="{{ route('account.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="account_code">Account Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="account_code" name="account_code" 
                               placeholder="e.g., 1150" value="{{ old('account_code') }}" required>
                        <small class="form-text text-muted">Unique code for the account</small>
                        @error('account_code')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="account_name">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="account_name" name="account_name" 
                               placeholder="e.g., Petty Cash" value="{{ old('account_name') }}" required>
                        @error('account_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="account_type">Account Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="account_type" name="account_type" required>
                            <option value="">Select Account Type</option>
                            @foreach($accountTypes as $key => $type)
                                <option value="{{ $key }}" {{ old('account_type') == $key ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                        @error('account_type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="parent_account_id">Parent Account (Optional)</label>
                        <select class="form-control" id="parent_account_id" name="parent_account_id">
                            <option value="">None (Main Account)</option>
                            @foreach($parentAccounts as $account)
                                <option value="{{ $account->id }}" {{ old('parent_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Create sub-account under a parent account</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="opening_balance">Opening Balance</label>
                        <input type="number" step="0.01" class="form-control" id="opening_balance" 
                               name="opening_balance" placeholder="0.00" value="{{ old('opening_balance', 0) }}">
                        <small class="form-text text-muted">Initial balance for this account</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="business_location_id">Business Location (Optional)</label>
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
                                  placeholder="Account description...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="las la-save"></i> Create Account
                    </button>
                    <a href="{{ route('account.chart') }}" class="btn btn-secondary">
                        <i class="las la-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
