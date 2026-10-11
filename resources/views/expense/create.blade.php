@extends('include')

@section('backTitle'){{ __('common.heading_add_new_expense') }}@endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('common.add_new_expense') }}</h4>
            <a href="{{ route('expense.list') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> {{ __('common.back') }}
            </a>
        </div>

        <form action="{{ route('expense.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="expense_date">{{ __('common.expense_date') }} <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="expense_date" name="expense_date"
                                                   value="{{ old('expense_date', date('Y-m-d')) }}" required>
                                            @error('expense_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category_id">{{ __('common.category') }} <span class="text-danger">*</span></label>
                                            <select class="form-control" id="category_id" name="category_id" required>
                                                <option value="">{{ __('common.select_category') }}</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('category_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="amount">{{ __('common.amount') }} <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control" id="amount" name="amount"
                                                   placeholder="0.00" value="{{ old('amount') }}" required>
                                            @error('amount')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="payment_method">{{ __('common.payment_method') }} <span class="text-danger">*</span></label>
                                            <select class="form-control" id="payment_method" name="payment_method" required>
                                                <option value="">{{ __('common.select_method') }}</option>
                                                @foreach($paymentMethods as $key => $method)
                                                    <option value="{{ $key }}" {{ old('payment_method') == $key ? 'selected' : '' }}>
                                                        {{ $method }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('payment_method')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reference_no">{{ __('common.reference_number') }}</label>
                                            <input type="text" class="form-control" id="reference_no" name="reference_no"
                                                   placeholder="{{ __('common.e_g_receipt_123') }}" value="{{ old('reference_no') }}">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="business_location_id">{{ __('common.business_location') }}</label>
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

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="receipt_file">{{ __('common.upload_receipt') }}</label>
                                            <input type="file" class="form-control" id="receipt_file" name="receipt_file" accept=".jpg,.jpeg,.png,.pdf">
                                            <small class="form-text text-muted">{{ __('common.max_2mb_jpg_png_pdf') }}</small>
                                            @error('receipt_file')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="expense_account_id">{{ __('common.expense_account_optional') }}</label>
                                            <select class="form-control" id="expense_account_id" name="expense_account_id">
                                                <option value="">{{ __('common.select_account') }}</option>
                                                @foreach($expenseAccounts as $account)
                                                    <option value="{{ $account->id }}">
                                                        {{ $account->account_code }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">{{ __('common.for_accounting_integration') }}</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="payment_account_id">{{ __('common.payment_account_optional') }}</label>
                                            <select class="form-control" id="payment_account_id" name="payment_account_id">
                                                <option value="">{{ __('common.select_account') }}</option>
                                                @foreach($paymentAccounts as $account)
                                                    <option value="{{ $account->id }}">
                                                        {{ $account->account_code }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">{{ __('common.cash_bank_account_used_for_payment') }}</small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="description">{{ __('common.description') }}</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                      placeholder="{{ __('common.expense_details') }}">{{ old('description') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <strong>{{ __('common.accounting_integration') }}:</strong> {{ __('common.accounting_integration_explanation') }}
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="las la-save"></i> {{ __('common.save_expense') }}
                                    </button>
                                    <a href="{{ route('expense.list') }}" class="btn btn-secondary">
                                        <i class="las la-times"></i> {{ __('common.cancel') }}
                                    </a>
                                </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
