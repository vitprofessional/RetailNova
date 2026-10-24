@extends('include')

@section('backTitle'){{ __('common.heading_edit_account') }}@endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card rn-m-account-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('common.edit_account') }}: {{ $account->account_name }}</h4>
            <a href="{{ route('account.chart') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> {{ __('common.back') }}
            </a>
        </div>

        <form action="{{ route('account.update', $account->id) }}" method="POST" class="rn-m-form rn-m-account-form">
            @csrf
            <div class="row rn-m-form-section rn-m-account-fields">
                                    <div class="col-md-6">
                                        <div class="form-group rn-m-field">
                                            <label for="account_code">{{ __('common.account_code') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control rn-m-input" id="account_code" name="account_code"
                                                   value="{{ old('account_code', $account->account_code) }}" required>
                                            @error('account_code')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group rn-m-field">
                                            <label for="account_name">{{ __('common.account_name') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control rn-m-input" id="account_name" name="account_name"
                                                   value="{{ old('account_name', $account->account_name) }}" required>
                                            @error('account_name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group rn-m-field">
                                            <label for="account_type">{{ __('common.account_type') }} <span class="text-danger">*</span></label>
                                            <select class="form-control rn-m-input" id="account_type" name="account_type" required>
                                                @foreach($accountTypes as $key => $type)
                                                    <option value="{{ $key }}" {{ old('account_type', $account->account_type) == $key ? 'selected' : '' }}>
                                                        {{ $type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group rn-m-field">
                                            <label for="parent_account_id">{{ __('common.parent_account') }}</label>
                                            <select class="form-control rn-m-input" id="parent_account_id" name="parent_account_id">
                                                <option value="">{{ __('common.none') }}</option>
                                                @foreach($parentAccounts as $pa)
                                                    <option value="{{ $pa->id }}" {{ old('parent_account_id', $account->parent_account_id) == $pa->id ? 'selected' : '' }}>
                                                        {{ $pa->account_code }} - {{ $pa->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group rn-m-field">
                                            <label>{{ __('common.opening_balance') }}</label>
                                            <input type="text" class="form-control rn-m-input" value="{{ number_format($account->opening_balance, 2) }}" readonly>
                                            <small class="form-text text-muted">{{ __('common.cannot_be_changed') }}</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group rn-m-field">
                                            <label>{{ __('common.current_balance') }}</label>
                                            <input type="text" class="form-control rn-m-input" value="{{ number_format($account->current_balance, 2) }}" readonly>
                                            <small class="form-text text-muted">{{ __('common.updated_automatically_via_transactions') }}</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group rn-m-field">
                                            <label for="business_location_id">{{ __('common.business_location') }}</label>
                                            <select class="form-control rn-m-input" id="business_location_id" name="business_location_id">
                                                <option value="">{{ __('common.all_locations') }}</option>
                                                @foreach($businessLocations as $location)
                                                    <option value="{{ $location->id }}" {{ old('business_location_id', $account->business_location_id) == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name ?? $location->id }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group rn-m-field">
                                            <label for="is_active">{{ __('common.status') }}</label>
                                            <select class="form-control rn-m-input" id="is_active" name="is_active">
                                                <option value="1" {{ old('is_active', $account->is_active) == 1 ? 'selected' : '' }}>{{ __('common.active') }}</option>
                                                <option value="0" {{ old('is_active', $account->is_active) == 0 ? 'selected' : '' }}>{{ __('common.inactive') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group rn-m-field">
                                            <label for="description">{{ __('common.description') }}</label>
                                            <textarea class="form-control rn-m-input" id="description" name="description" rows="3">{{ old('description', $account->description) }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="rn-m-sticky-actions rn-m-account-actions">
                                    <a href="{{ route('account.chart') }}" class="btn btn-secondary rn-m-btn rn-m-btn--outline">
                                        <i class="las la-times"></i> {{ __('common.cancel') }}
                                    </a>
                                    <button type="submit" class="btn btn-primary rn-m-btn rn-m-btn--primary">
                                        <i class="las la-save"></i> {{ __('common.update_account') }}
                                    </button>
                                </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
