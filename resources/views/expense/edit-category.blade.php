@extends('include')

@section('backTitle'){{ __('common.heading_edit_expense_category') }}@endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card rn-m-expense-category-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('common.edit_expense_category') }}</h4>
            <a href="{{ route('expense.categories') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> {{ __('common.back') }}
            </a>
        </div>

        <form action="{{ route('expense.categories.update', $category->id) }}" method="POST" class="rn-m-form rn-m-expense-category-form">
            @csrf
            <div class="row rn-m-form-section rn-m-expense-category-fields">
                                    <div class="col-md-12">
                                        <div class="form-group rn-m-field">
                                            <label for="name">{{ __('common.category_name') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control rn-m-input" id="name" name="name"
                                                   value="{{ old('name', $category->name) }}" required>
                                            @error('name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group rn-m-field">
                                            <label for="description">{{ __('common.description') }}</label>
                                            <textarea class="form-control rn-m-input" id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group rn-m-field">
                                            <label for="is_active">{{ __('common.status') }}</label>
                                            <select class="form-control rn-m-input" id="is_active" name="is_active">
                                                <option value="1" {{ old('is_active', $category->is_active) == 1 ? 'selected' : '' }}>{{ __('common.active') }}</option>
                                                <option value="0" {{ old('is_active', $category->is_active) == 0 ? 'selected' : '' }}>{{ __('common.inactive') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="rn-m-sticky-actions rn-m-expense-category-actions">
                                    <a href="{{ route('expense.categories') }}" class="btn btn-secondary rn-m-btn rn-m-btn--outline">
                                        <i class="las la-times"></i> {{ __('common.cancel') }}
                                    </a>
                                    <button type="submit" class="btn btn-primary rn-m-btn rn-m-btn--primary">
                                        <i class="las la-save"></i> {{ __('common.update_category') }}
                                    </button>
                                </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
