@extends('include')

@section('backTitle'){{ __('common.heading_create_new_account') }}@endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('common.create_new_account') }}</h4>
            <a href="{{ route('account.chart') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> {{ __('common.back') }}
            </a>
        </div>

        <form action="{{ route('account.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="account_code">{{ __('common.account_code') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="account_code" name="account_code"
                               placeholder="{{ __('common.e_g_1150') }}" value="{{ old('account_code') }}" required>
                        <small class="form-text text-muted">{{ __('common.unique_code_for_the_account') }}</small>
                        @error('account_code')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="account_name">{{ __('common.account_name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="account_name" name="account_name"
                               placeholder="{{ __('common.e_g_petty_cash') }}" value="{{ old('account_name') }}" required>
                        @error('account_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="account_type">{{ __('common.account_type') }} <span class="text-danger">*</span></label>
                        <select class="form-control" id="account_type" name="account_type" required>
                            <option value="">{{ __('common.select_account_type') }}</option>
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
                        <label for="parent_account_id">{{ __('common.parent_account_optional') }}</label>
                        <select class="form-control" id="parent_account_id" name="parent_account_id">
                            <option value="">{{ __('common.none_main_account') }}</option>
                            @foreach($parentAccounts as $account)
                                <option value="{{ $account->id }}" {{ old('parent_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">{{ __('common.create_sub_account_under_a_parent_account') }}</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="opening_balance">{{ __('common.opening_balance') }}</label>
                        <input type="number" step="0.01" class="form-control" id="opening_balance"
                               name="opening_balance" placeholder="0.00" value="{{ old('opening_balance', 0) }}">
                        <small class="form-text text-muted">{{ __('common.initial_balance_for_this_account') }}</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="business_location_id">{{ __('common.business_location_optional') }}</label>
                        <select class="form-control" id="business_location_id" name="business_location_id">
                            <option value="">{{ __('common.all_locations') }}</option>
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
                        <label for="description">{{ __('common.description') }}</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="{{ __('common.account_description') }}">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="las la-save"></i> {{ __('common.create_account') }}
                    </button>
                    <a href="{{ route('account.chart') }}" class="btn btn-secondary">
                        <i class="las la-times"></i> {{ __('common.cancel') }}
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
