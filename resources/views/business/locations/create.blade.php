@extends('include')
@section('backTitle'){{ __('common.heading_create_business_location') }}@endsection
@section('container')

<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card rn-m-location-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('settings.create_new_business_location') }}</h4>
            <a href="{{ route('business.locations') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> {{ __('settings.back') }}
            </a>
        </div>
                <form class="rn-m-form rn-m-location-form" action="{{ route('business.locations.store') }}" method="POST">
                    @csrf

                    <!-- Location Name -->
                    <div class="form-group mb-4 rn-m-field">
                        <label for="name" class="form-label font-weight-600">
                            <i class="las la-store mr-2"></i>{{ __('settings.location_name') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control rn-m-input @error('name') is-invalid @enderror"
                               id="name" name="name" placeholder="{{ __('common.e_g_main_store_branch_office') }}"
                               value="{{ old('name') }}" required>
                        @error('name')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Location Details -->
                    <div class="form-section mb-4 rn-m-form-section rn-m-location-section">
                        <h6 class="font-weight-700 mb-3 pb-2 border-bottom">
                            <i class="las la-map-pin mr-2"></i>{{ __('settings.address_information') }}
                        </h6>

                        <div class="row rn-m-location-grid">
                            <div class="col-md-12 mb-4">
                                <label for="address" class="form-label font-weight-600">
                                    <i class="las la-map-pin mr-2"></i>{{ __('settings.street_address') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control rn-m-input @error('address') is-invalid @enderror"
                                       id="address" name="address" placeholder="{{ __('common.enter_street_address') }}"
                                       value="{{ old('address') }}" required>
                                @error('address')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="city" class="form-label font-weight-600">
                                    <i class="las la-city mr-2"></i>{{ __('settings.city') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control rn-m-input @error('city') is-invalid @enderror"
                                       id="city" name="city" placeholder="{{ __('common.enter_city') }}"
                                       value="{{ old('city') }}" required>
                                @error('city')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="state" class="form-label font-weight-600">
                                    <i class="las la-map mr-2"></i>{{ __('settings.state_province') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control rn-m-input @error('state') is-invalid @enderror"
                                       id="state" name="state" placeholder="{{ __('common.enter_state_or_province') }}"
                                       value="{{ old('state') }}" required>
                                @error('state')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="postal_code" class="form-label font-weight-600">
                                    <i class="las la-envelope mr-2"></i>{{ __('settings.postal_code') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control rn-m-input @error('postal_code') is-invalid @enderror"
                                       id="postal_code" name="postal_code" placeholder="{{ __('common.enter_postal_code') }}"
                                       value="{{ old('postal_code') }}" required>
                                @error('postal_code')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="country" class="form-label font-weight-600">
                                    <i class="las la-globe mr-2"></i>{{ __('settings.country') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control rn-m-input @error('country') is-invalid @enderror"
                                       id="country" name="country" placeholder="{{ __('common.enter_country') }}"
                                       value="{{ old('country') }}" required>
                                @error('country')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="form-section mb-4 rn-m-form-section rn-m-location-section">
                        <h6 class="font-weight-700 mb-3 pb-2 border-bottom">
                            <i class="las la-phone mr-2"></i>{{ __('settings.contact_information') }}
                        </h6>

                        <div class="row rn-m-location-grid">
                            <div class="col-md-6 mb-4">
                                <label for="phone" class="form-label font-weight-600">
                                    <i class="las la-phone mr-2"></i>{{ __('settings.phone_number') }} <span class="text-danger">*</span>
                                </label>
                                <input type="tel" class="form-control rn-m-input @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" placeholder="{{ __('common.enter_phone_number') }}"
                                       value="{{ old('phone') }}" required>
                                @error('phone')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="email" class="form-label font-weight-600">
                                    <i class="las la-envelope mr-2"></i>{{ __('settings.email_address') }}
                                </label>
                                <input type="email" class="form-control rn-m-input @error('email') is-invalid @enderror"
                                       id="email" name="email" placeholder="{{ __('common.enter_email_address') }}"
                                       value="{{ old('email') }}">
                                @error('email')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-4">
                                <label for="manager_name" class="form-label font-weight-600">
                                    <i class="las la-user mr-2"></i>{{ __('settings.location_manager_name') }}
                                </label>
                                <input type="text" class="form-control rn-m-input @error('manager_name') is-invalid @enderror"
                                       id="manager_name" name="manager_name" placeholder="{{ __('common.enter_manager_name') }}"
                                       value="{{ old('manager_name') }}">
                                @error('manager_name')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Additional Settings -->
                    <div class="form-section mb-4 rn-m-form-section rn-m-location-section">
                        <h6 class="font-weight-700 mb-3 pb-2 border-bottom">
                            <i class="las la-sliders-h mr-2"></i>{{ __('settings.additional_settings') }}
                        </h6>

                        <div class="form-group form-check mb-4 p-3 bg-light rounded rn-m-location-toggle">
                            <input type="checkbox" class="form-check-input" id="is_main_location"
                                   name="is_main_location" value="1" {{ old('is_main_location') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_main_location">
                                <span class="font-weight-600">{{ __('settings.set_as_main_location') }}</span>
                                <small class="d-block text-muted">{{ __('settings.main_location_description') }}</small>
                            </label>
                        </div>

                        <div class="form-group form-check mb-4 p-3 bg-light rounded rn-m-location-toggle">
                            <input type="checkbox" class="form-check-input" id="status"
                                   name="status" value="1" {{ old('status', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="status">
                                <span class="font-weight-600">{{ __('settings.active_status') }}</span>
                                <small class="d-block text-muted">{{ __('settings.location_active_description') }}</small>
                            </label>
                        </div>

                        <div class="form-group mb-4 rn-m-field">
                            <label for="description" class="form-label font-weight-600">
                                <i class="las la-file-alt mr-2"></i>{{ __('settings.description') }}
                            </label>
                            <textarea class="form-control rn-m-input @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3"
                                      placeholder="{{ __('common.enter_any_additional_details_about_this_location') }}">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="rn-m-sticky-actions rn-m-location-actions">
                        <div class="col-12">
                            <a href="{{ route('business.locations') }}" class="btn btn-secondary btn-lg ml-2 rn-m-btn rn-m-btn--outline">
                                <i class="las la-times mr-2"></i>{{ __('settings.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg rn-m-btn rn-m-btn--primary">
                                <i class="las la-save mr-2"></i>{{ __('settings.create_location') }}
                            </button>
                        </div>
                    </div>
                </form>
    </div>
</div>
@endsection
<style>
    .form-section {
        background-color: #f8f9fa;
        padding: 1.5rem;
        border-radius: 0.5rem;
    }
    .is-invalid {
        border-color: #dc3545;
    }
    .invalid-feedback {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
</style>
