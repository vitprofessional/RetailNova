@extends('include')
@section('backTitle'){{ __('common.heading_sales_settings') }}@endsection
@section('container')
<div class="col-12 col-lg-8">
    <div class="card"><div class="card-body">
        <h4>{{ __('sales.sales_settings') }}</h4>
        @if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
        @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
        <form method="GET" action="{{ route('sales.settings.edit') }}">
            <label for="salesSettingsBusiness">{{ __('sales.business') }}</label>
            <select id="salesSettingsBusiness" name="businessId" class="form-control" onchange="this.form.submit()">
                @foreach($businesses as $option)
                <option value="{{ $option->id }}" @selected($business && $business->id === $option->id)>{{ $option->businessName }}</option>
                @endforeach
            </select>
        </form>
        @if($business)
        <form method="POST" action="{{ route('sales.settings.update') }}" class="mt-3">
            @csrf
            <input type="hidden" name="businessId" value="{{ $business->id }}">
            <input type="hidden" name="sales_serial_selection_required" value="0">
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="salesSerialRequired" name="sales_serial_selection_required" value="1"
                    @checked(old('sales_serial_selection_required', $business->sales_serial_selection_required ?? true))>
                <label class="custom-control-label" for="salesSerialRequired">{{ __('sales.require_manual_serial_selection_for_serialized_sales') }}</label>
            </div>
            <p class="text-muted mt-2">{{ __('sales.when_disabled_selection_remains_available_leaving_it_empty_automatically_assigns_available_serials_for_the_full_quantity_stock_and_serial_availability_checks_always_apply_backorders_allocate_serials_only_when_fulfilled') }}</p>
            <button type="submit" class="btn btn-primary">{{ __('sales.save_sales_settings') }}</button>
        </form>
        @else
        <p class="mt-3">{{ __('sales.create_a_business_before_configuring_its_sales_settings') }}</p>
        @endif
    </div></div>
</div>
@endsection
