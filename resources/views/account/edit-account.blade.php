@extends('include')

@section('backTitle')Edit Account @endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Edit Account: {{ $account->account_name }}</h4>
            <a href="{{ route('account.chart') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> Back
            </a>
        </div>

        <form action="{{ route('account.update', $account->id) }}" method="POST">
            @csrf
            <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="account_code">Account Code <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="account_code" name="account_code" 
                                                   value="{{ old('account_code', $account->account_code) }}" required>
                                            @error('account_code')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="account_name">Account Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="account_name" name="account_name" 
                                                   value="{{ old('account_name', $account->account_name) }}" required>
                                            @error('account_name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="account_type">Account Type <span class="text-danger">*</span></label>
                                            <select class="form-control" id="account_type" name="account_type" required>
                                                @foreach($accountTypes as $key => $type)
                                                    <option value="{{ $key }}" {{ old('account_type', $account->account_type) == $key ? 'selected' : '' }}>
                                                        {{ $type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parent_account_id">Parent Account</label>
                                            <select class="form-control" id="parent_account_id" name="parent_account_id">
                                                <option value="">None</option>
                                                @foreach($parentAccounts as $pa)
                                                    <option value="{{ $pa->id }}" {{ old('parent_account_id', $account->parent_account_id) == $pa->id ? 'selected' : '' }}>
                                                        {{ $pa->account_code }} - {{ $pa->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Opening Balance</label>
                                            <input type="text" class="form-control" value="{{ number_format($account->opening_balance, 2) }}" readonly>
                                            <small class="form-text text-muted">Cannot be changed</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Current Balance</label>
                                            <input type="text" class="form-control" value="{{ number_format($account->current_balance, 2) }}" readonly>
                                            <small class="form-text text-muted">Updated automatically via transactions</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="business_location_id">Business Location</label>
                                            <select class="form-control" id="business_location_id" name="business_location_id">
                                                <option value="">All Locations</option>
                                                @foreach($businessLocations as $location)
                                                    <option value="{{ $location->id }}" {{ old('business_location_id', $account->business_location_id) == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name ?? $location->id }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="is_active">Status</label>
                                            <select class="form-control" id="is_active" name="is_active">
                                                <option value="1" {{ old('is_active', $account->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ old('is_active', $account->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $account->description) }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="las la-save"></i> Update Account
                                    </button>
                                    <a href="{{ route('account.chart') }}" class="btn btn-secondary">
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
