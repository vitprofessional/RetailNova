@extends('include')
@section('backTitle'){{ __('common.heading_super_admin_setup') }}@endsection
@section('container')
<div class="col-12 rn-m-first-setup">
    @include('sweetalert::alert')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ __('authentication.first_time_super_admin_setup') }}</h5></div>
        <div class="card-body">
            <h5 class="rn-m-first-setup-title">{{ __('authentication.first_time_super_admin_setup') }}</h5>
            <p class="text-muted rn-m-form-page-intro">{{ __('authentication.create_the_first_super_admin_account_once_created_this_form_is_disabled') }}</p>
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form action="{{ route('superadmin.setup.store') }}" method="POST" class="rn-m-form rn-m-admin-form">
                @csrf
                <div class="row rn-m-form-section">
                    <div class="col-md-6 mb-3 rn-m-admin-field">
                        <label class="form-label">{{ __('authentication.business_name') }}</label>
                        <input name="businessName" class="form-control" value="{{ old('businessName') }}" required>
                    </div>
                    <div class="col-md-6 mb-3 rn-m-admin-field">
                        <label class="form-label">{{ __('authentication.full_name') }}</label>
                        <input name="fullName" class="form-control" value="{{ old('fullName') }}" required>
                    </div>
                    <div class="col-md-6 mb-3 rn-m-admin-field">
                        <label class="form-label">{{ __('authentication.sure_name') }}</label>
                        <input name="sureName" class="form-control" value="{{ old('sureName') }}">
                    </div>
                    <div class="col-md-6 mb-3 rn-m-admin-field">
                        <label class="form-label">{{ __('authentication.email') }}</label>
                        <input name="mail" type="email" class="form-control" value="{{ old('mail') }}" required>
                    </div>
                    <div class="col-md-6 mb-3 rn-m-admin-field">
                        <label class="form-label">{{ __('authentication.password') }}</label>
                        <input name="password" type="password" class="form-control" autocomplete="new-password" required>
                    </div>
                    <div class="col-md-6 mb-3 rn-m-admin-field">
                        <label class="form-label">{{ __('authentication.confirm_password') }}</label>
                        <input name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required>
                    </div>
                    <div class="col-md-6 mb-3 rn-m-admin-field">
                        <label class="form-label">{{ __('authentication.store_name') }}</label>
                        <input name="storeName" class="form-control" value="{{ old('storeName') }}">
                    </div>
                    <div class="col-md-6 mb-3 rn-m-admin-field">
                        <label class="form-label">{{ __('authentication.contact_number') }}</label>
                        <input name="contactNumber" class="form-control" value="{{ old('contactNumber') }}">
                    </div>
                    @if ($requiresSetupToken)
                        <div class="col-md-6 mb-3 rn-m-admin-field">
                            <label class="form-label">{{ __('authentication.setup_token') }}</label>
                            <input name="setup_token" type="password" class="form-control" autocomplete="off" required>
                        </div>
                    @endif
                </div>
                <div class="rn-m-sticky-actions rn-m-admin-actions">
                    <a href="{{ route('userLogin') }}" class="btn btn-secondary rn-m-btn rn-m-btn--outline">{{ __('authentication.back_to_login') }}</a>
                    <button class="btn btn-primary rn-m-btn rn-m-btn--primary">{{ __('authentication.create_super_admin') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
