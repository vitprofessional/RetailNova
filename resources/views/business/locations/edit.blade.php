@extends('include')
@section('backTitle') Edit Business Location @endsection
@section('container')

<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Edit Business Location</h4>
            <a href="{{ route('business.locations') }}" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> Back
            </a>
        </div>
                <form action="{{ route('business.locations.update', $location->id) }}" method="POST">
                    @csrf

                    <!-- Location Name -->
                    <div class="form-group mb-4">
                        <label for="name" class="form-label font-weight-600">
                            <i class="las la-store mr-2"></i>Location Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" placeholder="e.g., Main Store, Branch Office"
                               value="{{ old('name', $location->name) }}" required>
                        @error('name')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Location Details -->
                    <div class="form-section mb-4">
                        <h6 class="font-weight-700 mb-3 pb-2 border-bottom">
                            <i class="las la-map-pin mr-2"></i>Address Information
                        </h6>

                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label for="address" class="form-label font-weight-600">
                                    <i class="las la-map-pin mr-2"></i>Street Address <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror"
                                       id="address" name="address" placeholder="Enter street address"
                                       value="{{ old('address', $location->address) }}" required>
                                @error('address')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="city" class="form-label font-weight-600">
                                    <i class="las la-city mr-2"></i>City <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror"
                                       id="city" name="city" placeholder="Enter city"
                                       value="{{ old('city', $location->city) }}" required>
                                @error('city')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="state" class="form-label font-weight-600">
                                    <i class="las la-map mr-2"></i>State/Province <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('state') is-invalid @enderror"
                                       id="state" name="state" placeholder="Enter state or province"
                                       value="{{ old('state', $location->state) }}" required>
                                @error('state')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="postal_code" class="form-label font-weight-600">
                                    <i class="las la-envelope mr-2"></i>Postal Code <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('postal_code') is-invalid @enderror"
                                       id="postal_code" name="postal_code" placeholder="Enter postal code"
                                       value="{{ old('postal_code', $location->postal_code) }}" required>
                                @error('postal_code')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="country" class="form-label font-weight-600">
                                    <i class="las la-globe mr-2"></i>Country <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('country') is-invalid @enderror"
                                       id="country" name="country" placeholder="Enter country"
                                       value="{{ old('country', $location->country) }}" required>
                                @error('country')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="form-section mb-4">
                        <h6 class="font-weight-700 mb-3 pb-2 border-bottom">
                            <i class="las la-phone mr-2"></i>Contact Information
                        </h6>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="phone" class="form-label font-weight-600">
                                    <i class="las la-phone mr-2"></i>Phone Number <span class="text-danger">*</span>
                                </label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" placeholder="Enter phone number"
                                       value="{{ old('phone', $location->phone) }}" required>
                                @error('phone')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="email" class="form-label font-weight-600">
                                    <i class="las la-envelope mr-2"></i>Email Address
                                </label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" placeholder="Enter email address"
                                       value="{{ old('email', $location->email) }}">
                                @error('email')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-4">
                                <label for="manager_name" class="form-label font-weight-600">
                                    <i class="las la-user mr-2"></i>Location Manager Name
                                </label>
                                <input type="text" class="form-control @error('manager_name') is-invalid @enderror"
                                       id="manager_name" name="manager_name" placeholder="Enter manager name"
                                       value="{{ old('manager_name', $location->manager_name) }}">
                                @error('manager_name')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Additional Settings -->
                    <div class="form-section mb-4">
                        <h6 class="font-weight-700 mb-3 pb-2 border-bottom">
                            <i class="las la-sliders-h mr-2"></i>Additional Settings
                        </h6>

                        <div class="form-group form-check mb-4 p-3 bg-light rounded">
                            <input type="checkbox" class="form-check-input" id="is_main_location" 
                                   name="is_main_location" value="1" {{ old('is_main_location', $location->is_main_location) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_main_location">
                                <span class="font-weight-600">Set as Main Location</span>
                                <small class="d-block text-muted">(This will be the primary business location)</small>
                            </label>
                        </div>

                        <div class="form-group form-check mb-4 p-3 bg-light rounded">
                            <input type="checkbox" class="form-check-input" id="status" 
                                   name="status" value="1" {{ old('status', $location->status) ? 'checked' : '' }}>
                            <label class="form-check-label" for="status">
                                <span class="font-weight-600">Active Status</span>
                                <small class="d-block text-muted">(Location is available for operations)</small>
                            </label>
                        </div>

                        <div class="form-group mb-4">
                            <label for="description" class="form-label font-weight-600">
                                <i class="las la-file-alt mr-2"></i>Description
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3"
                                      placeholder="Enter any additional details about this location">{{ old('description', $location->description) }}</textarea>
                            @error('description')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="las la-save mr-2"></i>Update Location
                            </button>
                            <a href="{{ route('business.locations') }}" class="btn btn-secondary btn-lg ml-2">
                                <i class="las la-times mr-2"></i>Cancel
                            </a>
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

