@extends('include')

@section('backTitle')Edit Expense @endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Edit Expense</h4>
            <a href="{{ route('expense.list') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> Back
            </a>
        </div>

        <form action="{{ route('expense.update', $expense->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="expense_date">Expense Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="expense_date" name="expense_date" 
                                                   value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category_id">Category <span class="text-danger">*</span></label>
                                            <select class="form-control" id="category_id" name="category_id" required>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ old('category_id', $expense->category_id) == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="amount">Amount <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                                   value="{{ old('amount', $expense->amount) }}" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                                            <select class="form-control" id="payment_method" name="payment_method" required>
                                                @foreach($paymentMethods as $key => $method)
                                                    <option value="{{ $key }}" {{ old('payment_method', $expense->payment_method) == $key ? 'selected' : '' }}>
                                                        {{ $method }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reference_no">Reference Number</label>
                                            <input type="text" class="form-control" id="reference_no" name="reference_no" 
                                                   value="{{ old('reference_no', $expense->reference_no) }}">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="business_location_id">Business Location</label>
                                            <select class="form-control" id="business_location_id" name="business_location_id">
                                                <option value="">All Locations</option>
                                                @foreach($businessLocations as $location)
                                                    <option value="{{ $location->id }}" {{ old('business_location_id', $expense->business_location_id) == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name ?? $location->id }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="receipt_file">Upload New Receipt</label>
                                            @if($expense->receipt_file)
                                                <div class="mb-2">
                                                    <a href="{{ Storage::url($expense->receipt_file) }}" target="_blank" class="btn btn-sm btn-info">
                                                        <i class="las la-file"></i> View Current Receipt
                                                    </a>
                                                </div>
                                            @endif
                                            <input type="file" class="form-control" id="receipt_file" name="receipt_file" accept=".jpg,.jpeg,.png,.pdf">
                                            <small class="form-text text-muted">Max 2MB (JPG, PNG, PDF) - Leave empty to keep current receipt</small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $expense->description) }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="las la-save"></i> Update Expense
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
