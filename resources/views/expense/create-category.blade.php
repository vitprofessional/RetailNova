@extends('include')

@section('backTitle'){{ __('common.heading_create_expense_category') }}@endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('common.create_expense_category') }}</h4>
            <a href="{{ route('expense.categories') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> {{ __('common.back') }}
            </a>
        </div>

        <form action="{{ route('expense.categories.store') }}" method="POST">
            @csrf
            <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="name">{{ __('common.category_name') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                   placeholder="{{ __('common.e_g_transportation') }}" value="{{ old('name') }}" required>
                                            @error('name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="description">{{ __('common.description') }}</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                      placeholder="{{ __('common.category_description') }}">{{ old('description') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="las la-save"></i> {{ __('common.create_category') }}
                                    </button>
                                    <a href="{{ route('expense.categories') }}" class="btn btn-secondary">
                                        <i class="las la-times"></i> {{ __('common.cancel') }}
                                    </a>
                                </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
