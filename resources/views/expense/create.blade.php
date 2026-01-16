@extends('include')

@section('backTitle')Add New Expense @endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Add New Expense</h4>
            <a href="{{ route('expense.list') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> Back
            </a>
        </div>

        <form action="{{ route('expense.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="expense_date">Expense Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="expense_date" name="expense_date" 
                                                   value="{{ old('expense_date', date('Y-m-d')) }}" required>
                                            @error('expense_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category_id">Category <span class="text-danger">*</span></label>
                                            <select class="form-control" id="category_id" name="category_id" required>
                                                <option value="">Select Category</option>
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
                                            <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                                            <select class="form-control" id="payment_method" name="payment_method" required>
                                                <option value="">Select Method</option>
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
                                            <label for="reference_no">Reference Number</label>
                                            <input type="text" class="form-control" id="reference_no" name="reference_no" 
                                                   placeholder="e.g., RECEIPT-123" value="{{ old('reference_no') }}">
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

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="receipt_file">Upload Receipt</label>
                                            <input type="file" class="form-control" id="receipt_file" name="receipt_file" accept=".jpg,.jpeg,.png,.pdf">
                                            <small class="form-text text-muted">Max 2MB (JPG, PNG, PDF)</small>
                                            @error('receipt_file')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="expense_account_id">Expense Account (Optional)</label>
                                            <select class="form-control" id="expense_account_id" name="expense_account_id">
                                                <option value="">Select Account</option>
                                                @foreach($expenseAccounts as $account)
                                                    <option value="{{ $account->id }}">
                                                        {{ $account->account_code }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">For accounting integration</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="payment_account_id">Payment Account (Optional)</label>
                                            <select class="form-control" id="payment_account_id" name="payment_account_id">
                                                <option value="">Select Account</option>
                                                @foreach($paymentAccounts as $account)
                                                    <option value="{{ $account->id }}">
                                                        {{ $account->account_code }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">Cash/Bank account used for payment</small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3" 
                                                      placeholder="Expense details...">{{ old('description') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <strong>Accounting Integration:</strong> If you select both Expense Account and Payment Account, 
                                    a double-entry transaction will be automatically created.
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="las la-save"></i> Save Expense
                                    </button>
                                    <a href="{{ route('expense.list') }}" class="btn btn-secondary">
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
